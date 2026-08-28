# 数据库

## 基础约定

- 主数据库：PostgreSQL（`DB_CONNECTION=pgsql`），同时兼容 MySQL
- 基础模型：`App\Models\Model`，全局 `#[Unguarded]`，自带 `HasFactory`
- 迁移快捷方法（`AppServiceProvider` 中注册的 `Blueprint` 宏）：

| 宏 | 说明 |
|----|------|
| `tenant()` | `tenant_id` 外键（`tenants` 表） |
| `user()` | `user_id` 外键（`users` 表） |
| `no()` | 业务编号字段（订单号、退款号等） |
| `cover()` / `pictures()` | 封面图 / 图片集字段 |
| `easyStatus()` | 启停状态字段（配合 `HasEasyStatus` trait） |
| `sort()` | 排序字段（配合 `HasSortable` trait） |
| `regionAddress()` | 省市区字段组 |

## 模型 Trait

常用 Trait 位于 `app/Models/Traits/` 目录：

| Trait | 说明 |
|-------|------|
| `BelongsToTenant` | 多租户支持：`tenant()` 关联 + `ofTenant()` 作用域 |
| `BelongsToUser` | 关联用户 |
| `Searchable` | 模糊 / 全文搜索（自动适配 pgsql / mysql） |
| `HasComments` | 支持评论 |
| `HasCovers` | 支持封面图 |
| `HasSortable` | 支持排序（`bySort()` 作用域） |
| `HasEasyStatus` | 启停状态管理（`enable()` / `disable()`） |
| `HasRegion` | 地区关联 |
| `AutoCreateOrderNo` | 自动生成订单号 |
| `OrderScopes` | 订单查询作用域 |
| `ProductScopes` | 商品查询作用域 |
| `RefundScopes` | 退款查询作用域 |
| `MorphToUser` | 用户多态关联 |
| `BelongsToOrder` | 关联订单 |
| `BelongsToRefund` | 关联退款 |

## Searchable Trait

`App\Models\Traits\Searchable` 封装了模糊搜索与全文搜索，按数据库驱动自动适配：

```php
use App\Models\Traits\Searchable;
use App\Enums\Foundation\SearchLanguage;

class Product extends Model
{
    use Searchable;
}
```

### 模糊搜索

```php
// 单字段（pgsql 自动用 ILIKE，mysql 用 LIKE）
Product::search('name', '手机')->paginate();

// 多字段 OR 条件
Product::searchFields(['name', 'description'], '手机')->paginate();
```

### 全文索引搜索

```php
// 单字段 / 多字段全文搜索
Product::fullTextSearch('name', '手机')->paginate();
Product::fullTextSearch(['name', 'description'], '手机')->paginate();

// 全文搜索 + 相关性排序
Product::fullTextSearchWithRanking(['name', 'description'], '智能手机')->paginate();
```

### SearchLanguage 枚举

| 枚举值 | 说明 |
|--------|------|
| `SearchLanguage::Simple` | 不分词，按空白和标点分割（默认，适合中文为主的短字段） |
| `SearchLanguage::English` | 英文（支持词干提取） |
| `SearchLanguage::Chinese` | 中文分词（PostgreSQL 需安装 zhparser 扩展） |

> 全文搜索需要数据库预先创建全文索引（`fullTextSearch` 对应 pgsql 的 `to_tsvector @@ to_tsquery`、mysql 的 `MATCH AGAINST`）。

### 数据库适配

| 功能 | MySQL | PostgreSQL |
|------|-------|------------|
| 模糊搜索 | `LIKE`（默认不区分大小写） | `ILIKE`（不区分大小写） |
| 全文搜索 | `MATCH AGAINST` | `to_tsvector @@ to_tsquery` |
| 全文排序 | `MATCH AGAINST ... DESC` | `ts_rank() DESC` |

### PostgreSQL 中文全文索引配置

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
Product::fullTextSearch('content', '关键词', SearchLanguage::Chinese)->paginate();
```

## 性能建议

| 场景 | 推荐方案 | 原因 |
|------|----------|------|
| 短字段搜索（名称、编号） | `search()` | 简单高效，配合索引 |
| 多字段模糊搜索 | `searchFields()` | 便于维护 |
| 大文本搜索（文章、描述） | `fullTextSearch()` | 性能优于 LIKE |
| 搜索结果需要排序 | `fullTextSearchWithRanking()` | 相关性高的排前面 |

## 实际示例

```php
// 商品搜索：只看上架商品，按名称模糊匹配
$products = Product::ofUp()
    ->search('name', $request->input('name'))
    ->paginate();

// 订单搜索：订单号或备注
$orders = Order::ofUser(auth()->user())
    ->searchFields(['no', 'remark'], $keyword)
    ->paginate();

// 文章搜索（全文索引 + 相关性排序）
$articles = Content::query()
    ->fullTextSearchWithRanking(['title', 'content'], $keyword)
    ->paginate();
```
