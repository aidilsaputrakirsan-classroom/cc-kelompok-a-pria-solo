<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;

class StatusController extends Controller
{
    public function index()
    {
        return view('status.index', [
            'gatewayBase' => rtrim((string) config('app.url', 'http://localhost:8080'), '/'),
        ]);
    }
}
