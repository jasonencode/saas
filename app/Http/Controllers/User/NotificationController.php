<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Users\NotificationCollection;
use App\Http\Resources\Users\NotificationGroupResource;
use App\Http\Resources\Users\NotificationResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * 通知列表
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $resource = $user->notifications()
            ->select(['id', 'type', 'data', 'read_at', 'created_at'])
            ->when($request->type, function (Builder $builder, $type) {
                $builder->where('type', $type);
            })
            ->paginate(config('custom.model_per_page'));

        return $this->success(NotificationCollection::make($resource));
    }

    /**
     * 通知分组
     *
     * @return JsonResponse
     */
    public function group(): JsonResponse
    {
        $group = DatabaseNotification::whereMorphedTo('notifiable', Auth::user())
            ->select('type')
            ->distinct()
            ->get();

        return $this->success(NotificationGroupResource::collection($group));
    }

    public function count(Request $request): JsonResponse
    {
        $user = Auth::user();

        return $this->success([
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

        return $this->success(NotificationResource::make($notification));
    }

    public function markAsRead(DatabaseNotification $notification): JsonResponse
    {
        $this->checkPermission($notification);
        $notification->markAsRead();

        return $this->success();
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

        return $this->success();
    }

    public function deleteAllRead(Request $request): JsonResponse
    {
        $user = Auth::user();
        $user->readNotifications()
            ->when($request->type, function (Builder $builder, $type) {
                $builder->where('type', $type);
            })
            ->delete();

        return $this->success();
    }

    public function destroy(DatabaseNotification $notification): JsonResponse
    {
        $this->checkPermission($notification);
        $notification->delete();

        return $this->success();
    }
}
