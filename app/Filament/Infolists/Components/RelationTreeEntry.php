<?php

namespace App\Filament\Infolists\Components;

use App\Models\User\UserProfile;
use App\Models\User\UserRelation;
use Filament\Infolists\Components\Entry;

class RelationTreeEntry extends Entry
{
    protected string $view = 'filament.infolists.relation-tree';

    /**
     * 递归构建推荐关系树（直接上级 + 所有下级）
     *
     * @return array{parent: array|null, root: array, children: array}
     */
    public function getNodes(): array
    {
        $record = $this->getRecord();

        if (!$record instanceof UserRelation) {
            return [
                'parent' => null,
                'root' => null,
                'children' => [],
            ];
        }

        $descendants = $record->getDescendants();
        $parent = $record->parent;

        $labelMap = UserProfile::query()
            ->whereIn('user_id', collect([$record->user_id, $parent?->getKey()])
                ->merge($descendants->pluck('id'))
                ->filter()
                ->unique())
            ->pluck('nickname', 'user_id')
            ->all();

        $label = static function (int $userId) use ($labelMap): ?string {
            return $labelMap[$userId] ?? null;
        };

        $childrenByParent = [];
        foreach ($descendants as $descendant) {
            $childrenByParent[$descendant->parent_id][] = [
                'user' => $descendant,
                'children' => [],
            ];
        }

        $build = static function (int $userId) use (&$build, $childrenByParent): array {
            $nodes = $childrenByParent[$userId] ?? [];

            foreach ($nodes as &$node) {
                $node['children'] = $build($node['user']->getKey());
            }
            unset($node);

            return $nodes;
        };

        return [
            'parent' => $parent ? [
                'id' => $parent->getKey(),
                'label' => $parent->username.($label($parent->getKey()) ? '（'.$label($parent->getKey()).'）' : ''),
            ] : null,
            'root' => [
                'id' => $record->user_id,
                'label' => $record->user?->username.($label($record->user_id) ? '（'.$label($record->user_id).'）' : ''),
            ],
            'children' => $build((int) $record->user_id),
        ];
    }
}
