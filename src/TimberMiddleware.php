<?php

namespace Liteweb\TimberLaravel;

class TimberMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle(\Illuminate\Http\Request $request, \Closure $next)
    {
        \Timber::httpRequest($request);

        return $next($request);
    }

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function terminate(\Illuminate\Http\Request $request, \Symfony\Component\HttpFoundation\Response $response)
    {
        \Timber::httpResponse($response);
    }
}
