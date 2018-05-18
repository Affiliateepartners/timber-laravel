<?php

namespace Liteweb\TimberLaravel;

class HttpClient extends \GuzzleHttp\Client
{
    public function __construct(array $config = [], ?\Liteweb\TimberLaravel\ContextData $context_data = null)
    {
        $stack = \GuzzleHttp\HandlerStack::create();

        $stack->push(\GuzzleHttp\Middleware::mapRequest(function (\Psr\Http\Message\RequestInterface $request) use ($context_data)
        {
            \Timber::httpRequest(self::craftServerRequest($request), true, $context_data);

            return $request;
        }));

        $stack->push(\GuzzleHttp\Middleware::mapResponse(function (\Psr\Http\Message\ResponseInterface $response) use ($context_data)
        {
            \Timber::httpResponse($response, true, $context_data);

            return $response;
        }));

        $config['handler'] = $stack;
        $config['connect_timeout'] = 30;
        $config['timeout']         = 30;

        parent::__construct($config);
    }

    private static function craftServerRequest(\GuzzleHttp\Psr7\Request $request): \GuzzleHttp\Psr7\ServerRequest
    {
        $method  = $request->getMethod();
        $uri     = $request->getUri()->__toString();
        $headers = $request->getHeaders();
        $body    = $request->getBody();
        $version = $request->getProtocolVersion();

        return new \GuzzleHttp\Psr7\ServerRequest($method, $uri, $headers, $body, $version);
    }
}
