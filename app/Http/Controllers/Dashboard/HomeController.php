<?php

/**
 * SnowSYS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;

class HomeController extends DashboardController
{
    public function welcome()
    {
        return redirect( '/sign-in' );
    }
}
