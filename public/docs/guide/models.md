# 模型定义

## 模型规范

### 命名规范

- 模型名称使用单数形式：`User`, `Product`, `Order`
- 文件名称与类名一致
- 放在 `app/Models` 目录下，按业务模块分组

### 基础模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class Model extends Model
{
    // 公共功能
}
```

### 模型 Trait

常用 Trait 位于 `app/Models/Traits/` 目录：

| Trait | 说明 |
|-------|------|
| `BelongsToTenant` | 多租户支持，关联租户 |
| `BelongsToUser` | 关联用户 |
| `HasComments` | 支持评论 |
| `HasCovers` | 支持封面图 |
| `HasSortable` | 支持排序 |
| `HasEasyStatus` | 状态管理 |
| `HasRegion` | 地区关联 |
| `AutoCreateOrderNo` | 自动生成订单号 |
| `OrderScopes` | 订单查询作用域 |
| `ProductScopes` | 商品查询作用域 |
| `RefundScopes` | 退款查询作用域 |
| `MorphToUser` | 用户多态关联 |
| `BelongsToOrder` | 关联订单 |
| `BelongsToRefund` | 关联退款 |

## 枚举类

枚举类位于 `app/Enums/` 目录，按业务模块分组：

```php
<?php

namespace App\Enums\Mall;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending => '待支付',
            self::Paid => '已支付',
            // ...
        };
    }
}
```

### 枚举模块列表

#### BlockChain 模块

| 枚举 | 说明 | 路径 |
|------|------|------|
| `CertificateSignType` | 证书签名类型 | `app/Enums/BlockChain/CertificateSignType.php` |
| `CertificateType` | 证书类型 | `app/Enums/BlockChain/CertificateType.php` |
| `ChainType` | 链类型 | `app/Enums/BlockChain/ChainType.php` |
| `ContractType` | 合约类型 | `app/Enums/BlockChain/ContractType.php` |

#### Campaign 模块

| 枚举 | 说明 | 路径 |
|------|------|------|
| `CouponType` | 优惠券类型 | `app/Enums/Campaign/CouponType.php` |
| `ExpiredType` | 过期类型 | `app/Enums/Campaign/ExpiredType.php` |
| `RedpackCodeStatus` | 红包码状态 | `app/Enums/Campaign/RedpackCodeStatus.php` |

#### Content 模块

| 枚举 | 说明 | 路径 |
|------|------|------|
| `CategoryType` | 分类类型 | `app/Enums/Content/CategoryType.php` |
| `PlatformType` | 平台类型 | `app/Enums/Content/PlatformType.php` |

#### Finance 模块

| 枚举 | 说明 | 路径 |
|------|------|------|
| `AccountAssetType` | 账户资产类型 | `app/Enums/Finance/AccountAssetType.php` |
| `InvoiceApplicationStatus` | 发票申请状态 | `app/Enums/Finance/InvoiceApplicationStatus.php` |
| `InvoiceStatus` | 发票状态 | `app/Enums/Finance/InvoiceStatus.php` |
| `InvoiceTitleType` | 发票抬头类型 | `app/Enums/Finance/InvoiceTitleType.php` |
| `InvoiceType` | 发票类型 | `app/Enums/Finance/InvoiceType.php` |
| `PaymentGateway` | 支付网关 | `app/Enums/Finance/PaymentGateway.php` |
| `PaymentRefundStatus` | 支付退款状态 | `app/Enums/Finance/PaymentRefundStatus.php` |
| `PaymentStatus` | 支付状态 | `app/Enums/Finance/PaymentStatus.php` |
| `VoucherStatus` | 凭证状态 | `app/Enums/Finance/VoucherStatus.php` |

#### Foundation 模块

| 枚举 | 说明 | 路径 |
|------|------|------|
| `AliyunDnsType` | 阿里云 DNS 类型 | `app/Enums/Foundation/AliyunDnsType.php` |
| `AliyunDomainStatus` | 阿里云域名状态 | `app/Enums/Foundation/AliyunDomainStatus.php` |
| `AliyunInstanceChargeType` | 阿里云实例计费类型 | `app/Enums/Foundation/AliyunInstanceChargeType.php` |
| `SocialiteProvider` | 社交登录提供商 | `app/Enums/Foundation/SocialiteProvider.php` |

#### Mall 模块

| 枚举 | 说明 | 路径 |
|------|------|------|
| `ApplyStatus` | 入驻申请状态 | `app/Enums/Mall/ApplyStatus.php` |
| `DeductStockType` | 扣库存类型 | `app/Enums/Mall/DeductStockType.php` |
| `DeliveryType` | 配送类型 | `app/Enums/Mall/DeliveryType.php` |
| `OrderStatus` | 订单状态 | `app/Enums/Mall/OrderStatus.php` |
| `ProductStatus` | 商品状态 | `app/Enums/Mall/ProductStatus.php` |
| `RefundStatus` | 退款状态 | `app/Enums/Mall/RefundStatus.php` |
| `RegionLevel` | 地区级别 | `app/Enums/Mall/RegionLevel.php` |

#### System 模块

| 枚举 | 说明 | 路径 |
|------|------|------|
| `AdminType` | 管理员类型 | `app/Enums/System/AdminType.php` |
| `HttpMethod` | HTTP 方法 | `app/Enums/System/HttpMethod.php` |
| `PolicyPlatform` | 策略平台 | `app/Enums/System/PolicyPlatform.php` |

#### User 模块

| 枚举 | 说明 | 路径 |
|------|------|------|
| `Gender` | 性别 | `app/Enums/User/Gender.php` |
| `IdentityChannel` | 身份渠道 | `app/Enums/User/IdentityChannel.php` |
| `IdentityOrderStatus` | 身份订单状态 | `app/Enums/User/IdentityOrderStatus.php` |
| `RealnameStatus` | 实名认证状态 | `app/Enums/User/RealnameStatus.php` |
| `RealnameType` | 实名认证类型 | `app/Enums/User/RealnameType.php` |
| `SmsChannel` | 短信渠道 | `app/Enums/User/SmsChannel.php` |
| `UserAccountLogType` | 用户账户日志类型 | `app/Enums/User/UserAccountLogType.php` |

## 模型模块列表

### BlockChain（区块链模块）

| 模型 | 说明 | 路径 |
|------|------|------|
| `ChainAddress` | 链上地址 | `app/Models/BlockChain/ChainAddress.php` |
| `Certificate` | 证书 | `app/Models/BlockChain/Certificate.php` |
| `Contract` | 合约 | `app/Models/BlockChain/Contract.php` |
| `Network` | 网络配置 | `app/Models/BlockChain/Network.php` |

### Campaign（营销活动模块）

| 模型 | 说明 | 路径 |
|------|------|------|
| `Coupon` | 优惠券 | `app/Models/Campaign/Coupon.php` |
| `CouponUser` | 用户优惠券 | `app/Models/Campaign/CouponUser.php` |
| `CouponProduct` | 优惠券商品关联 | `app/Models/Campaign/CouponProduct.php` |
| `CouponOrder` | 优惠券订单关联 | `app/Models/Campaign/CouponOrder.php` |
| `Redpack` | 红包 | `app/Models/Campaign/Redpack.php` |
| `RedpackCode` | 红包码 | `app/Models/Campaign/RedpackCode.php` |

### Content（内容模块）

| 模型 | 说明 | 路径 |
|------|------|------|
| `Content` | 内容 | `app/Models/Content/Content.php` |
| `Category` | 分类 | `app/Models/Content/Category.php` |
| `Comment` | 评论 | `app/Models/Content/Comment.php` |
| `AppVersion` | 应用版本 | `app/Models/Content/AppVersion.php` |
| `Notification` | 通知 | `app/Models/Content/Notification.php` |

### Finance（财务模块）

| 模型 | 说明 | 路径 |
|------|------|------|
| `UserAccount` | 用户账户 | `app/Models/Finance/UserAccount.php` |
| `UserAccountLog` | 账户日志 | `app/Models/Finance/UserAccountLog.php` |
| `PaymentOrder` | 支付单 | `app/Models/Finance/PaymentOrder.php` |
| `PaymentRefund` | 支付退款 | `app/Models/Finance/PaymentRefund.php` |
| `InvoiceApplication` | 发票申请 | `app/Models/Finance/InvoiceApplication.php` |
| `Invoice` | 发票 | `app/Models/Finance/Invoice.php` |
| `InvoiceTitle` | 发票抬头 | `app/Models/Finance/InvoiceTitle.php` |
| `Voucher` | 凭证 | `app/Models/Finance/Voucher.php` |
| `VoucherLog` | 凭证日志 | `app/Models/Finance/VoucherLog.php` |
| `Plan` | 结算计划 | `app/Models/Finance/Plan.php` |
| `Task` | 结算任务 | `app/Models/Finance/Task.php` |
| `Refund` | 退款 | `app/Models/Finance/Refund.php` |

### Foundation（基础配置模块）

| 模型 | 说明 | 路径 |
|------|------|------|
| `Wechat` | 微信公众号 | `app/Models/Foundation/Wechat.php` |
| `WechatMini` | 微信小程序 | `app/Models/Foundation/WechatMini.php` |
| `WechatPayment` | 微信支付 | `app/Models/Foundation/WechatPayment.php` |
| `Alipay` | 支付宝 | `app/Models/Foundation/Alipay.php` |
| `Aliyun` | 阿里云配置 | `app/Models/Foundation/Aliyun.php` |
| `AliyunDns` | 阿里云 DNS | `app/Models/Foundation/AliyunDns.php` |
| `AliyunDomain` | 阿里云域名 | `app/Models/Foundation/AliyunDomain.php` |
| `AliyunEcs` | 阿里云 ECS | `app/Models/Foundation/AliyunEcs.php` |
| `Socialite` | 社交登录配置 | `app/Models/Foundation/Socialite.php` |
| `SocialiteAccount` | 社交账号绑定 | `app/Models/Foundation/SocialiteAccount.php` |

### Mall（商城模块）

| 模型 | 说明 | 路径 |
|------|------|------|
| `Product` | 商品 | `app/Models/Mall/Product.php` |
| `Sku` | 商品规格 | `app/Models/Mall/Sku.php` |
| `SkuAttribute` | SKU 属性 | `app/Models/Mall/SkuAttribute.php` |
| `Attribute` | 属性 | `app/Models/Mall/Attribute.php` |
| `AttributeValue` | 属性值 | `app/Models/Mall/AttributeValue.php` |
| `Brand` | 品牌 | `app/Models/Mall/Brand.php` |
| `Category` | 商品分类 | `app/Models/Mall/Category.php` |
| `ProductCategory` | 商品分类关联 | `app/Models/Mall/ProductCategory.php` |
| `Order` | 订单 | `app/Models/Mall/Order.php` |
| `OrderItem` | 订单明细 | `app/Models/Mall/OrderItem.php` |
| `OrderAddress` | 订单地址 | `app/Models/Mall/OrderAddress.php` |
| `OrderShipping` | 订单物流 | `app/Models/Mall/OrderShipping.php` |
| `OrderLog` | 订单日志 | `app/Models/Mall/OrderLog.php` |
| `Refund` | 退款 | `app/Models/Mall/Refund.php` |
| `RefundItem` | 退款明细 | `app/Models/Mall/RefundItem.php` |
| `RefundExpress` | 退款物流 | `app/Models/Mall/RefundExpress.php` |
| `RefundLog` | 退款日志 | `app/Models/Mall/RefundLog.php` |
| `Cart` | 购物车 | `app/Models/Mall/Cart.php` |
| `CartItem` | 购物车项 | `app/Models/Mall/CartItem.php` |
| `Banner` | Banner | `app/Models/Mall/Banner.php` |
| `Supplier` | 供应商 | `app/Models/Mall/Supplier.php` |
| `StoreApply` | 入驻申请 | `app/Models/Mall/StoreApply.php` |
| `StoreConfigure` | 店铺配置 | `app/Models/Mall/StoreConfigure.php` |
| `Express` | 快递公司 | `app/Models/Mall/Express.php` |
| `Delivery` | 配送方式 | `app/Models/Mall/Delivery.php` |
| `DeliveryRule` | 配送规则 | `app/Models/Mall/DeliveryRule.php` |
| `Region` | 地区 | `app/Models/Mall/Region.php` |
| `ReturnAddress` | 退货地址 | `app/Models/Mall/ReturnAddress.php` |
| `ProductLog` | 商品日志 | `app/Models/Mall/ProductLog.php` |

### User（用户模块）

| 模型 | 说明 | 路径 |
|------|------|------|
| `User` | 用户 | `app/Models/User/User.php` |
| `UserProfile` | 用户资料 | `app/Models/User/UserProfile.php` |
| `UserRealname` | 用户实名认证 | `app/Models/User/UserRealname.php` |
| `UserRelation` | 用户关系 | `app/Models/User/UserRelation.php` |
| `UserIdentity` | 用户身份关联 | `app/Models/User/UserIdentity.php` |
| `Identity` | 身份定义 | `app/Models/User/Identity.php` |
| `IdentityOrder` | 身份订单 | `app/Models/User/IdentityOrder.php` |
| `IdentityLog` | 身份日志 | `app/Models/User/IdentityLog.php` |
| `Tenant` | 租户 | `app/Models/User/Tenant.php` |
| `Address` | 用户地址 | `app/Models/User/Address.php` |
| `SmsCode` | 短信验证码 | `app/Models/User/SmsCode.php` |
| `LoginRecord` | 登录记录 | `app/Models/User/LoginRecord.php` |

### System（系统模块）

| 模型 | 说明 | 路径 |
|------|------|------|
| `Administrator` | 管理员 | `app/Models/System/Administrator.php` |
| `AdministratorRole` | 管理员角色关联 | `app/Models/System/AdministratorRole.php` |
| `AdministratorTenant` | 管理员租户关联 | `app/Models/System/AdministratorTenant.php` |
| `AdminRole` | 角色 | `app/Models/System/AdminRole.php` |
| `AdminRolePermission` | 角色权限 | `app/Models/System/AdminRolePermission.php` |
| `ApiLog` | API 日志 | `app/Models/System/ApiLog.php` |
| `Sensitive` | 敏感词 | `app/Models/System/Sensitive.php` |
| `BlackList` | 黑名单 | `app/Models/System/BlackList.php` |
| `System` | 系统配置 | `app/Models/System/System.php` |
| `FailedJob` | 失败任务 | `app/Models/System/FailedJob.php` |
| `JobBatch` | 任务批次 | `app/Models/System/JobBatch.php` |

## 核心模型详解

### User（用户模型）

**所属模块**: User

**主要关联**:

| 关联方法 | 类型 | 关联模型 | 说明 |
|---------|------|---------|------|
| `profile()` | HasOne | `UserProfile` | 用户资料 |
| `account()` | HasOne | `UserAccount` | 用户账户 |
| `relation()` | HasOne | `UserRelation` | 推荐关系 |
| `identities()` | BelongsToMany | `Identity` | 用户身份 |
| `addresses()` | HasMany | `Address` | 用户地址 |
| `realname()` | HasOne | `UserRealname` | 实名认证 |
| `orders()` | HasMany | `Order` | 用户订单 |
| `comments()` | HasMany | `Comment` | 用户评论 |
| `coupons()` | HasMany | `CouponUser` | 用户优惠券 |

**使用的 Trait**:
- `BelongsToTenant` - 多租户支持
- `HasApiTokens` - API Token 支持
- `SoftDeletes` - 软删除

**关键特性**:
- 创建用户时自动创建 `profile` 和 `account`
- 使用 `#[UsePolicy]` 注解绑定 `UserPolicy`

