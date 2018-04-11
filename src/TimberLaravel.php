<?php

namespace Liteweb\TimberLaravel;

class TimberLaravel
{
    private $monologData;
    private $customContextData;

    function __construct()
    {
        $this->monologData['TAPI_do_format'] = true;
    }

    public function debug(string $msg, array $data = [], bool $customEvent = true)
    {
        $this->log('DEBUG', $msg, $data, $customEvent);
    }

    public function info(string $msg, array $data = [], bool $customEvent = true)
    {
        $this->log('INFO', $msg, $data, $customEvent);
    }

    public function warn(string $msg, array $data = [], bool $customEvent = true)
    {
        $this->log('WARNING', $msg, $data, $customEvent);
    }

    public function warning(string $msg, array $data = [], bool $customEvent = true)
    {
        $this->log('WARNING', $msg, $data, $customEvent);
    }

    public function error(string $msg, array $data = [], bool $customEvent = true)
    {
        $this->log('ERROR', $msg, $data, $customEvent);
    }

    public function critical(string $msg, array $data = [], bool $customEvent = true)
    {
        $this->log('CRITICAL', $msg, $data, $customEvent);
    }

    private function log(string $level, string $msg, array $data, bool $customEvent = true)
    {
        if($customEvent and $data)
        {
            $this->monologData['TAPI_event'] = ['custom' => ['Additional Data' => $data]];
        }
        elseif(!$customEvent and $data)
        {
            $this->monologData['TAPI_event'] = $data;
        }

        $this->monologData['TAPI_context'] = $this->craftContext();

        \Log::log($level, $msg, $this->monologData);
    }

    private function craftContext()
    {
        $context_data = $this->customContextData ?? new ContextData(request());

        $context = [];

        $context['http'] = [
            'host'        => $context_data->http_host,
            'method'      => $context_data->http_method,
            'path'        => $context_data->http_path,
            'remote_addr' => $context_data->http_remote_addr,
            'request_id'  => $context_data->http_request_id,
        ];

        if($context_data->user_id)
        {
            $context['user'] = [
                'email' => $context_data->user_email,
                'name'  => $context_data->user_name,
                'id'    => $context_data->user_id,
            ];
        }

        return $context;
    }

    public function httpResponse($response, bool $incoming = true, ?\Liteweb\TimberLaravel\ContextData $customContextData = null)
    {
        $this->customContextData = $customContextData;

        if($response instanceof \GuzzleHttp\Psr7\Response)
        {
            $httpFoundationFactory = new \Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory();

            $response = $httpFoundationFactory->createResponse($response);
        }

        if(!($response instanceof \Symfony\Component\HttpFoundation\Response))
        {
            throw new \Exception('Response parameter was not of type Symfony\Component\HttpFoundation\Response. Found: ' . get_class($response));
        }

        $direction = $incoming ? 'incoming' : 'outgoing';

        $http_response = [
            'direction'    => $direction,
            'headers_json' => json_encode(array_map(function($v) { return $v[0]; }, $response->headers->all())),
            'status'       => $response->getStatusCode(),
        ];

        if($response->getContent())
        {
            $body = substr((string)$response->getContent(), 0, 8192);
            $http_response['body']           = $body;
            $http_response['content_length'] = strlen($body);
        }

        $where = 'incoming' === $direction ? 'Received' : 'Sent';

        $this->info($where . ' response ' . $response->getStatusCode(), ['http_response' => $http_response], false);
    }

    public function httpRequest($request, bool $outgoing = true, ?\Liteweb\TimberLaravel\ContextData $customContextData = null)
    {
        $this->customContextData = $customContextData;

        $headers = [];

        if($request instanceof \GuzzleHttp\Psr7\ServerRequest)
        {
            $httpFoundationFactory = new \Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory();

            // We have to grab headers before conversion as they are somehow lost
            $headers = array_map(function($v) { return $v[0]; }, $request->getHeaders());

            $request     = \Illuminate\Http\Request::createFromBase($httpFoundationFactory->createRequest($request));
        }
        elseif($request instanceof \Illuminate\Http\Request)
        {
            $headers = $request->header();
        }
        else
        {
            throw new \Exception('Request parameter was not of type Illuminate\Http\Request. Found: ' . get_class($request));
        }

        $scheme    = $request->secure() ? 'https' : 'http';
        $host      = $headers['Host'] ?? $request->getHost();
        $addr      = $request->ip() ?? gethostbyname($request->getHost());
        $direction = $outgoing ? 'outgoing' : 'incoming';
        $query_str = http_build_query($request->query());

        $http_request = [
            'direction'    => $direction,
            'headers_json' => json_encode($headers),
            'host'         => $host,
            'method'       => $request->method(),
            'path'         => $request->path(),
            'scheme'       => $scheme,
        ];

        if($request->getContent())
        {
            $body = substr((string)$request->getContent(), 0, 8192);
            $http_request['body']           = $body;
            $http_request['content_length'] = strlen($body);
        }

        $where = 'incoming' === $direction ? 'at' : 'to';

        $this->info(ucfirst($direction) . ' query ' . $where . ' ' . trim($request->fullUrl(), ':'), ['http_request' => $http_request], false);
    }

    public function exception(\Exception $e, string $message = NULL)
    {
        $message = $message ?? $e->getMessage();

        $error = [
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
        ];

        // There must be a backtrace...
        $error['backtrace'] or $error['backtrace'] = [['file' => '_no_trace_', 'line' => 1, 'function' => '_no_trace_']];

        $this->critical($message, ['error' => $error], false);
    }
}
