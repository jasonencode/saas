<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserRelation extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'user_id';

    protected $casts = [
        'layer' => 'int',
        'direct_count' => 'int',
        'team_count' => 'int',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * 获取所有上级
     *
     * @param  int|null  $maxLayer
     * @return Collection
     */
    public function getAncestors(?int $maxLayer = null): Collection
    {
        $pathIds = array_filter(
            explode('/', trim($this->path, '/')),
            static fn($id) => $id > 0
        );
        array_pop($pathIds);

        $query = User::join('user_relations', 'users.id', '=', 'user_relations.user_id')
            ->whereIn('users.id', $pathIds)
            ->select([
                'users.id', 'users.username', 'users.created_at',
                'user_relations.parent_id', 'user_relations.layer', 'user_relations.path',
            ]);

        if ($maxLayer !== null) {
            $minLayer = max(0, $this->layer - $maxLayer);
            $query->where('user_relations.layer', '>=', $minLayer);
        }

        return $query->orderBy('user_relations.layer', 'desc')->get()->map(function ($ancestor) {
            return [
                'user' => $ancestor,
                'layer_info' => [
                    'absolute_layer' => $ancestor->layer,
                    'relative_layer' => $this->layer - $ancestor->layer,
                    'is_direct_parent' => ($this->layer - $ancestor->layer) === 1,
                ],
            ];
        });
    }

    /**
     * 获取指定层级的上级
     *
     * @param  int  $layer
     * @return User|null
     */
    public function getAncestorAtLayer(int $layer): ?User
    {
        if ($layer <= 0) {
            return null;
        }
        $targetLayer = $this->layer - $layer;

        if ($targetLayer < 0) {
            return null;
        }
        $pathIds = array_filter(
            explode('/', trim($this->path, '/')),
            static fn($id) => $id > 0
        );
        array_pop($pathIds);

        return User::whereIn('id', static function ($query) use ($targetLayer) {
            $query->select('user_id')
                ->from('user_relations')
                ->where('layer', $targetLayer);
        })
            ->whereIn('id', $pathIds)
            ->first();
    }

    /**
     * 获取所有下级
     *
     * @param  int|null  $maxLayer
     * @return Collection
     */
    public function getDescendants(?int $maxLayer = null): Collection
    {
        $query = User::join('user_relations', 'users.id', '=', 'user_relations.user_id')
            ->where('user_relations.path', 'like', $this->path.'%')
            ->where('users.id', '!=', $this->user_id);

        if ($maxLayer) {
            $query->where('user_relations.layer', '<=', $this->layer + $maxLayer);
        }

        return $query->select([
            'users.id', 'users.username', 'users.created_at',
            'user_relations.parent_id', 'user_relations.layer', 'user_relations.path',
        ])
            ->orderBy('user_relations.layer')
            ->orderBy('users.id')
            ->get();
    }

    /**
     * 获取指定层级的下级
     *
     * @param  int  $targetLayer
     * @return Collection
     */
    public function getDescendantsAtLayer(int $targetLayer): Collection
    {
        return User::join('user_relations', 'users.id', '=', 'user_relations.user_id')
            ->where('user_relations.path', 'like', $this->path.'%')
            ->where('user_relations.layer', $this->layer + $targetLayer)
            ->select([
                'users.id', 'users.username', 'users.created_at',
                'user_relations.parent_id', 'user_relations.layer', 'user_relations.path',
            ])
            ->get();
    }

    /**
     * 检查是否为上级
     *
     * @param  int  $userId
     * @return bool
     */
    public function isAncestorOf(int $userId): bool
    {
        return static::where('user_id', $userId)
            ->where('path', 'like', '%/'.$this->user_id.'/%')
            ->exists();
    }

    /**
     * 检查是否为下级
     *
     * @param  int  $userId
     * @return bool
     */
    public function isDescendantOf(int $userId): bool
    {
        return str_contains($this->path, '/'.$userId.'/');
    }

    /**
     * 检查更新推荐人是否合法
     *
     * @param  int  $newParentId
     * @return bool
     */
    public function canUpdateParent(int $newParentId): bool
    {
        if ($newParentId === 0) {
            return true;
        }

        return $newParentId !== $this->user_id
            && !$this->isAncestorOf($newParentId)
            && User::where('id', $newParentId)->exists();
    }

    /**
     * 获取团队成员统计
     *
     * @return array
     */
    public function getTeamStats(): array
    {
        $baseLayer = $this->layer;

        return [
            'direct_count' => $this->direct_count,
            'team_count' => $this->team_count,
            'self_layer' => $this->layer,
            'layer_stats' => static::select([
                'layer',
                DB::raw('count(*) as count'),
                DB::raw("(layer - $baseLayer) as relative_layer"),
            ])
                ->where('path', 'like', $this->path.'%')
                ->where('user_id', '!=', $this->user_id)
                ->groupBy('layer')
                ->orderBy('layer')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->relative_layer => $item->count];
                })
                ->all(),
        ];
    }
}
