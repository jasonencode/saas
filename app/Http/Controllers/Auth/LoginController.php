<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LoginController extends Controller
{
    public function tenant(Request $request)
    {
        $app_key = $request->post('app_key');
        $app_secret = $request->post('app_secret');

        $tenant = Tenant::where('app_key', $app_key)->first();

        if (!$tenant || $tenant->app_secret !== $app_secret) {
            return $this->error('app_key or app_secret authentication failed');
        }

        event(new Login('sanctum', $tenant, false));

        return $this->success([
            'access_token' => base64_encode($tenant->createToken('Tenant', ['*'],
                Carbon::now()->addHours(2))->plainTextToken),
        ]);
    }
}