<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class UserRelationService implements ServiceInterface
{
    /**
     * 创建用户关系
     *
     * @param  User  $user
     * @param  int  $parentId
     * @return bool
     * @throws Throwable
     */
    public function createRelation(User $user, int $parentId = 0): bool
    {
        if (UserRelation::where('user_id', $user->getKey())->exists()) {
            throw new InvalidArgumentException('用户关系已存在');
        }

        return DB::transaction(static function () use ($user, $parentId) {
            if ($parentId === $user->getKey()) {
                throw new InvalidArgumentException('不能将自己设为推荐人');
            }
            [$layer, $path] = self::resolveLayerAndPath($parentId);

            UserRelation::create([
                'user_id' => $user->getKey(),
                'parent_id' => $parentId,
                'layer' => $layer,
                'path' => $path.$user->getKey().'/',
            ]);

            self::updateAncestorCounts($user);

            return true;
        });
    }

    /**
     * 更新用户的推荐人
     *
     * @param  User  $user
     * @param  int  $newParentId
     * @return bool
     * @throws Throwable
     */
    public function updateParent(User $user, int $newParentId): bool
    {
        $relation = UserRelation::where('user_id', $user->id)->first();
        if (!$relation) {
            throw new InvalidArgumentException('用户关系不存在');
        }
        // 如果新旧推荐人相同，直接返回
        if ($relation->parent_id === $newParentId) {
            return true;
        }

        // 检查合法性
        if (!$relation->canUpdateParent($newParentId)) {
            throw new InvalidArgumentException('不能将自己或下级设为推荐人');
        }

        return DB::transaction(static function () use ($relation, $newParentId) {
            $oldParentId = $relation->parent_id;
            $oldPath = $relation->path;

            // 获取新路径
            [$newLayer, $newPath] = self::resolveLayerAndPath($newParentId);
            $newPath .= $relation->user_id.'/';

            // 计算层级差
            $layerDiff = $newLayer - $relation->layer;

            // 更新当前节点
            $relation->parent_id = $newParentId;
            $relation->layer = $newLayer;
            $relation->path = $newPath;
            $relation->save();

            // 更新所有下级节点
            UserRelation::where('path', 'like', $oldPath.'%')
                ->where('user_id', '!=', $relation->user_id)
                ->update([
                    'layer' => DB::raw("layer + $layerDiff"),
                    'path' => DB::raw("CONCAT('$newPath', SUBSTRING(path, ".(strlen($oldPath) + 1)."))"),
                ]);

            // 更新新旧上级的统计
            if ($oldParentId > 0) {
                $oldParent = User::find($oldParentId);
                if ($oldParent) {
                    static::updateAncestorCounts($oldParent);
                }
            }
            if ($newParentId > 0) {
                $newParent = User::find($newParentId);
                if ($newParent) {
                    static::updateAncestorCounts($newParent);
                }
            }

            return true;
        });
    }

    /**
     * 更新上级统计
     *
     * @param  User  $user
     * @return void
     */
    protected static function updateAncestorCounts(User $user): void
    {
        $relation = UserRelation::find($user->getKey());
        if (!$relation) {
            return;
        }

        // 获取所有上级ID
        $ancestorIds = trim($relation->path, '/')
                |> (static fn ($x) => explode('/', $x))
                |> (static fn ($x) => array_filter($x, static fn ($id) => $id > 0));

        foreach ($ancestorIds as $ancestorId) {
            $ancestor = UserRelation::find($ancestorId);
            if ($ancestor) {
                // 更新直推人数
                $ancestor->direct_count = UserRelation::where('parent_id', $ancestorId)->count();

                // 更新团队人数
                $ancestor->team_count = UserRelation::where('path', 'like', $ancestor->path.'%')
                    ->where('user_id', '!=', $ancestorId)
                    ->count();

                $ancestor->save();
            }
        }
    }

    /**
     * 解析层级和路径
     *
     * @param  int  $parentId
     * @return array
     * @throws InvalidArgumentException
     */
    protected static function resolveLayerAndPath(int $parentId): array
    {
        if ($parentId > 0) {
            $parent = UserRelation::find($parentId);
            if (!$parent) {
                throw new InvalidArgumentException('推荐人不存在');
            }

            return [$parent->layer + 1, $parent->path];
        }

        return [0, '/'];
    }
}
