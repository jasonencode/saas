# 数据库

## PostgreSQL 全文搜索

### 概述

项目使用 PostgreSQL 的 `tsvector`/`tsquery` 实现全文搜索，替代 MySQL 的 `MATCH...AGAINST`。

已在 `contents` 和 `products` 表中添加了 `search_vector` 生成列和 GIN 索引，数据变更时自动同步。

### 索引结构

| 表 | 字段（权重） | 权重说明 |
|---|---|---|
| `contents` | `title`(A), `sub_title`(B), `description`(C), `content`(D) | A 最高，D 最低 |
| `products` | `name`(A), `description`(B) | A 最高，B 最低 |

使用 `'simple'` 配置（适合中文为主的内容，不做词干提取）。

### 搜索商品

```php
$products = Product::select('*')
    ->whereRaw('search_vector @@ plainto_tsquery(\'simple\', ?)', ['搜索关键词'])
    ->orderByRaw('ts_rank(search_vector, plainto_tsquery(\'simple\', ?)) DESC', ['搜索关键词'])
    ->get();
```

### 搜索文章

```php
$articles = Content::select('*')
    ->whereRaw('search_vector @@ plainto_tsquery(\'simple\', ?)', ['关键词'])
    ->orderByRaw('ts_rank(search_vector, plainto_tsquery(\'simple\', ?)) DESC', ['关键词'])
    ->get();
```

### 高级搜索

使用 `to_tsquery` 支持布尔运算符（`&` 与、`|` 或、`!` 非）：

```php
$products = Product::select('*')
    ->whereRaw('search_vector @@ to_tsquery(\'simple\', ?)', ['关键词1 & 关键词2'])
    ->orderByRaw('ts_rank(search_vector, to_tsquery(\'simple\', ?)) DESC', ['关键词1 & 关键词2'])
    ->get();
```

### 模型 Scope 封装建议

建议在模型中封装查询逻辑以便复用：

```php
// app/Models/Product.php
public function scopeWhereSearch($query, string $keyword)
{
    return $query->whereRaw(
        'search_vector @@ plainto_tsquery(\'simple\', ?)',
        [$keyword]
    );
}

public function scopeOrderBySearch($query, string $keyword)
{
    return $query->orderByRaw(
        'ts_rank(search_vector, plainto_tsquery(\'simple\', ?)) DESC',
        [$keyword]
    );
}

// 使用
$products = Product::whereSearch('关键词')->orderBySearch('关键词')->get();
```

### 扩展中文分词

如需更好的中文分词效果，可安装 `zhparser` 或 `jieba` 扩展，然后将 `'simple'` 替换为对应配置名即可。
