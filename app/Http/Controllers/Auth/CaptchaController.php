<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Mews\Captcha\Facades\Captcha;

class CaptchaController extends Controller
{
    public function index()
    {
        $res = Captcha::create('default', true);

        return $this->success([
            'key' => $res['key'],
            'img' => $res['img'],
        ]);
    }
}