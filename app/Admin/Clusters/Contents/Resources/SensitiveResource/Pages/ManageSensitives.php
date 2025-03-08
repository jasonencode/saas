<?php

namespace App\Admin\Clusters\Contents\Resources\SensitiveResource\Pages;

use App\Admin\Clusters\Contents\Resources\SensitiveResource;
use App\Models\Sensitive;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ManageSensitives extends ManageRecords
{
    protected static string $resource = SensitiveResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('keywords')
                    ->label('敏感词'),
                TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('batchCreate')
                ->label('批量创建')
                ->form([
                    Textarea::make('words')
                        ->label('敏感词')
                        ->rows(8)
                        ->helperText('每行一个词，如果有重复的，会自动过滤')
                        ->required(),
                ])
                ->action(function(array $data, Action $action) {
                    $list = explode("\n", $data['words']);
                    $list = array_unique($list);

                    foreach ($list as $word) {
                        Sensitive::create(['keywords' => $word]);
                    }
                    $action->successNotificationTitle('操作成功');
                    $action->success();
                }),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('keywords')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull()
                    ->label('敏感词'),
            ]);
    }
}
