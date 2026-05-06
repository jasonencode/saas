# Filament 使用

## 架构概述

本项目采用 **Cluster (集群)** 模式组织 Filament 资源，将相关业务模块分组管理。所有资源位于 `app/Filament/Backend/Clusters/` 目录下。

### Cluster 结构

| Cluster | 模块名称 | 说明 |
|---------|---------|------|
| BlockChain | 区块链 | 地址、证书、合约、网络管理 |
| Campaign | 营销活动 | 优惠券、红包管理 |
| Content | 内容管理 | 内容、评论、分类、敏感词 |
| Finance | 财务管理 | 账户、支付、发票、退款 |
| Foundation | 基础配置 | 微信、支付宝、阿里云等配置 |
| Mall | 商城管理 | 商品、订单、供应商等 |
| User | 用户管理 | 用户、租户、Token管理 |

### 资源文件结构

每个资源遵循统一的目录结构：

```
Resources/
├── ResourceName/
│   ├── Pages/           # 页面定义
│   │   ├── ManageXXX.php
│   │   ├── ViewXXX.php
│   │   └── CreateXXX.php (可选)
│   ├── Tables/          # 表格定义
│   │   └── XXXTable.php
│   ├── Schemas/         # 表单和详情定义
│   │   ├── XXXForm.php
│   │   └── XXXInfolist.php (可选)
│   ├── RelationManagers/ # 关联管理器 (可选)
│   ├── Widgets/         # 仪表盘组件 (可选)
│   └── XXXResource.php  # 资源入口
```

## 创建资源

```bash
php artisan make:filament-resource Product
```

## Action 权限

### visible() 和 hidden()

在 Action 中控制显示/隐藏：

```php
use function App\Helpers\userCan;

Action::make('up')
    ->visible(fn (Product $record) => userCan('up', $record))
    ->action(fn (Product $record) => $record->up());
```

### 推荐使用 visible()

> [!TIP]
> 推荐使用 `visible()` 而非 `hidden()`，因为语义更明确（默认隐藏，需要明确授权才显示）。

## 自定义 Action

创建自定义 Action：

```php
// app/Filament/Actions/Mall/ProductUpAction.php

namespace App\Filament\Actions\Mall;

use App\Models\Product;
use App\Services\Mall\ProductService;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class ProductUpAction
{
    public static function make(): Action
    {
        return Action::make('up')
            ->label('上架')
            ->visible(fn (Product $record) => userCan('up', $record))
            ->action(function (Product $record) {
                app(ProductService::class)->up($record);
            });
    }
}
```

## 页面权限

在页面中使用策略方法：

```php
protected function getHeaderActions(): array
{
    return [
        CreateAction::make(),
    ];
}

public static function canAccess(): bool
{
    return user()->can('create', Product::class);
}
```

## BlockChain 模块

### 资源列表

| 资源 | 说明 | 路径 |
|------|------|------|
| AddressResource | 链上地址管理 | `BlockChain/Resources/Addresses/` |
| CertificateResource | 证书管理 | `BlockChain/Resources/Certificates/` |
| ContractResource | 合约管理 | `BlockChain/Resources/Contracts/` |
| NetworkResource | 网络配置 | `BlockChain/Resources/Networks/` |

## Campaign 模块

### 资源列表

| 资源 | 说明 | 路径 |
|------|------|------|
| CouponResource | 优惠券管理 | `Campaign/Resources/Coupons/` |
| RedpackResource | 红包管理 | `Campaign/Resources/Redpacks/` |

## Content 模块

### 资源列表

| 资源 | 说明 | 路径 |
|------|------|------|
| AppVersionResource | 应用版本管理 | `Content/Resources/AppVersions/` |
| CategoryResource | 内容分类 | `Content/Resources/Categories/` |
| CommentResource | 评论管理 | `Content/Resources/Comments/` |
| ContentResource | 内容管理 | `Content/Resources/Contents/` |
| NotificationResource | 通知管理 | `Content/Resources/Notifications/` |
| SensitiveResource | 敏感词管理 | `Content/Resources/Sensitives/` |

## Finance 模块

### 资源列表

| 资源 | 说明 | 路径 |
|------|------|------|
| AccountResource | 用户账户管理 | `Finance/Resources/Accounts/` |
| InvoiceApplicationResource | 发票申请 | `Finance/Resources/InvoiceApplications/` |
| InvoiceTitleResource | 发票抬头 | `Finance/Resources/InvoiceTitles/` |
| InvoiceResource | 发票管理 | `Finance/Resources/Invoices/` |
| PaymentResource | 支付记录 | `Finance/Resources/Payments/` |
| PlanResource | 结算计划 | `Finance/Resources/Plans/` |
| RefundResource | 退款管理 | `Finance/Resources/Refunds/` |
| VoucherResource | 凭证管理 | `Finance/Resources/Vouchers/` |

## Foundation 模块

### 资源列表

| 资源 | 说明 | 路径 |
|------|------|------|
| AlipayResource | 支付宝配置 | `Foundation/Resources/Alipays/` |
| AliyunResource | 阿里云配置 | `Foundation/Resources/Aliyuns/` |
| SocialiteAccountResource | 社交账号绑定 | `Foundation/Resources/SocialiteAccounts/` |
| SocialiteResource | 社交登录配置 | `Foundation/Resources/Socialites/` |
| WechatMiniResource | 微信小程序配置 | `Foundation/Resources/WechatMinis/` |
| WechatPaymentResource | 微信支付配置 | `Foundation/Resources/WechatPayments/` |
| WechatResource | 微信公众号配置 | `Foundation/Resources/Wechats/` |

## Mall 模块

### 资源列表

| 资源 | 说明 | 路径 |
|------|------|------|
| AddressResource | 收货地址 | `Mall/Resources/Addresses/` |
| ApplyResource | 入驻申请 | `Mall/Resources/Applies/` |
| BannerResource | Banner管理 | `Mall/Resources/Banners/` |
| BrandResource | 品牌管理 | `Mall/Resources/Brands/` |
| CategoryResource | 商品分类 | `Mall/Resources/Categories/` |
| ConfigureResource | 商城配置 | `Mall/Resources/Configures/` |
| ExpressResource | 快递管理 | `Mall/Resources/Expresses/` |
| OrderResource | 订单管理 | `Mall/Resources/Orders/` |
| ProductResource | 商品管理 | `Mall/Resources/Products/` |
| RefundResource | 退款管理 | `Mall/Resources/Refunds/` |
| RegionResource | 地区管理 | `Mall/Resources/Regions/` |
| ReturnAddressResource | 退货地址 | `Mall/Resources/ReturnAddresses/` |
| SupplierResource | 供应商管理 | `Mall/Resources/Suppliers/` |

## User 模块

### 资源列表

| 资源 | 说明 | 路径 |
|------|------|------|
| TenantResource | 租户管理 | `User/Resources/Tenants/` |
| UserResource | 用户管理 | `User/Resources/Users/` |
| TokenResource | Token管理 | `User/Resources/Tokens/` |
| UserRelationResource | 用户关系 | `User/Resources/UserRelations/` |
