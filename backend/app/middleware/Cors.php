<?php

namespace app\middleware;

use think\Response;

class Cors
{
    public function handle($request, \Closure $next)
    {
        $response = $next($request);
        
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Max-Age' => '86400'
        ];
        
        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }
        
        if ($request->method() == 'OPTIONS') {
            return Response::create('', 'html', 200)->header($headers);
        }
        
        return $response;
    }
}
