<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserTypesApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$types)
    {
        if ( ! empty( $types ) )
        if ( in_array('GUEST', $types) )
            if ( ! auth()->check() ) return $next($request);

        if ( ! auth()->check() )
            goto Redirect;

        if ( empty( $types ) )
            goto Redirect;

        foreach ($types as $key => $type) {
            if ( $type != 'GUEST' )
                if ( $request->user()->type ==  $type  ) return $next($request);

        }

        Redirect : return $next($request);
        // return response()->json([
        //     'message'   => 'fail',
        //     'data'      => [
        //         'result'  => '',
        //     ],
        //     'errors'    => [
        //         'global' => 'permission_denied'
        //     ],
        // ],200);
    }
}
