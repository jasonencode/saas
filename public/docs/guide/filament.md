# Filament 使用

## 架构概述

本项目采用 **Cluster (集群)** 模式组织 Filament 资源，将相关业务模块分组管理。所有集群位于 `app/Filament/Backend/Clusters/` 目录下。

项目有两个 Filament 面板：

| 面板 | ID | 路径 | Guard | 说明 |
|------|----|------|-------|------|
| 平台面板 | `backend` | `/backend` | `auth:backend` | 平台方管理，默认面板 |
| 租户面板 | `tenant` | `/tenant` | `auth:tenant` | 租户方管理，按域名识别租户（`TENANT_DOMAIN`） |

面板定义见 `app/Providers/BackendPanelProvider.php` 与 `app/Providers/TenantPanelProvider.php`，公共配置在抽象类 `app/Providers/FilamentPanelProvider.php`。

### Cluster 结构

| Cluster | 模块名称 | 说明 |
|---------|---------|------|
| BlockChain | 区块链 | 地址、证书、合约、合约仓库、网络管理 |
| Campaign | 营销活动 | 优惠券、红包、抽奖管理 |
| Content | 内容管理 | 内容、评论、分类、标签、单页、应用版本、敏感词 |
| Finance | 财务管理 | 账户、支付、发票、凭证、结算计划与任务 |
| Foundation | 基础配置 | 微信、支付宝、阿里云、社交登录等配置 |
| Mall | 商城管理 | 商品、订单、退款、店铺、配送、自提点等 |
| Setting | 系统管理 | 管理员、角色、API 日志、黑名单、失败任务、导入导出、Horizon 监控 |
| User | 用户管理 | 用户、租户、身份、实名、员工、用户关系、Token 管理 |

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
php artisan make:filament-resource Product --cluster=Mall
```

## Action 权限

### visible() 和 hidden()

在 Action 中控制显示/隐藏：

```php
use function App\Support\userCan;

Action::make('up')
    ->visible(fn (Product $record) => userCan('up', $record))
    ->action(fn (Product $record) => service(ProductService::class)->up($record));
```

### 推荐使用 visible()

> [!TIP]
> 推荐使用 `visible()` 而非 `hidden()`，因为语义更明确（默认隐藏，需要明确授权才显示）。

## 自定义 Action

自定义 Action 集中在 `app/Filament/Actions/` 下，按业务模块分组。每个 Action 是一个带静态 `make()` 方法的类：

```php
// app/Filament/Actions/Mall/ProductUpAction.php

namespace App\Filament\Actions\Mall;

use App\Models\Mall\Product;
use App\Services\Mall\ProductService;
use Filament\Actions\Action;

class ProductUpAction
{
    public static function make(): Action
    {
        return Action::make('up')
            ->label('上架')
            ->visible(fn (Product $record) => userCan('up', $record))
            ->action(fn (Product $record) => service(ProductService::class)->up($record));
    }
}
```

常用通用 Action（`app/Filament/Actions/Common/`）：`EnableBulkAction`、`DisableBulkAction`、`SetDefaultAction`、`RefreshAction`、`CustomExportAction` 等。

## 页面权限

在页面中使用策略方法：

```php
public static function canAccess(): bool
{
    return userCan('viewAny', Product::class);
}
```

## BlockChain 模块

### 资源列表

| 资源 | 说明 | 路径 |
|------|------|------|
| AddressResource | 链上地址管理 | `BlockChain/Resources/Addresses/` |
| CertificateResource | 证书管理 | `BlockChain/Resources/Certificates/` |
| ContractResource | 合约管理 | `BlockChain/Resources/Contracts/` |
| ContractRepositoryResource | 合约仓库管理 | `BlockChain/Resources/ContractRepositories/` |
| NetworkResource | 网络配置 | `BlockChain/Resources/Networks/` |

## Campaign 模块

### 资源列表

| 资源 | 说明 | 路径 |
|------|------|------|
| CouponResource | 优惠券管理 | `Campaign/Resources/Coupons/` |
| LotteryResource | 抽奖活动管理 | `Campaign/Resources/Lotteries/` |
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
| SinglePageResource | 单页管理 | `Content/Resources/SinglePages/` |
| TagResource | 标签管理 | `Content/Resources/Tags/` |

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
| ApplyResource | 店铺申请 | `Mall/Resources/Applies/` |
| BannerResource | Banner管理 | `Mall/Resources/Banners/` |
| BrandResource | 品牌管理 | `Mall/Resources/Brands/` |
| CategoryResource | 商品分类 | `Mall/Resources/Categories/` |
| ConfigureResource | 店铺配置 | `Mall/Resources/Configures/` |
| DeliveryResource | 配送方式 | `Mall/Resources/Deliveries/` |
| ExpressResource | 快递管理 | `Mall/Resources/Expresses/` |
| OrderResource | 订单管理 | `Mall/Resources/Orders/` |
| PickupPointResource | 自提点管理 | `Mall/Resources/PickupPoints/` |
| ProductResource | 商品管理 | `Mall/Resources/Products/` |
| RefundResource | 退款管理 | `Mall/Resources/Refunds/` |
| RegionResource | 地区管理 | `Mall/Resources/Regions/` |
| ReturnAddressResource | 退货地址 | `Mall/Resources/ReturnAddresses/` |
| SupplierResource | 供应商管理 | `Mall/Resources/Suppliers/` |
| TagResource | 商品标签 | `Mall/Resources/Tags/` |

## Setting 模块

### 资源列表

| 资源 | 说明 | 路径 |
|------|------|------|
| AdministratorResource | 管理员管理 | `Setting/Resources/Administrators/` |
| ApiLogResource | API 日志 | `Setting/Resources/ApiLogs/` |
| BlackListResource | 黑名单管理 | `Setting/Resources/BlackLists/` |
| DbLogResource | 数据库日志 | `Setting/Resources/DbLogs/` |
| ExportResource | 导出管理 | `Setting/Resources/Exports/` |
| FailedJobResource | 失败任务管理 | `Setting/Resources/FailedJobs/` |
| ImportResource | 导入管理 | `Setting/Resources/Imports/` |
| RoleResource | 角色权限管理 | `Setting/Resources/Roles/` |
| SystemResource | 系统配置 | `Setting/Resources/Systems/` |

另有自定义页面 `HorizonMonitor.php`（Horizon 队列监控）。

## User 模块

### 资源列表

| 资源 | 说明 | 路径 |
|------|------|------|
| IdentityResource | 身份管理 | `User/Resources/Identities/` |
| RealnameResource | 实名认证管理 | `User/Resources/Realnames/` |
| StafferResource | 员工管理 | `User/Resources/Staffers/` |
| TenantResource | 租户管理 | `User/Resources/Tenants/` |
| TokenResource | Token管理 | `User/Resources/Tokens/` |
| UserRelationResource | 用户关系 | `User/Resources/UserRelations/` |
| UserResource | 用户管理 | `User/Resources/Users/` |
