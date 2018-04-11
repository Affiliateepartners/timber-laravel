<?php

namespace Liteweb\TimberLaravel;

class HttpClient extends \GuzzleHttp\Client
{
    public function __construct(array $config = [])
    {
        $stack = \GuzzleHttp\HandlerStack::create();

        $stack->push(\GuzzleHttp\Middleware::mapRequest(function (\Psr\Http\Message\RequestInterface $request)
        {
            \Timber::httpRequest(self::craftServerRequest($request));

            return $request;
        }));

        $stack->push(\GuzzleHttp\Middleware::mapResponse(function (\Psr\Http\Message\ResponseInterface $response)
        {
            \Timber::httpResponse($response);

            return $response;
        }));

        $config['handler'] = $stack;

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
