<?php

namespace Liteweb\TimberLaravel;

class TimberLaravel
{
    private $context;

    private static $unique_id;
    private static $pending_https = [];

    function __construct()
    {
        $this->context['TAPI_do_format'] = true;
    }


    public function debug(string $msg, array $data)
    {
        $this->log('DEBUG', $msg, $data);
    }

    public function info(string $msg, array $data)
    {
        $this->log('INFO', $msg, $data);
    }

    public function warn(string $msg, array $data)
    {
        $this->log('WARNING', $msg, $data);
    }

    public function error(string $msg, array $data)
    {
        $this->log('ERROR', $msg, $data);
    }

    public function critical(string $msg, array $data)
    {
        $this->log('CRITICAL', $msg, $data);
    }

    private function log(string $level, string $msg, array $data)
    {
        $this->context['TAPI_event'] = ['custom' => ['Additional Data' => $data]];

        \Log::log($level, $msg, $this->context);
    }

    public function httpResponse($response)
    {
        if($response instanceof \GuzzleHttp\Psr7\Response)
        {
            $httpFoundationFactory = new \Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory();

            $response = $httpFoundationFactory->createResponse($response);
        }

        if(!($response instanceof \Symfony\Component\HttpFoundation\Response))
        {
            throw new \Exception('Response parameter was not of type Symfony\Component\HttpFoundation\Response. Found: ' . get_class($response));
        }

        $pending = array_pop(static::$pending_https);

        if(!$pending or !isset($pending['url'], $pending['http'], $pending['http_request']))
        {
            throw new \Exception('Attempt to prepare response without preparing request');
        }

        $request = $pending['http_request'];

        $direction = 'incoming' === $request['direction'] ? 'outgoing' : 'incoming';

        $http_response = [
            'direction'    => $direction,
            'headers_json' => json_encode(array_map(function($v) { return $v[0]; }, $response->headers->all())),
            'request_id'   => $this->getUniqueId(),
            'status'       => $response->getStatusCode(),
            'request'      => [
                'host'         => $request['host'],
                'method'       => $request['method'],
                'path'         => $request['path'],
                'scheme'       => $request['scheme'],
            ]
        ];

        if($response->getContent())
        {
            $body = substr((string)$response->getContent(), 0, 8192);
            $http_response['body']           = $body;
            $http_response['content_length'] = strlen($body);
        }

        $this->context['TAPI_context'] = ['http'          => $pending['http']];
        $this->context['TAPI_event']   = ['http_response' => $http_response];

        $where = 'incoming' === $direction ? 'Received' : 'Sent';

        \Log::info($where . ' response ' . $response->getStatusCode(), $this->context);
    }

    public function httpRequest($request)
    {
        if($request instanceof \GuzzleHttp\Psr7\ServerRequest)
        {
            $httpFoundationFactory = new \Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory();

            $request     = \Illuminate\Http\Request::createFromBase($httpFoundationFactory->createRequest($request));
        }

        if(!($request instanceof \Illuminate\Http\Request))
        {
            throw new \Exception('Request parameter was not of type Illuminate\Http\Request. Found: ' . get_class($request));
        }

        $scheme    = $request->secure() ? 'https' : 'http';
        $host      = $request->header('host') ?? $request->getHost();
        $addr      = $request->ip() ?? gethostbyname($request->getHost());
        $direction = $request->ip() ? 'incoming' : 'outgoing';
        $query_str = http_build_query($request->query());

        $http = [
            'host'        => $host,
            'method'      => $request->method(),
            'path'        => $request->path(),
            'remote_addr' => $addr,
            'request_id'  => $this->getUniqueId(),
        ];

        $http_request = [
            'direction'    => $direction,
            'headers_json' => json_encode($request->header()),
            'host'         => $host,
            'method'       => $request->method(),
            'path'         => $request->path(),
            'request_id'   => $this->getUniqueId(),
            'scheme'       => $scheme,
        ];

        if($request->getContent())
        {
            $body = substr((string)$request->getContent(), 0, 8192);
            $http_request['body']           = $body;
            $http_request['content_length'] = strlen($body);
        }

        $query_str and $http_request['query_string'] = $query_str;

        static::$pending_https[] = [
            'url' => $request->fullUrl(),
            'http' => $http,
            'http_request' => $http_request,
        ];

        $where = 'incoming' === $direction ? 'at' : 'to';

        $this->context['TAPI_context'] = ['http'         => $http];
        $this->context['TAPI_event']   = ['http_request' => $http_request];

        \Log::info(ucfirst($direction) . ' query ' . $where . ' ' . trim($request->fullUrl(), ':'), $this->context);
    }

    public function exception(\Exception $e, string $message = NULL)
    {
        $message = $message ?? $e->getMessage();

        $this->context['TAPI_event'] = [
            'error' =>
            [
                'message'   => "{$e->getMessage()} in {$e->getFile()} (line {$e->getLine()})",
                'name'      => substr($message, 0, 256),
                'backtrace' => array_slice(array_map(function($el)
                    {
                        return  [
                            'file'     => $el['file']     ?? '_NULL_',
                            'line'     => $el['line']     ??  1,
                            'function' => $el['function'] ?? '_NULL_'
                        ];
                    }, $e->getTrace()),
                    0, 20)
            ]
        ];

        \Log::critical($message, $this->context);
    }

    private function getUniqueId()
    {
        return static::$unique_id = static::$unique_id ?? \Ramsey\Uuid\Uuid::uuid4()->toString();
    }
}