<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Addresses\Schemas;

use App\Filament\Forms\Components\AddressSelect;
use App\Filament\Forms\Components\UserSelect;
use App\Models\User\User;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AddressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                UserSelect::make()
                    ->label('选择用户')
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                        if (!$state) {
                            $set('name', null);
                            $set('mobile', null);

                            return;
                        }

                        $user = User::with('profile')->find($state);
                        $set('name', $user->profile->nickname);
                        $set('mobile', $user->username);
                    })
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('联系人')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('mobile')
                    ->label('手机号')
                    ->required()
                    ->maxLength(20),
                AddressSelect::make(),
                Forms\Components\Toggle::make('is_default')
                    ->label('设为默认')
                    ->default(false),
            ]);
    }
}
