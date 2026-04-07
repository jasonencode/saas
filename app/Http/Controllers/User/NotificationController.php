<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Users\NotificationGroupResource;
use App\Http\Resources\Users\NotificationResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * 通知列表
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $resource = $user->notifications()
            ->select(['id', 'type', 'data', 'read_at', 'created_at'])
            ->when($request->type, function (Builder $builder, $type) {
                $builder->where('type', $type);
            })
            ->paginate();

        return ApiResponse::success($resource);
    }

    /**
     * 通知分组
     */
    public function group(): JsonResponse
    {
        $group = DatabaseNotification::whereMorphedTo('notifiable', Auth::user())
            ->select('type')
            ->distinct()
            ->get();

        return ApiResponse::success(NotificationGroupResource::collection($group));
    }

    public function count(Request $request): JsonResponse
    {
        $user = Auth::user();

        return ApiResponse::success([
            'total' => $user->notifications()
                ->when($request->type, function (Builder $builder, $type) {
                    $builder->where('type', $type);
                })->count(),
            'unread' => $user->unreadNotifications()
                ->when($request->type, function (Builder $builder, $type) {
                    $builder->where('type', $type);
                })->count(),
        ]);
    }

    public function show(DatabaseNotification $notification): JsonResponse
    {
        $this->checkPermission($notification);
        $notification->markAsRead();

        return ApiResponse::success(NotificationResource::make($notification));
    }

    public function markAsRead(DatabaseNotification $notification): JsonResponse
    {
        $this->checkPermission($notification);
        $notification->markAsRead();

        return ApiResponse::noContent('通知已标记为已读');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = Auth::user();
        $notifications = $user->unreadNotifications()
            ->when($request->type, function (Builder $builder, $type) {
                $builder->where('type', $type);
            })
            ->get();
        $notifications->each->markAsRead();

        return ApiResponse::noContent('所有通知已标记为已读');
    }

    public function deleteAllRead(Request $request): JsonResponse
    {
        $user = Auth::user();
        $user->readNotifications()
            ->when($request->type, function (Builder $builder, $type) {
                $builder->where('type', $type);
            })
            ->delete();

        return ApiResponse::noContent('已删除所有已读通知');
    }

    public function destroy(DatabaseNotification $notification): JsonResponse
    {
        $this->checkPermission($notification);
        $notification->delete();

        return ApiResponse::noContent('通知删除成功');
    }
}
