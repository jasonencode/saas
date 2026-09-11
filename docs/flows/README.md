# 流程图文档

业务流程与状态机流转图，配套源码位置见各文档开头说明。

**命名规则：** `<模块>-<主题>.md`，kebab-case，不带 `-flow` 后缀（文件夹名已表达流程含义），如 `order-status.md`。

| 文档 | 说明 |
|------|------|
| [order-status.md](order-status.md) | 商城订单状态流转（按履约方式分 mail / pickup / virtual 三条链路） |
| [refund-status.md](refund-status.md) | 退款状态流转（仅退款 / 退货退款两条链路） |
| [payment-status.md](payment-status.md) | 支付单状态流转（余额 / 微信分流与回调） |
| [payment-refund-status.md](payment-refund-status.md) | 支付退款单状态流转（通道侧退款单，仅 Pending 可达） |
| [recharge-status.md](recharge-status.md) | 充值订单状态流转（待支付 → 已支付 → 已完成） |
| [withdraw-status.md](withdraw-status.md) | 提现订单状态流转（审核 / 取消 / 打款） |
| [invoice-status.md](invoice-status.md) | 发票状态流转（申请单 + 发票） |
| [voucher-status.md](voucher-status.md) | 结算凭据状态流转（计划任务链执行） |
| [realname-status.md](realname-status.md) | 实名认证状态流转（个人 / 企业两类认证） |
| [store-apply-status.md](store-apply-status.md) | 开店申请状态流转（审核通过即开通店铺） |
| [product-status.md](product-status.md) | 商品状态流转（审核 / 上下架，SKU 无状态） |
| [lottery-prize-status.md](lottery-prize-status.md) | 抽奖奖品状态流转（待兑奖 → 已兑奖 / 已取消） |
| [redpack-code-status.md](redpack-code-status.md) | 红包码状态流转（领取 + 微信商家转账发放） |
| [suggest-status.md](suggest-status.md) | 意见反馈状态流转（待处理 / 已回复 / 已关闭） |
| [coupon-status.md](coupon-status.md) | 优惠券状态流转（发放 / 核销 / 返还 / 过期清理） |

各文档中的「实现缺口」小节记录了枚举已定义但代码未写入的状态，改动相关模块前请先对照该节确认实际可达状态。

**暂未建文档的枚举：** `App\Enums\Foundation\AliyunDomainStatus` 是阿里云域名查询结果的映射（`AliyunDomain` 由接口数据即时构造，无本地流转）；`App\Enums\BlockChain\ContractDeployStatus` 所属代码已归档至 `docs/archive/blockchain/`，不在应用内。
