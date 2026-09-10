<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    // Laravel 11: the base controller is empty and extends NOTHING by default.
    // - extends BaseController  → provides $this->middleware()
    //   (needed by authorizeResource) + callAction()
    // - trait AuthorizesRequests → provides $this->authorize()
    use AuthorizesRequests;
}
