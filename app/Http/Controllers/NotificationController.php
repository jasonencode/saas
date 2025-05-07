<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationCollection;
use App\Http\Resources\NotificationGroupResource;
use App\Http\Resources\NotificationResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function group()
    {
        $group = DatabaseNotification::whereMorphedTo('notifiable', Auth::user())
            ->select('type')
            ->distinct()
            ->get();

        return $this->success(NotificationGroupResource::collection($group));
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $resource = $user->notifications()
            ->select(['id', 'type', 'data', 'read_at', 'created_at'])
            ->when($request->type, function(Builder $builder, $type) {
                $builder->where('type', $type);
            })
            ->paginate(config('custom.model_per_page'));

        return $this->success(new NotificationCollection($resource));
    }

    public function show(DatabaseNotification $notification)
    {
        $this->checkPermission($notification);
        $notification->markAsRead();

        return $this->success(new NotificationResource($notification));
    }

    public function markAsRead(DatabaseNotification $notification)
    {
        $this->checkPermission($notification);
        $notification->markAsRead();

        return $this->success();
    }

    public function count(Request $request)
    {
        $user = Auth::user();

        return $this->success([
            'total' => $user->notifications()
                ->when($request->type, function(Builder $builder, $type) {
                    $builder->where('type', $type);
                })->count(),
            'unread' => $user->unreadNotifications()
                ->when($request->type, function(Builder $builder, $type) {
                    $builder->where('type', $type);
                })->count(),
        ]);
    }

    public function maskAllAsRead(Request $request)
    {
        $user = Auth::user();
        $notifications = $user->unreadNotifications()
            ->when($request->type, function(Builder $builder, $type) {
                $builder->where('type', $type);
            })
            ->get();
        $notifications->each->markAsRead();

        return $this->success();
    }

    public function deleteAllRead(Request $request)
    {
        $user = Auth::user();
        $user->readNotifications()
            ->when($request->type, function(Builder $builder, $type) {
                $builder->where('type', $type);
            })
            ->delete();

        return $this->success();
    }

    public function destroy(DatabaseNotification $notification)
    {
        $this->checkPermission($notification);
        $notification->delete();

        return $this->success();
    }
}
