<?php

namespace App\Models\Traits;

use App\Enums\Foundation\SearchLanguage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait Searchable
{
    /**
     * 获取当前数据库驱动
     */
    protected static function getDatabaseDriver(): string
    {
        return DB::getDriverName();
    }

    /**
     * 判断是否为 PostgreSQL
     */
    protected static function isPostgres(): bool
    {
        return static::getDatabaseDriver() === 'pgsql';
    }

    /**
     * 获取模糊搜索操作符
     *
     * - PostgreSQL: ILIKE（不区分大小写）
     * - MySQL: LIKE（utf8mb4_unicode_ci 默认不区分大小写）
     */
    protected static function getLikeOperator(): string
    {
        return static::isPostgres() ? 'ILIKE' : 'LIKE';
    }

    /**
     * 单字段模糊搜索（自动适配数据库驱动）
     *
     * @param  string  $field  搜索字段
     * @param  string  $keyword  搜索关键词
     */
    public function scopeSearch(Builder $query, string $field, string $keyword): Builder
    {
        return $query->where($field, static::getLikeOperator(), "%$keyword%");
    }

    /**
     * 多字段模糊搜索（OR条件）
     *
     * @param  array<string>  $fields  搜索字段列表
     * @param  string  $keyword  搜索关键词
     */
    public function scopeSearchFields(Builder $query, array $fields, string $keyword): Builder
    {
        $operator = static::getLikeOperator();

        return $query->where(function (Builder $q) use ($fields, $keyword, $operator) {
            foreach ($fields as $field) {
                $q->orWhere($field, $operator, "%$keyword%");
            }
        });
    }

    /**
     * 全文索引搜索
     *
     * @param  string|array<string>  $fields  搜索字段（MySQL 支持多字段，PostgreSQL 也支持）
     * @param  string  $keyword  搜索关键词
     */
    public function scopeFullTextSearch(
        Builder $query,
        string|array $fields,
        string $keyword,
        SearchLanguage $language = SearchLanguage::Simple
    ): Builder {
        $fields = (array) $fields;

        if (static::isPostgres()) {
            return $query->whereFullText($fields, $keyword, [
                'language' => $language->value,
            ]);
        }

        // MySQL: 使用 MATCH AGAINST
        return $query->whereFullText($fields, $keyword, [
            'mode' => 'boolean',
        ]);
    }

    /**
     * 全文索引搜索（带相关性排序）
     *
     * @param  string|array<string>  $fields  搜索字段
     * @param  string  $keyword  搜索关键词
     */
    public function scopeFullTextSearchWithRanking(
        Builder $query,
        string|array $fields,
        string $keyword,
        SearchLanguage $language = SearchLanguage::Simple
    ): Builder {
        $fields = (array) $fields;

        if (static::isPostgres()) {
            $lang = $language->value;
            $tsQuery = "to_tsquery('$lang', ?)";

            $query->whereRaw(
                "to_tsvector('$lang', ".implode(" || ' ' || ", $fields).") @@ $tsQuery",
                [static::buildTsQuery($keyword)]
            );

            $query->orderByRaw(
                "ts_rank(to_tsvector('$lang', ".implode(" || ' ' || ", $fields)."), $tsQuery) DESC",
                [static::buildTsQuery($keyword)]
            );

            return $query;
        }

        // MySQL: 使用 MATCH AGAINST 并按相关性排序
        $columns = implode(', ', $fields);

        $query->whereRaw(
            "MATCH($columns) AGAINST(? IN BOOLEAN MODE)",
            [$keyword]
        );

        $query->orderByRaw(
            "MATCH($columns) AGAINST(? IN BOOLEAN MODE) DESC",
            [$keyword]
        );

        return $query;
    }

    /**
     * 构建 PostgreSQL tsquery
     *
     * 将用户输入转换为 tsquery 格式
     * 例如: "laravel framework" -> "laravel & framework"
     */
    protected static function buildTsQuery(string $keyword): string
    {
        $words = array_filter(preg_split('/\s+/', trim($keyword)));

        if (empty($words)) {
            return '';
        }

        return implode(' & ', $words);
    }
}
