<?php

namespace App\Filament\Tenant\Clusters\User\Resources\UserRealnames\Schemas;

use App\Models\User\UserRealname;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;

class UserRealnameInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('认证信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('user.username')
                            ->label('用户名')
                            ->icon(Heroicon::OutlinedUser)
                            ->copyable(),
                        Infolists\Components\TextEntry::make('name')
                            ->label('真实姓名/企业名称'),
                        Infolists\Components\TextEntry::make('type')
                            ->label('认证类型')
                            ->badge(),
                        Infolists\Components\TextEntry::make('status')
                            ->label('认证状态')
                            ->badge(),
                        Infolists\Components\TextEntry::make('verified_at')
                            ->label('认证时间')
                            ->dateTime()
                            ->placeholder('未认证'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('申请时间')
                            ->dateTime()
                            ->icon(Heroicon::OutlinedCalendar),
                    ]),
                Schemas\Components\Fieldset::make('个人认证资料')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('id_card_number')
                            ->label('身份证号')
                            ->getStateUsing(fn (UserRealname $record): ?string => $record->decryptedIdCardNumber())
                            ->copyable(),
                        Infolists\Components\ImageEntry::make('id_card_front')
                            ->label('身份证正面')
                            ->disk(config('filesystems.private'))
                            ->visibility('private')
                            ->action(
                                MediaAction::make('id_card_front')
                                    ->label('身份证正面')
                                    ->modalWidth(Width::Large)
                                    ->visible(fn (UserRealname $record) => $record->id_card_front)
                                    ->media(fn (UserRealname $record) => temporary_file_url($record->id_card_front))
                            ),
                        Infolists\Components\ImageEntry::make('id_card_back')
                            ->label('身份证背面')
                            ->disk(config('filesystems.private'))
                            ->visibility('private')
                            ->action(
                                MediaAction::make('id_card_back')
                                    ->label('身份证背面')
                                    ->modalWidth(Width::Large)
                                    ->visible(fn (UserRealname $record) => $record->id_card_back)
                                    ->media(fn (UserRealname $record) => temporary_file_url($record->id_card_back))
                            ),
                    ])
                    ->visible(fn ($record): bool => ($record->type ?? null)?->value === 'personal'),
                Schemas\Components\Fieldset::make('企业认证资料')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('contact_person')
                            ->label('联系人'),
                        Infolists\Components\TextEntry::make('contact_phone')
                            ->label('联系电话')
                            ->copyable(),
                        Infolists\Components\ImageEntry::make('business_license')
                            ->label('营业执照')
                            ->disk(config('filesystems.private'))
                            ->visibility('private')
                            ->action(
                                MediaAction::make('business_license')
                                    ->label('营业执照')
                                    ->modalWidth(Width::Large)
                                    ->visible(fn (UserRealname $record) => $record->business_license)
                                    ->media(fn (UserRealname $record) => temporary_file_url($record->business_license))
                            ),
                    ])
                    ->visible(fn ($record): bool => ($record->type ?? null)?->value === 'enterprise'),
                Schemas\Components\Fieldset::make('审核结果')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('reject_reason')
                            ->label('拒绝原因')
                            ->color('danger'),
                    ])
                    ->visible(fn ($record): bool => ($record->status ?? null)?->value === 'rejected'),
            ]);
    }
}
