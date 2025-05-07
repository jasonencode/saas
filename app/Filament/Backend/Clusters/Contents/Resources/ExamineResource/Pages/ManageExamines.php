<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\ExamineResource\Pages;

use App\Contracts\ShouldExamine;
use App\Enums\ExamineState;
use App\Filament\Backend\Clusters\Contents\Resources\ExamineResource;
use App\Models\Examine;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageExamines extends ManageRecords
{
    protected static string $resource = ExamineResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('全部'),
            'pending' => Tab::make()
                ->label('待审核')
                ->badge(fn() => Examine::ofPending()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofPending()),
            'reject' => Tab::make()
                ->label('已驳回')
                ->badge(fn() => Examine::ofRejected()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofRejected()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                TextColumn::make('target')
                    ->label('审核对象')
                    ->getStateUsing(function(Examine $record) {
                        if ($record->target instanceof ShouldExamine) {
                            return $record->target?->getExamineTitleAttribute();
                        }

                        return '未找到对象';
                    }),
                TextColumn::make('state')
                    ->label('当前状态')
                    ->badge(),
                TextColumn::make('reviewer.username')
                    ->label('审核人')
                    ->searchable(),
                TextColumn::make('passed_at')
                    ->label('通过时间'),
                TextColumn::make('rejected_at')
                    ->label('驳回时间'),
                TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->actions([
                Action::make('audit')
                    ->label('审核')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->visible(fn(Examine $record): bool => userCan('examine', $record) && $record->isPending())
                    ->fillForm(function(Examine $record) {
                        return [
                            'target' => $record->target?->getExamineTitleAttribute(),
                            'pending_text' => $record->pending_text,
                            'state' => ExamineState::Approved,
                        ];
                    })
                    ->form([
                        TextInput::make('target')
                            ->label('审核对象')
                            ->disabled()
                            ->readOnly(),
                        Textarea::make('pending_text')
                            ->label('申请说明')
                            ->rows(4)
                            ->disabled()
                            ->readOnly(),
                        Radio::make('state')
                            ->label('审核结果')
                            ->live()
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->options(ExamineState::class)
                            ->disableOptionWhen(fn(string $value): bool => $value === ExamineState::Pending->value),
                        Textarea::make('text')
                            ->label(fn(Get $get) => $get('state') == ExamineState::Rejected ? '驳回原因' : '通过备注')
                            ->rows(4)
                            ->required(),
                        TextInput::make('password')
                            ->label('当前密码')
                            ->password()
                            ->required()
                            ->currentPassword(),
                    ])
                    ->action(function(array $data, Examine $record, Action $action) {
                        if ($data['state'] == ExamineState::Approved) {
                            $record->pass(auth()->user(), $data['text']);
                        } else {
                            $record->reject(auth()->user(), $data['text']);
                        }
                        $action->successNotificationTitle('审核成功');
                        $action->success();
                    }),
            ]);
    }
}
