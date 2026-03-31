<?php

namespace App\Http\Controllers\Auth;

use App\Factories\AuthResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordLoginRequest;
use App\Http\Requests\TenantTokenRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Tenant;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * 使用账户密码登录
     */
    public function password(PasswordLoginRequest $request): JsonResponse
    {
        $credentials = $request->safe()->only(['username', 'password']);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            return ApiResponse::success(new AuthResponse($user), '登录成功');
        }

        return ApiResponse::validationError([
            'username' => '用户名或密码错误',
        ], '账号或密码错误');
    }

    /**
     * 租户获取访问令牌（基于签名验证）
     *
     * 使用 app_key + timestamp + nonce + signature 进行签名验证
     * 签名算法：HMAC-SHA256(app_secret, "app_key={app_key}&timestamp={timestamp}&nonce={nonce}")
     */
    public function tenant(TenantTokenRequest $request): JsonResponse
    {
        $appKey = $request->safe()->string('app_key');
        $timestamp = $request->safe()->integer('timestamp');
        $nonce = $request->safe()->string('nonce');
        $signature = $request->safe()->string('signature');

        // 查找租户
        $tenant = Tenant::where('app_key', $appKey)->first();

        if (!$tenant) {
            return ApiResponse::error('Invalid app_key', 403);
        }

        // 检查租户状态
        if (!$tenant->status) {
            return ApiResponse::error('Tenant has been disabled', 403);
        }

        // 检查租户是否过期
        if ($tenant->expired_at && $tenant->expired_at->isPast()) {
            return ApiResponse::error('Tenant has expired', 403);
        }

        // 验证时间戳（防止重放攻击，允许±5 分钟误差）
        $now = time();
        $timeDiff = abs($now - $timestamp);
        if ($timeDiff > 300) {
            return ApiResponse::error('Timestamp is invalid or expired', 403);
        }

        // 验证签名
        $expectedSignature = $this->generateSignature($appKey, $timestamp, $nonce, $tenant->app_secret);
        if (!hash_equals($expectedSignature, $signature)) {
            return ApiResponse::error('Invalid signature', 403);
        }

        // 记录登录事件
        event(new Login('sanctum', $tenant, false));

        // 生成访问令牌（有效期 2 小时）
        $token = $tenant->createToken('Tenant', ['API'], Carbon::now()->addHours(2));

        return ApiResponse::success([
            'access_token' => base64_encode($token->plainTextToken),
            'token_type' => 'Bearer',
            'expires_in' => 7200, // 2 小时（秒）
        ]);
    }

    /**
     * 生成签名
     *
     * @param  string  $appKey  应用 Key
     * @param  int  $timestamp  时间戳
     * @param  string  $nonce  随机字符串
     * @param  string  $appSecret  应用 Secret
     * @return string 签名结果
     */
    private function generateSignature(string $appKey, int $timestamp, string $nonce, string $appSecret): string
    {
        $signStr = sprintf(
            'app_key=%s&timestamp=%d&nonce=%s',
            $appKey,
            $timestamp,
            $nonce
        );

        return hash_hmac('sha256', $signStr, $appSecret);
    }
}
