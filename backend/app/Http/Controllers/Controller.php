<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    // Laravel 11: base controller kosong dan TIDAK meng-extend apa pun.
    // - extends BaseController  → menyediakan $this->middleware()
    //   (dibutuhkan authorizeResource) + callAction()
    // - trait AuthorizesRequests → menyediakan $this->authorize()
    use AuthorizesRequests;
}
