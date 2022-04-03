<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Interface\StreamDataProviderAPI;

class EnsureTokenIsValid
{
    protected $streamDataProviderAPI;

    public function __construct(StreamDataProviderAPI $_streamDataProviderAPI)
    {
        $this->streamDataProviderAPI = $_streamDataProviderAPI;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $access_token = $request->header('authToken');
        if (is_null($access_token)) {
            $code = $request->input('code');
            if (is_null($code)) {
                return response('Invalid Token', 401);
            }
            $response = $this->streamDataProviderAPI->validateAccessCode($code);
            $isaccesscodevalid = $response['iscodevalid'];
            if (! $isaccesscodevalid) {
                return response($response['errormessage'], 401);
            }
            $access_token = $response['token'];
            $request->headers->set('authToken', $access_token);
        }
        else {
            $getuserfollowedstreams = $request->input('getuserfollowedstreams');
            if ($getuserfollowedstreams == 'true') {
            $isauthcodevalid = $this->streamDataProviderAPI->validateAuthToken($access_token);
            if (! $isauthcodevalid) {
                return response('Invalid Token', 401);
            }
            }
        }
        return $next($request);
    }
}
