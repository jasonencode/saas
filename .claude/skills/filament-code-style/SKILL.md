---
name: filament-code-style
description: Filament/PHP代码格式化规则，包括链式调用换行、参数省略默认值、schema/components区分等。
origin: USER
---

# Filament 代码格式化规则

适用于 Filament 表单、表格、Infolist 等 Schema 组件的代码格式化。

## 适用场景

* 格式化 Filament 表单/Infolist/表格定义代码
* 统一链式调用风格
* 优化代码可读性

## 规则

### 1. 链式调用每个 `->` 独立成行

每个方法调用换行，保持一致的缩进层级。

**正确：**
```php
TextEntry::make('name')
    ->label('名称')
    ->numeric(),
```

**错误：**
```php
TextEntry::make('name')->label('名称')->numeric(),
```

### 2. 省略与默认值匹配的参数

当参数值等于默认值时，省略该参数。

**正确：**
```php
Grid::make()
    ->schema([...]),
```

**错误：**
```php
Grid::make(1)
    ->schema([...]),
```

### 3. 顶层用 `components()`，布局组件用 `schema()`

* 顶层 `Schema` 对象使用 `->components([...])`
* 布局组件（`Grid`、`Section`、`Tabs`、`Fieldset` 等）使用 `->schema([...])`

```php
return $schema
    ->components([
        Grid::make()
            ->schema([
                Section::make('信息')
                    ->schema([
                        TextEntry::make('name'),
                    ]),
            ]),
    ]);
```

### 4. 闭包体保持单行

短闭包保持单行，超过 80 字符才换行。

**正确：**
```php
->state(fn ($record) => $record->items()->count()),
```

**过长时换行：**
```php
->state(fn ($record) => $record->items()
    ->where('status', CardItem::STATUS_USED)
    ->count()),
```

## 完整示例

```php
return $schema
    ->components([
        Grid::make()
            ->schema([
                Section::make('批次信息')
                    ->schema([
                        TextEntry::make('name')
                            ->label('批次名称'),
                        TextEntry::make('def_amount')
                            ->label('默认充值金额')
                            ->numeric(),
                        IconEntry::make('status')
                            ->label('状态')
                            ->boolean(),
                    ]),
                Section::make('卡号设置')
                    ->schema([
                        TextEntry::make('no_size')
                            ->label('卡号长度'),
                        TextEntry::make('no_prfix')
                            ->label('卡号前缀'),
                    ]),
            ]),
        Section::make('统计信息')
            ->schema([
                Grid::make(4)
                    ->schema([
                        TextEntry::make('items_count')
                            ->label('总数量')
                            ->state(fn ($record) => $record->items()->count()),
                    ]),
            ]),
    ]);
```
