<?php

namespace App\Filament\Actions\User;

use App\Filament\Forms\Components\UserSelect;
use App\Models\User\User;
use App\Models\User\UserRelation;
use App\Services\User\UserRelationService;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

class UpdateParentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'updateParent';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('修改上级');
        $this->icon(Heroicon::OutlinedShare);
        $this->modalWidth(Width::Medium);

        $this->visible(fn (User $record): bool => userCan(self::getDefaultName(), $record));

        $this->fillForm(fn (User $record): array => [
            'parent_id' => UserRelation::where('user_id', $record->id)->value('parent_id'),
        ]);

        $this->schema([
            UserSelect::make('parent_id')
                ->label('上级用户')
                ->placeholder('顶级用户（无上级）'),
        ]);

        $this->action(function (User $record, array $data): void {
            /** @var UserRelationService $userRelationService */
            $userRelationService = app(UserRelationService::class);

            $parentId = filled($data['parent_id']) ? (int) $data['parent_id'] : null;

            try {
                $userRelationService->updateParent($record, $parentId);

                $this->successNotificationTitle('上级修改成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('上级修改失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
