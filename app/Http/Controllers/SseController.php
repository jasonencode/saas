<?php

namespace App\Http\Controllers;

use App\Models\Content\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SSE (Server-Sent Events) 控制器
 * 用于处理服务端实时推送逻辑
 */
class SseController extends Controller
{
    /**
     * 推送评论记录
     *
     * @param  Request  $request
     * @return StreamedResponse
     */
    public function index(Request $request): StreamedResponse
    {
        // 获取客户端最后接收到的 ID，实现增量推送
        $lastId = $request->integer('last_id');

        return Response::stream(static function () use (&$lastId) {
            // 设置脚本执行不超时
            @set_time_limit(0);
            // 关闭输出缓冲，确保数据能实时发送
            @ob_end_flush();
            @ob_implicit_flush();

            $start = microtime(true);
            while (true) {
                // 查询状态为启用且 ID 大于上次推送 ID 的评论记录
                $records = Comment::where('id', '>', $lastId)
                    ->orderBy('id')
                    ->get();

                foreach ($records as $item) {
                    // 构建推送数据负载
                    $payload = json_encode([
                        'id' => $item->id,
                        'content' => $item->content,
                    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

                    // 按照 SSE 协议格式输出数据
                    echo "event: comment\n";
                    echo "data: $payload\n\n";

                    // 更新最后推送的 ID
                    $lastId = $item->id;
                }

                // 如果没有新数据，发送 ping 包保持连接活跃
                if ($records->isEmpty()) {
                    echo ": ping\n\n";
                }

                // 强制刷新缓冲区，将数据发送给客户端
                flush();

                // 连接存活时间超过 120 秒则主动断开，建议客户端重连
                if ((microtime(true) - $start) > 120) {
                    break;
                }

                // 轮询间隔，单位为微秒 (800000us = 0.8s)
                usleep(800000);
            }
        }, 200, [
            'Contents-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // 禁用 Nginx 缓存，确保实时推送
        ]);
    }
}