### Product（商品模型）

**所属模块**: Mall

**主要关联**:

| 关联方法 | 类型 | 关联模型 | 说明 |
|---------|------|---------|------|
| `logs()` | HasMany | `ProductLog` | 操作日志 |
| `brand()` | BelongsTo | `Brand` | 品牌 |
| `categories()` | BelongsToMany | `Category` | 分类 |
| `attributes()` | HasMany | `Attribute` | 属性 |
| `skus()` | HasMany | `Sku` | 规格 |
| `comments()` | MorphMany | `Comment` | 评价 |

**使用的 Trait**:
- `BelongsToTenant` - 多租户支持
- `HasCovers` - 封面图支持
- `HasComments` - 评论支持
- `HasSortable` - 排序支持
- `ProductScopes` - 查询作用域
- `SoftDeletes` - 软删除

**关键特性**:
- 自动记录操作日志到 `ProductLog`
- 支持多分类关联
- 支持规格（SKU）管理

### Order（订单模型）

**所属模块**: Mall

**主要关联**:

| 关联方法 | 类型 | 关联模型 | 说明 |
|---------|------|---------|------|
| `items()` | HasMany | `OrderItem` | 订单明细 |
| `refunds()` | HasMany | `Refund` | 售后记录 |
| `shippings()` | HasMany | `OrderShipping` | 物流信息 |
| `address()` | HasOne | `OrderAddress` | 订单地址 |
| `logs()` | HasMany | `OrderLog` | 订单日志 |
| `paymentOrders()` | MorphMany | `PaymentOrder` | 支付单 |

