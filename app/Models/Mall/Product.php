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
        SoftDeletes;

    protected $casts = [
        'deduct_stock_type' => DeductStockType::class,
        'status' => ProductStatus::class,
        'can_cart' => 'bool',
        'materials' => 'json',
        'ext' => 'json',
        'weight' => 'decimal:2',
        'volume' => 'decimal:2',
    ];

    protected $appends = [
        'delivery_template',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::saved(static function (Product $goods) {
            $goods->logs()->create([
                'user_type' => Auth::user()?->getMorphClass(),
                'user_id' => Auth::id(),
                'records' => Arr::except($goods->getDirty(), ['updated_at']),
            ]);
        });
    }

    /**
     * 操作日志
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ProductLog::class);
    }

    /**
     * 关联品牌
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * 关联分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * 关联运费模板
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * 获取运费模板（SKU 优先，其次商品）
     */
    public function getDeliveryTemplateAttribute(): ?Delivery
    {
        return $this->delivery;
    }

    /**
     * 获取总库存
     */
    public function getStocksAttribute(): int
    {
        return $this->skus()->sum('stock');
    }

    /**
     * 商品规格
     */
    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class);
    }

    /**
     * 获取总销量
     */
    public function getSalesAttribute(): int
    {
        return $this->skus()->sum('sale');
    }

    /**
     * 商品评价
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * 获取评价标题
     */
    public function getCommentableTitleAttribute(): string
    {
        return '[商品]#'.$this->getKey();
    }
}
