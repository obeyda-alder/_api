<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
// use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class validateJWTToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = \JWTAuth::parseToken()->authenticate();
            if(! $user)
            {
                $response = [];
                $response['message'] = 'fail';
                $response['errors']['global'] = 'this_account_does_not_exist';
                $response['data']['result'] = '';
                return response()->json($response, 200);
            }
        } catch (\Exception $e) {
            $response = [];
            $response['message'] = 'fail';
            $response['data']['result'] = '';
            if($e instanceof \Tymon\JWTAuth\Exceptions\UserNotDefinedException)
            {
                $response['errors']['global'] = 'user_not_defined';
            }
            else if($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException)
            {
                $response['errors']['global'] = 'token_invalid';
            }
            else if($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException)
            {
                $response['errors']['global'] = 'token_expired';
            }
            else
            {
                $response['errors']['global'] = 'token_not_provided';
            }
            return response()->json($response, 401);
        }
        return $next($request);
    }
}
