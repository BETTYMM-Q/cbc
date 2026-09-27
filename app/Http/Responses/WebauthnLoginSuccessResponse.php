<?php

namespace App\Http\Responses;

use App\Support\PortalRedirector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\LoginSuccessResponse as LoginSuccessResponseContract;

class WebauthnLoginSuccessResponse implements LoginSuccessResponseContract
{
    public function toResponse($request)
    {
        /** @var Request $request */
        $destination = PortalRedirector::destinationFor($request->user());

        return $request->wantsJson()
            ? Response::json(['result' => true, 'callback' => $request->session()->pull('url.intended', $destination)])
            : Redirect::intended($destination);
    }
}
