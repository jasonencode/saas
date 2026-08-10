<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Vouchers\Schemas;

use App\Filament\Forms\Components\TenantSelect;
use App\Models\Finance\Plan;
use App\Models\Mall\Order;
use App\Models\User\User;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class VoucherForm
{
    /**
     * 可选的结算目标模型
     *
     * @var array<string, string>
     */
    protected static array $targetModels = [
        Order::class => '商城订单',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make()
                    ->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set) => $set('user_id', null)),
                Forms\Components\Select::make('plan_id')
                    ->label('结算计划')
                    ->options(fn (Get $get): array => Plan::query()
                        ->ofEnabled()
                        ->where('tenant_id', $get('tenant_id'))
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->label('结算用户')
                    ->options(fn (Get $get): array => User::query()
                        ->whereHas('tenants', fn (Builder $query) => $query->where('tenants.id', $get('tenant_id')))
                        ->pluck('username', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('target_type')
                    ->label('结算对象类型')
                    ->options(static::$targetModels)
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('target_id')
                    ->label('结算对象')
                    ->options(fn (Get $get): array => self::getTargetOptions($get('target_type'), $get('tenant_id')))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label('计划执行时间')
                    ->helperText('不设置则立即执行')
                    ->columnSpanFull()
                    ->suffixIcon(Heroicon::OutlinedCalculator),
            ]);
    }

    /**
     * 获取结算目标选项
     *
     * @return array<int, string>
     */
    protected static function getTargetOptions(?string $targetType, ?int $tenantId): array
    {
        if (!$targetType || !class_exists($targetType)) {
            return [];
        }

        $instance = new $targetType;
        $query = $targetType::query();

        if (in_array('tenant_id', $instance->getConnection()->getSchemaBuilder()->getColumnListing($instance->getTable()), true)) {
            $query->where('tenant_id', $tenantId);
        }

        if (method_exists($targetType, 'user')) {
            $query->with('user');
        }

        return $query->get()
            ->mapWithKeys(fn ($item) => [
                $item->getKey() => self::getTargetLabel($item),
            ])
            ->toArray();
    }

    /**
     * 获取结算目标显示标签
     */
    protected static function getTargetLabel(object $model): string
    {
        if (method_exists($model, 'getSettlementTitleAttribute')) {
            return $model->settlement_title;
        }

        if (isset($model->no)) {
            return $model->no;
        }

        if (isset($model->name)) {
            return $model->name;
        }

        return '#'.$model->getKey();
    }
}
