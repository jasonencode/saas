<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\Schemas;

use App\Enums\Campaign\CouponType;
use App\Enums\Campaign\ExpiredType;
use App\Models\Campaign\Coupon;
use Filament\Infolists;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class CouponInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns()
            ->components([
                Fieldset::make('基础信息')
                    ->columns(3)
                    ->components([
                        Infolists\Components\TextEntry::make('name')
                            ->label('优惠券名称'),
                        Infolists\Components\TextEntry::make('code')
                            ->label('优惠券代码')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('type')
                            ->label('优惠券类型')
                            ->badge(),
                        Infolists\Components\TextEntry::make('description')
                            ->label('优惠券描述')
                            ->columnSpanFull()
                            ->placeholder('无描述'),
                    ]),
                Fieldset::make('折扣信息')
                    ->columns(3)
                    ->components([
                        Infolists\Components\TextEntry::make('value')
                            ->label('折扣值')
                            ->state(fn (Coupon $record): string => $record->type === CouponType::Percent
                                ? $record->value.'%'
                                : Number::currency($record->value, 'cny', config('app.locale'))),
                        Infolists\Components\TextEntry::make('min_amount')
                            ->label('最低消费金额')
                            ->money('cny'),
                        Infolists\Components\TextEntry::make('max_discount')
                            ->label('最大折扣金额')
                            ->visible(fn (Coupon $record) => $record->type === CouponType::Percent)
                            ->money('cny'),
                        Infolists\Components\TextEntry::make('usage_limit')
                            ->label('发放数量'),
                        Infolists\Components\TextEntry::make('usage_limit_per_user')
                            ->label('每人限领数量'),
                    ]),
                Fieldset::make('有效期信息')
                    ->columns(3)
                    ->components([
                        Infolists\Components\TextEntry::make('expired_type')
                            ->label('过期方式')
                            ->badge(),
                        Infolists\Components\TextEntry::make('days')
                            ->label('有效时长')
                            ->visible(fn (Coupon $record) => $record->expired_type === ExpiredType::Receive)
                            ->suffix('天'),
                        Infolists\Components\TextEntry::make('start_at')
                            ->label('开始时间')
                            ->visible(fn (Coupon $record) => $record->expired_type === ExpiredType::Fixed),
                        Infolists\Components\TextEntry::make('end_at')
                            ->label('结束时间')
                            ->visible(fn (Coupon $record) => $record->expired_type === ExpiredType::Fixed),
                    ]),
                Fieldset::make('状态与时间')
                    ->columns(3)
                    ->components([
                        Infolists\Components\IconEntry::make('status')
                            ->label(__('backend.status'))
                            ->boolean(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('backend.created_at')),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('backend.updated_at')),
                    ]),
            ]);
    }
}
