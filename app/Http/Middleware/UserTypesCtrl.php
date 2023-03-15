<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserTypesCtrl
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
        $response = [];
        $response['message'] = 'fail';
        $response['data']['result'] = '';

        if ( Auth::check() )
        {
            $user = auth()->guard('api')->user();


            if ( ! Auth::user()->isVerified() )
            {
                $response['errors']['global'] = __('app.base.error_account_needs_to_be_verified');
                Auth::guard('api')->logout();
                $request->session()->invalidate();
                return response()->json($response, 200);

            }
            if ( ! Auth::user()->isApproved() )
            {
                $response['errors']['global'] =  __('app.base.error_account_needs_to_be_approved');
                Auth::guard('api')->logout();
                $request->session()->invalidate();
                return response()->json($response, 200);

            }
            if ( $user->status != 'ACTIVE' )
            {
                $response['errors']['global'] =  __('app.base.error_account_suspended');
                Auth::guard('api')->logout();
                $request->session()->invalidate();
                return response()->json($response, 200);
            }
        }
        return $next($request);
    }
}
