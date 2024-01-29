<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Response;

class APIAuth
{
    public function handle($request, Closure $next): JsonResponse
    {
        //Static Tokens for example. We can implement a database query to improve this workflow.
        $valid_tokens = ['ABC123T','123TEST','zxy9090'];
        if(in_array($request->get('auth_token',null),$valid_tokens)) {
            return $next($request);
        } else {
            return Response::json(['error' => 'Error validating access token'], 401);
        }
    }
}
