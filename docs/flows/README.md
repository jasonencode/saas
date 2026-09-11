# 流程图文档

业务流程与状态机流转图，配套源码位置见各文档开头说明。

**命名规则：** `<模块>-<主题>.md`，kebab-case，不带 `-flow` 后缀（文件夹名已表达流程含义），如 `order-status.md`。

| 文档 | 说明 |
|------|------|
| [order-status.md](order-status.md) | 商城订单状态流转（按履约方式分 mail / pickup / virtual 三条链路） |
| [refund-status.md](refund-status.md) | 退款状态流转（仅退款 / 退货退款两条链路） |
