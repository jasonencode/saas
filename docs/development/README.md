# 开发文档

功能设计、技术方案与分析评估文档。

**命名规则：** `<模块>-<主题>.md`，kebab-case，模块前缀用名词（如 `refund-`、`identity-`），API 类文档以 `-api` 结尾，不使用动词开头或面板名做前缀。

| 文档 | 说明 |
|------|------|
| [coupon-system.md](coupon-system.md) | 优惠券系统方案（现状盘点 + 核销链路） |
| [fulfillment-type.md](fulfillment-type.md) | 商品履约方式（FulfillmentType）方案 |
| [identity-discount.md](identity-discount.md) | 商品身份折扣功能设计 |
| [model-caching-analysis.md](model-caching-analysis.md) | Eloquent 模型缓存分析 |
| [refund-permissions.md](refund-permissions.md) | 退款权限矩阵 |
| [refund-service-api.md](refund-service-api.md) | RefundService API 文档 |
| [schedule-log.md](schedule-log.md) | 计划任务执行日志方案（事件采集 + Filament 展示） |
| [searchable-trait.md](searchable-trait.md) | Searchable Trait 使用说明 |
| [seckill-system.md](seckill-system.md) | 秒杀系统设计（名额、场次与订单链路） |
