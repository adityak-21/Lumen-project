<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

class BroadcastAuthController extends Controller
{
    public function authenticate(Request $request)
    {
        
        if (Auth::check()) {
            \Log::info('Broadcast auth', [
        'user' => Auth::user(),
        'request' => $request->all()
    ]);
            return Broadcast::auth($request);
        }
        return response('Unauthorized.', 403);
    }
}