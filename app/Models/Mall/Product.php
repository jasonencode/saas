<?php

namespace App\Models\Mall;

use App\Contracts\ShouldComment;
use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\ProductStatus;
use App\Models\Content\Comment;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasSortable;
use App\Models\Traits\ProductScopes;
use App\Models\Traits\Searchable;
use App\Policies\Mall\ProductPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

#[Unguarded]
#[UsePolicy(ProductPolicy::class)]
class Product extends Model implements ShouldComment
{
    use BelongsToTenant,
        HasComments,
        HasCovers,
        HasSortable,
        ProductScopes,
        Searchable,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'deduct_stock_type' => DeductStockType::class,
            'status' => ProductStatus::class,
            'can_cart' => 'bool',
            'materials' => 'json',
            'ext' => 'json',
        ];
    }

    protected $appends = [
        'delivery_template',
        'price',
        'origin_price',
        'total_stock',
        'total_sale',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::updated(static function (Product $goods) {
            $dirty = Arr::except($goods->getDirty(), ['updated_at']);

            if (empty($dirty)) {
                return;
            }

            $goods->logs()->create([
                'user_type' => Auth::user()?->getMorphClass(),
                'user_id' => Auth::id(),
                'records' => $dirty,
            ]);
        });
    }

    /**
     * 操作日志
     *
     * @return HasMany<ProductLog>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ProductLog::class);
    }

    /**
     * 关联品牌
     *
     * @return BelongsTo<Brand>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * 关联分类
     *
     * @return BelongsTo<ProductCategory>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * 关联运费模板
     *
     * @return BelongsTo<Delivery>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * 关联供应商
     *
     * @return BelongsTo<Supplier>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * 获取运费模板（SKU 优先，其次商品）
     */
    public function getDeliveryTemplateAttribute(): ?Delivery
    {
        return $this->relationLoaded('delivery') ? $this->getRelation('delivery') : $this->delivery;
    }

    /**
     * 获取总库存（聚合字段，从所有 SKU 汇总）
     *
     * 优先使用 withSum('skus', 'stock') 预加载的值，避免 N+1 查询
     */
    public function getTotalStockAttribute(): int
    {
        if (array_key_exists('skus_sum_stock', $this->attributes)) {
            return (int) $this->attributes['skus_sum_stock'];
        }

        return $this->relationLoaded('skus') ? $this->skus->sum('stock') : $this->skus()->sum('stock');
    }

    /**
     * 获取销售价格区间
     *
     * 当只有一个 SKU 时返回单个价格，多个 SKU 时返回 "最低价-最高价" 格式
     */
    public function getPriceAttribute(): string
    {
        if ($this->relationLoaded('skus')) {
            $prices = $this->skus->pluck('price');
        } else {
            $min = $this->skus()->min('price');
            $max = $this->skus()->max('price');

            if (is_null($min)) {
                return '0.00';
            }

            if ($min === $max) {
                return number_format($min, 2, '.', '');
            }

            return number_format($min, 2, '.', '').'-'.number_format($max, 2, '.', '');
        }

        if ($prices->isEmpty()) {
            return '0.00';
        }

        if ($prices->count() === 1) {
            return number_format($prices->first(), 2, '.', '');
        }

        return number_format($prices->min(), 2, '.', '').'-'.number_format($prices->max(), 2, '.', '');
    }

    /**
     * 获取市场原价区间
     *
     * 当只有一个 SKU 时返回单个价格，多个 SKU 时返回 "最低价-最高价" 格式
     */
    public function getOriginPriceAttribute(): string
    {
        if ($this->relationLoaded('skus')) {
            $prices = $this->skus->pluck('origin_price')->filter()->values();
        } else {
            $min = $this->skus()->whereNotNull('origin_price')->min('origin_price');
            $max = $this->skus()->whereNotNull('origin_price')->max('origin_price');

            if (is_null($min)) {
                return '0.00';
            }

            if ($min === $max) {
                return number_format($min, 2, '.', '');
            }

            return number_format($min, 2, '.', '').'-'.number_format($max, 2, '.', '');
        }

        if ($prices->isEmpty()) {
            return '0.00';
        }

        if ($prices->count() === 1) {
            return number_format($prices->first(), 2, '.', '');
        }

        return number_format($prices->min(), 2, '.', '').'-'.number_format($prices->max(), 2, '.', '');
    }

    /**
     * 商品规格
     *
     * @return HasMany<Sku>
     */
    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class)->orderByDesc('sort');
    }

    /**
     * 获取总销量（聚合字段，从所有 SKU 汇总）
     *
     * 优先使用 withSum('skus', 'sale') 预加载的值，避免 N+1 查询
     */
    public function getTotalSaleAttribute(): int
    {
        if (array_key_exists('skus_sum_sale', $this->attributes)) {
            return (int) $this->attributes['skus_sum_sale'];
        }

        return $this->skus()->sum('sale');
    }

    /**
     * 商品评价
     *
     * @return MorphMany<Comment>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * 关联店铺配置
     *
     * @return BelongsTo<StoreConfigure>
     */
    public function storeConfigure(): BelongsTo
    {
        return $this->belongsTo(StoreConfigure::class, 'tenant_id', 'tenant_id');
    }

    /**
     * 获取评价标题
     */
    public function getCommentableTitleAttribute(): string
    {
        return '[商品]#'.$this->getKey();
    }
}