**使用的 Trait**:
- `AutoCreateOrderNo` - 自动生成订单号
- `BelongsToTenant` - 多租户支持
- `BelongsToUser` - 关联用户
- `OrderScopes` - 查询作用域
- `SoftDeletes` - 软删除

**关键特性**:
- 创建时自动设置过期时间
- 使用订单号作为路由键
- 支持计算属性：`products_count`, `skus_count`, `skus_quantities`, `total_amount`

## 模型设计模式

### 观察者模式

使用 `$dispatchesEvents` 属性定义事件：

```php
protected $dispatchesEvents = [
    'created' => UserCreatedEvent::class,
];
```

### 策略模式

使用 `#[UsePolicy]` 注解绑定策略类：

```php
#[UsePolicy(UserPolicy::class)]
class User extends Authenticatable
{
    // ...
}
```

### 契约接口

实现接口定义行为契约：

```php
use App\Contracts\ShouldPayment;

class Order extends Model implements ShouldPayment
{
    // 实现支付相关方法
}
```

## 枚举使用示例

在模型中使用枚举类型转换：

```php
protected $casts = [
    'status' => OrderStatus::class,
    'deduct_stock_type' => DeductStockType::class,
];
```

使用枚举进行查询：

```php
$orders = Order::where('status', OrderStatus::Paid)->get();
```

