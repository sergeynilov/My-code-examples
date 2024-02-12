<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckIsAdmin
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

        if ( !auth()->user() ) {
            \Log::info(  varDump(-11, ' -11 You are not logged::') );
            return redirect('/')->with('error','You are not logged');
        }
        if ( !auth()->user()->can(ACCESS_APP_ADMIN_LABEL) ) {
            \Log::info(  varDump(-12, ' -12 You have not admin access::') );
            return redirect('/')->with('error','You have not admin access');
        }
        return $next($request);
    }
}
