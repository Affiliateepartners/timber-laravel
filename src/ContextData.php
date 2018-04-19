<?php

namespace Liteweb\TimberLaravel;

class ContextData
{
    public $http_host;
    public $http_method;
    public $http_path;
    public $http_remote_addr;
    public $http_request_id;

    public $user_id;
    public $user_email;
    public $user_name;

    public $system_hostname;
    public $system_ip;
    public $system_pid;

    public function __construct(\Illuminate\Http\Request $request)
    {
        $headers = array_map(function($v) { return $v[0]; }, $request->headers->all());
        $salt = "{$request->server('REQUEST_TIME_FLOAT')}{$request->server('REMOTE_PORT')}{$request->server('REQUEST_URI')}{$request->server('REMOTE_ADDR')}";

        $this->http_host        = $headers['host'] ?? $request->getHost();
        $this->http_method      = $request->method();
        $this->http_path        = $request->path();
        $this->http_remote_addr = $request->ip() ?? gethostbyname($request->getHost());
        $this->http_request_id  = sha1($salt);

        if($user = $request->user())
        {
            $this->user_email = $user->email;
            $this->user_name  = $user->name;
            $this->user_id    = (string)$user->id;
        }

        $this->system_hostname = gethostname();
        $this->system_ip       = gethostbyname(gethostname());
        $this->system_pid      = (int)getmypid();
    }
}
