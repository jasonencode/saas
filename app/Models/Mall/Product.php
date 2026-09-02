<?php

namespace App\Models\Mall;

use App\Contracts\ShouldComment;
use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\ProductStatus;
use App\Models\Campaign\Coupon;
use App\Models\Content\Comment;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasSortable;
use App\Models\Traits\ProductScopes;
use App\Models\Traits\Searchable;
use App\Observers\ProductObserver;
use App\Policies\Mall\ProductPolicy;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Overtrue\LaravelFavorite\Traits\Favoriteable;

#[Unguarded]
#[UsePolicy(ProductPolicy::class)]
class Product extends Model implements ShouldComment
{
    use BelongsToTenant,
        Cachable,
        Favoriteable,
        HasComments,
        HasCovers,
        HasSortable,
        ProductScopes,
        Searchable,
        SoftDeletes;

    protected $appends = [
        'delivery_template',
        'price',
        'origin_price',
        'total_stock',
        'total_sale',
    ];

    protected function casts(): array
    {
        return [
            'deduct_stock_type' => DeductStockType::class,
            'fulfillment_type' => 'array',
            'status' => ProductStatus::class,
            'can_cart' => 'bool',
            'materials' => 'json',
            'ext' => 'json',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::observe(ProductObserver::class);

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
     * 是否需要物流发货（仅支持快递邮寄时）
     */
    public function needsShipping(): bool
    {
        return in_array(FulfillmentType::Mail->value, $this->fulfillment_type ?? [], true);
    }

    /**
     * 是否支持指定履约方式
     *
     * @param  FulfillmentType  $type  履约方式
     *
     * @return bool 是否支持该履约方式
     */
    public function supportsFulfillmentType(FulfillmentType $type): bool
    {
        return in_array($type->value, $this->fulfillment_type ?? [], true);
    }

    /**
     * 是否免运费（仅当不支持快递邮寄时全部免运费）
     */
    public function isFreeFreight(): bool
    {
        return !$this->needsShipping();
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
     * 关联标签
     *
     * @return BelongsToMany<ProductTag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_tag', 'product_id', 'tag_id')
            ->orderBy('sort');
    }

    /**
     * 关联优惠券
     *
     * @return BelongsToMany<Coupon>
     */
    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_product')
            ->withTimestamps();
    }

    /**
     * 关联专题
     *
     * @return BelongsToMany<Topic>
     */
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'topic_product', 'product_id', 'topic_id')
            ->withPivot('sort')
            ->orderByPivotDesc('sort');
    }

    /**
     * 关联自提点
     *
     * @return BelongsToMany<PickupPoint>
     */
    public function pickupPoints(): BelongsToMany
    {
        return $this->belongsToMany(PickupPoint::class, 'pickup_point_product', 'product_id', 'pickup_point_id');
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
     * 退货地址
     *
     * @return BelongsTo<ReturnAddress>
     */
    public function returnAddress(): BelongsTo
    {
        return $this->belongsTo(ReturnAddress::class);
    }

    protected function materialUrls(): Attribute
    {
        return Attribute::get(function () {
            $pictures = $this->getAttribute('materials');

            return Collection::wrap($pictures ?? [])
                ->map(fn ($picture) => $this->parseImageUrl($picture))
                ->filter()
                ->values()
                ->all();
        })->shouldCache();
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
     * 获取销售最低价
     */
    public function getPriceAttribute(): string
    {
        if (array_key_exists('skus_min_price', $this->attributes)) {
            return number_format((float) $this->attributes['skus_min_price'], 2, '.', '');
        }

        if ($this->relationLoaded('skus')) {
            $prices = $this->skus->pluck('price');
        } else {
            return number_format((float) $this->skus()->min('price'), 2, '.', '');
        }

        return $prices->isEmpty() ? '0.00' : number_format((float) $prices->min(), 2, '.', '');
    }

    /**
     * 获取市场原价最低价
     */
    public function getOriginPriceAttribute(): string
    {
        if (array_key_exists('skus_min_origin_price', $this->attributes)) {
            return number_format((float) $this->attributes['skus_min_origin_price'], 2, '.', '');
        }

        if ($this->relationLoaded('skus')) {
            $prices = $this->skus->pluck('origin_price')->filter()->values();
        } else {
            return number_format((float) $this->skus()->whereNotNull('origin_price')->min('origin_price'), 2, '.', '');
        }

        return $prices->isEmpty() ? '0.00' : number_format((float) $prices->min(), 2, '.', '');
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
     * 获取评价标题
     */
    public function getCommentableTitleAttribute(): string
    {
        return '[商品]#'.$this->getKey();
    }
}
