<?php

namespace Ajtarragona\AlfrescoLaravel\Middlewares;

use Closure;

class AlfrescoExplorer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
    	if (!config("alfresco.explorer")) {
    		 $error=__("Oops! Alfresco explorer is disabled");
    		 return view("alfresco::error",compact('error'));
        }

        return $next($request);
    }
}