获取枚举标签：

```php
$order->status->label(); // 返回 "已支付"
```

## 服务层

服务类位于 `app/Services/` 目录，按业务模块分组。所有服务类实现 `ServiceInterface` 接口。

### 服务层架构

```
app/Services/
├── Campaign/          # 营销活动服务
├── Content/           # 内容管理服务
├── Finance/           # 财务管理服务
├── Foundation/        # 基础配置服务
├── Mall/              # 商城管理服务
├── System/            # 系统服务
└── User/              # 用户管理服务
```

### 服务模块列表

#### Campaign（营销活动模块）

| 服务 | 说明 | 路径 |
|------|------|------|
| `CouponService` | 优惠券服务 | `app/Services/Campaign/CouponService.php` |
| `RedpackService` | 红包服务 | `app/Services/Campaign/RedpackService.php` |

#### Content（内容模块）

| 服务 | 说明 | 路径 |
|------|------|------|
| `AppVersionService` | 应用版本服务 | `app/Services/Content/AppVersionService.php` |
| `TrackVisitService` | 访问统计服务 | `app/Services/Content/TrackVisitService.php` |

#### Finance（财务模块）

| 服务 | 说明 | 路径 |
|------|------|------|
| `PaymentService` | 支付服务 | `app/Services/Finance/PaymentService.php` |
| `SettlementService` | 结算服务 | `app/Services/Finance/SettlementService.php` |
| `UserAccountService` | 用户账户服务 | `app/Services/Finance/UserAccountService.php` |
| `VoucherService` | 凭证服务 | `app/Services/Finance/VoucherService.php` |

