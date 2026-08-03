# Searchable Trait 使用说明

> 模型搜索功能封装，自动适配 MySQL/PostgreSQL，支持模糊搜索和全文索引搜索。

## 引用

```php
use App\Models\Traits\Searchable;
use App\Enums\Foundation\SearchLanguage;
```

## 基础用法

### 在模型中引入 Trait

```php
<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\Searchable;

class Product extends Model
{
    use Searchable;
}
```

### 单字段模糊搜索

```php
// 自动适配数据库：PostgreSQL 使用 ILIKE，MySQL 使用 LIKE
Product::search('name', '手机')->paginate();

// 等价于 SQL:
// PostgreSQL: WHERE name ILIKE '%手机%'
// MySQL: WHERE name LIKE '%手机%'
```

### 多字段模糊搜索

```php
// 搜索名称或描述中包含关键词的商品
Product::searchFields(['name', 'description'], '手机')->paginate();

// 等价于 SQL:
// WHERE (name ILIKE '%手机%' OR description ILIKE '%手机%')
```

---

## 全文索引搜索

全文索引比模糊搜索性能更好，适合大数据量场景，但需要数据库预先创建全文索引。

### 基础全文搜索

```php
// 单字段全文搜索
Product::fullTextSearch('name', '手机')->paginate();

// 多字段全文搜索（MySQL 支持多字段，PostgreSQL 也支持）
Product::fullTextSearch(['name', 'description'], '手机')->paginate();
```

### 全文搜索 + 相关性排序

```php
// 搜索结果按相关性从高到低排序
Product::fullTextSearchWithRanking(['name', 'description'], '智能手机')->paginate();
```

### 指定语言（PostgreSQL）

```php
use App\Enums\Foundation\SearchLanguage;

// 英文全文搜索（支持词干提取：running 匹配 run）
Product::fullTextSearch('content', 'running', SearchLanguage::English)->paginate();

// 中文全文搜索（需安装 zhparser 扩展）
Product::fullTextSearch('content', '关键词', SearchLanguage::Chinese)->paginate();

// 默认使用 simple（不分词，按字符分割）
Product::fullTextSearch('content', '关键词')->paginate();
```

---

## SearchLanguage 枚举

| 枚举值 | 说明 |
|--------|------|
| `SearchLanguage::Simple` | 不分词，按空白和标点分割（默认） |
| `SearchLanguage::English` | 英文（支持词干提取） |
| `SearchLanguage::Chinese` | 中文（需安装 zhparser 扩展） |

---

## 方法列表

| 方法 | 参数 | 说明 |
|------|------|------|
| `scopeSearch` | `$field`, `$keyword` | 单字段模糊搜索 |
| `scopeSearchFields` | `$fields`, `$keyword` | 多字段模糊搜索（OR 条件） |
| `scopeFullTextSearch` | `$fields`, `$keyword`, `$language = SearchLanguage::Simple` | 全文索引搜索 |
| `scopeFullTextSearchWithRanking` | `$fields`, `$keyword`, `$language = SearchLanguage::Simple` | 全文搜索 + 相关性排序 |

---

## 数据库适配

| 功能 | MySQL | PostgreSQL |
|------|-------|------------|
| 模糊搜索 | `LIKE`（默认不区分大小写） | `ILIKE`（不区分大小写） |
| 全文搜索 | `MATCH AGAINST` | `to_tsvector @@ to_tsquery` |
| 全文排序 | `MATCH AGAINST ... DESC` | `ts_rank() DESC` |

---

## PostgreSQL 中文全文索引配置

PostgreSQL 默认不支持中文分词，需要安装 `zhparser` 扩展：

```sql
-- 1. 安装扩展
CREATE EXTENSION IF NOT EXISTS zhparser;

-- 2. 创建中文搜索配置
CREATE TEXT SEARCH CONFIGURATION chinese (PARSER = zhparser);

-- 3. 添加词性映射（名词、动词、形容词等）
ALTER TEXT SEARCH CONFIGURATION chinese ADD MAPPING FOR n,v,a,i,e,l WITH simple;

-- 4. 测试配置
SELECT * FROM ts_debug('chinese', '这是一个测试');
```

配置完成后使用：

```php
use App\Enums\Foundation\SearchLanguage;

Product::fullTextSearch('content', '关键词', SearchLanguage::Chinese)->paginate();
```

---

## 实际应用示例

### 商品搜索（推荐）

```php
// 简单模糊搜索，适用于商品名等短字段
$products = Product::ofUp()
    ->search('name', $request->input('name'))
    ->paginate();
```

### 订单搜索（多字段）

```php
$orders = Order::ofUser(auth()->user())
    ->searchFields(['no', 'remark'], $keyword)
    ->paginate();
```

### 文章搜索（全文索引）

```php
// 需要数据库已创建全文索引
$articles = Article::query()
    ->fullTextSearchWithRanking(['title', 'content'], $keyword)
    ->paginate();
```

---

## 性能建议

| 场景 | 推荐方案 | 原因 |
|------|----------|------|
| 短字段搜索（名称、编号） | `search()` | 简单高效，配合索引 |
| 多字段模糊搜索 | `searchFields()` | 便于维护 |
| 大文本搜索（文章、描述） | `fullTextSearch()` | 性能优于 LIKE |
| 搜索结果需要排序 | `fullTextSearchWithRanking()` | 相关性高的排前面 |
