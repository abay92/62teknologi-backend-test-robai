<?php

namespace App\Http\Middleware;

use App\Traits\ResponseApi;
use Closure;
use Illuminate\Http\Request;

class Localization
{
    use ResponseApi;

    /**
    * Handle an incoming request.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \Closure  $next
    * @return mixed
    */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->locale ?? $request->header('locale') ?? config('app.locale');
        app()->setLocale($locale);

        if (!in_array($locale, ['id', 'en'])) {
            return $this->resError(__('message.not_locale'), 400);
        }

        return $next($request);
    }
}
