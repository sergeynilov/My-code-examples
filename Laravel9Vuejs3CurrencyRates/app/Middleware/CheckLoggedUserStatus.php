<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class CheckLoggedUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(isUserLogged()) {
            if (Route::current()->getName() != 'logout.perform') {
                if (auth()->user()->status != 'A') {   //  N => New(Waiting activation), A=>Active, I=>Inactive
                    return redirect(route('logout.perform'));
                }
            }
        }

        return $next($request);
    }
}
