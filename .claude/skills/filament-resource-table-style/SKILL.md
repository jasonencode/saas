# Filament Resource Table 代码风格规范

适用于 Filament Resource 的 Table 配置文件（如 `OrdersTable.php`、`UsersTable.php` 等）。

## 适用场景

* 创建或修改 Resource 的 Table 配置文件
* 统一表格列、筛选器、操作等定义风格
* 优化代码可读性

## 文件结构

```php
<?php

namespace App\Filament\Backend\Clusters\{Cluster}\Resources\{Resource}\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class {Resource}sTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->searchPlaceholder('搜索提示...')
            ->columns([...])
            ->filters([...])
            ->recordActions([
                Actions\ActionGroup::make([
                    // actions...
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    // bulk actions...
                ]),
            ]);
    }
}
```

## 方法排序规范

在 `configure()` 方法中，按以下顺序调用方法：

### 1. defaultSort（默认排序）

```php
->defaultSort('id', 'desc')
// 或
->defaultSort('created_at', 'desc')
```

### 2. modifyQuery（查询修改，如有）

```php
->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
```

### 3. searchPlaceholder（搜索提示）

```php
->searchPlaceholder('UID / 手机号码 / 昵称')
```

### 4. poll（轮询刷新）

```php
->poll('60s')
```

### 5. columns（列定义）

```php
->columns([
    Tables\Columns\TextColumn::make('user.name')
        ->label('用户'),
    
    Tables\Columns\TextColumn::make('no')
        ->label('编号')
        ->searchable()
        ->copyable(),
    
    Tables\Columns\TextColumn::make('amount')
        ->label('金额')
        ->money('CNY')
        ->sortable(),
    
    Tables\Columns\TextColumn::make('status')
        ->label('状态')
        ->badge()
        ->color(fn (StatusEnum $state): string => match ($state) {
            StatusEnum::ACTIVE => 'success',
            StatusEnum::INACTIVE => 'danger',
            default => 'gray',
        })
        ->formatStateUsing(fn (StatusEnum $state): string => $state->toString()),
    
    Tables\Columns\TextColumn::make('created_at')
        ->label('创建时间')
        ->sortable()
        ->toggleable(isToggledHiddenByDefault: true),
])
```

### 6. filters（筛选器）

```php
->filters([
    Tables\Filters\SelectFilter::make('status')
        ->label('状态')
        ->options(StatusEnum::class),
    
    Tables\Filters\SelectFilter::make('store_id')
        ->label('店铺')
        ->relationship('store', 'name')
        ->searchable()
        ->preload(),
    
    DateRangeFilter::make('created_at')
        ->label('创建时间'),
    
    Tables\Filters\Filter::make('is_active')
        ->label('仅有效')
        ->query(fn (Builder $query) => $query->where('status', 'active')),
    
    Tables\Filters\TrashedFilter::make(),
])
```

### 7. recordActions（行操作）

**必须使用 `ActionGroup` 包裹（即使只有一个 action）。**

```php
->recordActions([
    Actions\ActionGroup::make([
        Actions\ViewAction::make(),
        Actions\EditAction::make(),
        // 自定义 Action
        CustomAction::make(),
    ]),
])
```

### 8. toolbarActions（工具栏操作）

```php
->toolbarActions([
    Actions\BulkActionGroup::make([
        Actions\DeleteBulkAction::make(),
        // 自定义 BulkAction
        CustomBulkAction::make(),
    ]),
])
```

## 列定义规范

### TextColumn

```php
Tables\Columns\TextColumn::make('field')
    ->label('标签')
    ->searchable()
    ->sortable()
    ->copyable()
    ->limit(30)
    ->toggleable(isToggledHiddenByDefault: true)
    ->money('CNY')
    ->numeric()
    ->badge()
    ->color(fn ($state) => 'primary')
    ->formatStateUsing(fn ($state) => $state->toString())
    ->description(fn ($record): string => $record->desc)
    ->counts('relation')
    ->sum('relation', 'field')
    ->url(fn ($record) => route('...'))
```

### ImageColumn

```php
Tables\Columns\ImageColumn::make('cover')
    ->label('封面')
    ->circular()
    ->square()
    ->imageSize(60)
    ->disk('public')
```

### IconColumn

```php
Tables\Columns\IconColumn::make('is_active')
    ->label('状态')
    ->boolean()
    ->trueIcon('heroicon-o-check-circle')
    ->falseIcon('heroicon-o-x-circle')
    ->trueColor('success')
    ->falseColor('danger')
```

## 筛选器规范

### SelectFilter

```php
Tables\Filters\SelectFilter::make('status')
    ->label('状态')
    ->options(StatusEnum::class),

Tables\Filters\SelectFilter::make('store_id')
    ->label('店铺')
    ->relationship('store', 'name')
    ->searchable()
    ->preload(),

Tables\Filters\SelectFilter::make('type')
    ->label('类型')
    ->options([
        'type1' => '类型1',
        'type2' => '类型2',
    ]),
```

### 自定义 Filter

```php
Tables\Filters\Filter::make('is_active')
    ->label('仅有效')
    ->toggle()
    ->query(fn (Builder $query) => $query->where('status', 'active')),
```

### DateRangeFilter

```php
DateRangeFilter::make('created_at')
    ->label('创建时间'),
```

### TrashedFilter

```php
Tables\Filters\TrashedFilter::make(),
```

## Import 规范

```php
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

use App\Filament\Tables\Components\UserInfoColumn;

use Modules\Mall\Enums\OrderStatus;

use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
```

## 完整示例

```php
<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Orders\Tables;

use App\Filament\Tables\Components\UserInfoColumn;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Mall\Enums\OrderStatus;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('订单编号 / 店铺名称')
            ->columns([
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('no')
                    ->label('订单编号')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('store.name')
                    ->label('店铺'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('商品总额')
                    ->money('CNY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::PENDING => 'warning',
                        OrderStatus::PAID => 'success',
                        OrderStatus::CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->toString()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('下单时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('订单状态')
                    ->options(OrderStatus::class),
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('店铺')
                    ->relationship('store', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make(),
                    Actions\EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

## 常用方法速查

| 方法 | 说明 |
|------|------|
| `->label('标签')` | 设置显示标签 |
| `->searchable()` | 可搜索 |
| `->sortable()` | 可排序 |
| `->copyable()` | 可复制 |
| `->limit(30)` | 限制文本长度 |
| `->toggleable(isToggledHiddenByDefault: true)` | 可切换显示（默认隐藏） |
| `->money('CNY')` | 金额格式 |
| `->numeric()` | 数字格式 |
| `->badge()` | 徽章样式 |
| `->boolean()` | 布尔值显示 |
| `->circular()` | 圆形图片 |
| `->square()` | 正方形图片 |
| `->imageSize(60)` | 图片大小 |
| `->counts('relation')` | 关联计数 |
| `->sum('relation', 'field')` | 关联求和 |
| `->description(fn ($record) => ...)` | 描述文本 |
| `->formatStateUsing(fn ($state) => ...)` | 格式化显示 |
| `->color(fn ($state) => ...)` | 颜色 |
| `->options(Enum::class)` | 枚举选项 |
| `->relationship('relation', 'name')` | 关系筛选 |
| `->searchable()` | 筛选器可搜索 |
| `->preload()` | 预加载选项 |