#### Foundation（基础配置模块）

| 服务 | 说明 | 路径 |
|------|------|------|
| `AlipayService` | 支付宝服务 | `app/Services/Foundation/AlipayService.php` |
| `SmsService` | 短信服务 | `app/Services/Foundation/SmsService.php` |
| `UploadService` | 上传服务 | `app/Services/Foundation/UploadService.php` |
| `WechatPaymentService` | 微信支付服务 | `app/Services/Foundation/WechatPaymentService.php` |
| `WechatService` | 微信服务 | `app/Services/Foundation/WechatService.php` |

#### Mall（商城模块）

| 服务 | 说明 | 路径 |
|------|------|------|
| `CartService` | 购物车服务 | `app/Services/Mall/CartService.php` |
| `OrderService` | 订单服务 | `app/Services/Mall/OrderService.php` |
| `ProductService` | 商品服务 | `app/Services/Mall/ProductService.php` |
| `StoreService` | 店铺服务 | `app/Services/Mall/StoreService.php` |

#### System（系统模块）

| 服务 | 说明 | 路径 |
|------|------|------|
| `BlackListService` | 黑名单服务 | `app/Services/System/BlackListService.php` |
| `SensitiveService` | 敏感词服务 | `app/Services/System/SensitiveService.php` |
| `TaskService` | 任务服务 | `app/Services/System/TaskService.php` |

#### User（用户模块）

| 服务 | 说明 | 路径 |
|------|------|------|
| `IdentityService` | 身份服务 | `app/Services/User/IdentityService.php` |
| `RealnameService` | 实名认证服务 | `app/Services/User/RealnameService.php` |
| `TenantService` | 租户服务 | `app/Services/User/TenantService.php` |
| `UserRelationService` | 用户关系服务 | `app/Services/User/UserRelationService.php` |

### 服务类示例

```php
<?php

namespace App\Services\Mall;

use App\Contracts\ServiceInterface;
use App\Models\Mall\Product;

class ProductService implements ServiceInterface
{
    public function up(Product $product): void
    {
        // 上架商品逻辑
    }

    public function down(Product $product): void
    {
        // 下架商品逻辑
    }
}
```

### 服务注入

使用构造函数注入服务：

```php
use App\Services\Mall\OrderService;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function show(Order $order)
    {
        $orderData = $this->orderService->getDetail($order);
        return OrderResource::make($orderData);
    }
}
```
