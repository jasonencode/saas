# Content 模块 检查报告

- **检查日期**：2026-11-14
- **检查范围**：`app/Models/Content/**`、`app/Services/Content/**`、`app/Http/{Controllers,Requests,Resources}/Content/**`、`app/Policies/Content/**`、`routes/apis/content.php`、Content 相关迁移、`app/Filament/**` 中 Content 资源/表单/表格、`tests/Feature/Content/**`
- **检查方式**：逐文件走读 + 迁移列宽与校验规则交叉核对 + 测试断言反向验证
- **结论**：Content 的**租户隔离仍然完全缺失**（API 与后台共用同一批全平台数据）；评论表列宽与校验规则不一致，PostgreSQL 下 256～2000 字评论必现 500；评论先发后审、可刷浏览量、分类树 N+1 等旧问题原样存在。现有测试反而把「跨租户同列表」「star=0」等错误行为固化为断言。

**统计：严重 1 / 高 3 / 中 5 / 低 1（共 10 条）。**

---

## 一、模块概览

| 子域 | 现状 | 主要风险 |
|---|---|---|
| 内容/分类/标签 | 内容、分类、标签、单页齐全，后台双面板 | API 无租户过滤，跨租户读写 |
| 评论 | 写入即 `status=true`，后台仅能删除 | 无审核/敏感词，垃圾评论直接可见 |
| 单页 | `slug` 查询 + 后台表单唯一 | 无 `(tenant_id, slug)` 唯一，可能串租户 |
| 反馈 | Suggest + 消息 | 列表 N+1、无事务、时间口径错 |

---

## 二、严重问题

### CONT-01 ｜ 严重 ｜ 租户隔离

**内容/分类/标签/单页/评论 API 全链路无租户过滤**

- **位置**：`app/Http/Controllers/Content/ContentController.php:25-33`、`TagController.php:20-21`、`SinglePageController.php:21-22,36-38`、`CategoryController.php:21`、`CommentController.php:31-35`
- **代码要点**：`Content::ofEnabled()->with('tags')->paginate()`、`ContentTag::withCount('contents')->get()`、`SinglePage::where('slug', $slug)->firstOrFail()` 均无租户条件；全模块未引用 `TenantResolver`。
- **失败场景**：A 租户 H5 可拿到 B/C 租户文章正文与封面；`/api/contents/tags` 的计数是跨租户合计；`/single-pages/about` 可能返回其他租户页面。
- **修复建议**：路由/控制器注入 `TenantResolver::current()`，无租户头返回空集或 400；查询统一 `->ofTenant()`；把现有「多租户同列表」测试改为「互不可见」回归。

---

## 三、高优先级问题

### CONT-02 ｜ 高 ｜ 唯一约束

**`single_pages.slug` 无租户内唯一约束，后台却全局唯一**

- **位置**：`database/migrations/0002_01_00_000001_create_contents_tables.php:97-108`、`app/Filament/Forms/Content/SinglePageForm.php:36-39`、`SinglePageController.php:36-38`
- **失败场景**：A 租户占用 `about` 后 B 租户无法创建；绕过表单后同 slug 多条，API 随机命中其他租户页面。
- **修复建议**：迁移补 `unique(['tenant_id','slug'])`；表单改 `scopedUnique()`；API 查询补租户条件。

### CONT-03 ｜ 高 ｜ 列宽 / PostgreSQL

**评论正文列宽 255 与校验 2000 不一致，PG 必现 500**

- **位置**：`database/migrations/0002_02_00_000001_create_comments_table.php:22-24`、`app/Http/Requests/Content/StoreCommentRequest.php:17`
- **失败场景**：256～2000 字评论触发 `value too long for character varying(255)`（SQLSTATE 22001）→ 500；SQLite 不校验长度，测试只测 2001 字，故全绿。
- **修复建议**：列改 `text`；补 256/1000 字用例并在 PostgreSQL 验证。

### CONT-04 ｜ 高 ｜ 内容安全

**评论无审核、无敏感词，`status` 硬编码 true**

- **位置**：`app/Http/Controllers/Content/CommentController.php:55-61`、`app/Filament/Backend/Clusters/Content/Resources/Comments/Tables/CommentsTable.php:32-36`、`app/Services/System/SensitiveService.php:31`
- **失败场景**：广告/辱骂评论立即对 H5 可见；`star` 未传时落 0（规则 `min:1`）；`pictures.*` 仅字符串校验，可塞外链。
- **修复建议**：写库前走 `SensitiveService`，命中置 `status=false`；后台补启停动作；`pictures.*` 校验 URL/域名；`star` 未传存 null。

---

## 四、中优先级问题

### CONT-05 ｜ 中 ｜ 租户 / 运营

**评论无租户归属，租户后台无法管理；灌水仅靠全局限流**

- **位置**：评论迁移、`app/Models/Content/Comment.php:17-22`、`routes/apis/content.php:35-37`
- **失败场景**：租户运营看不到/管不了自家评论；`lock:content_comment` 不是限流（`finally` 释放），同一用户仍可每分钟 60 条。
- **修复建议**：评论补 `tenant_id`（或按 `commentable` 派生作用域）；租户面板加评论资源；写接口挂独立 RateLimiter + 去重。

### CONT-06 ｜ 中 ｜ 入参 / 500

**负 `per_page` 与非数字 `tag_id` 会 500**

- **位置**：`ContentController.php:27-33`、`CommentController.php:35`、`SinglePageController.php:22`
- **失败场景**：`per_page=-1` → PostgreSQL `LIMIT must not be negative`；`tag_id=abc` → 闭包 `int` 形参 TypeError（500）。
- **修复建议**：FormRequest 校验 `per_page:integer|min:1|max:50`、`tag_id/category_id:integer|exists`。

### CONT-07 ｜ 中 ｜ 性能

**分类树序列化每节点 2 条 SQL 且递归**

- **位置**：`app/Http/Resources/Content/CategoryResource.php:21-23`
- **失败场景**：100 个三级分类约 150～200 条 SQL；`exists()` 与 `get()` 之间并发改状态会出现「有 children 字段但内容为空」。
- **修复建议**：一次取全量，用 `list2tree()` 内存建树。

### CONT-08 ｜ 中 ｜ 性能 / 一致性

**反馈列表逐行 SQL 算最后消息；`store()` 无事务；后台时间口径错误**

- **位置**：`SuggestController.php:55-57`、`app/Models/Content/Suggest.php:39-43`、`SuggestService.php:25-37,54-56`、`SuggestsTable.php:33-36`
- **失败场景**：一页 20 条反馈多 20 次查询并把全部消息载入内存；消息写入失败留下空反馈；追加消息不 `touch()`，后台排序不刷新。
- **修复建议**：`withMax('messages','created_at')`；`store()` 包事务；追加消息 `touch()` 或后台改用 `MAX()`。

### CONT-09 ｜ 中 ｜ 数据可信度

**`views` 可刷可手改；`TrackVisitService` 仍是死代码且 key 不含模型类型**

- **位置**：`ContentController.php:51`、两侧 `ContentForm.php` 与 `SinglePageForm.php`、`TrackVisitService.php:27-30`
- **失败场景**：循环 GET 即可刷曝光；`Content#1` 与 `SinglePage#1` 共用 HLL key；运营可直接改浏览量。
- **修复建议**：接入 `TrackVisitService` 且 key 含 morphClass，或 Cache 去重；`views` 从表单移除。

---

## 五、低优先级问题

### CONT-10 ｜ 低 ｜ 分页 / 版本接口

- 内容/单页列表分页无 `ORDER BY`，PG 大表翻页可能重复/漏行；App 版本接口仍用 PG 专用 `split_part`、重复 `version` 列、`publish_at=null` 被排除、版本号无格式校验。

---

## 六、旧问题状态（2026-11-01 → 2026-11-14）

| 旧编号 | 状态 | 说明 |
|---|---|---|
| CONT-01 无租户隔离 | 仍存在 | 测试仍固化跨租户同列表 |
| CONT-02 单页 slug 唯一 | 仍存在 | 迁移与表单均未改 |
| CONT-03 评论审核/敏感词 | 仍存在 | `status=true`、`star=0`、图片外链 |
| CONT-04 评论无 tenant/层级 | 仍存在 | 表结构未变 |
| CONT-05 分类树 N+1 | 仍存在 | `exists()+get()` |
| CONT-06 反馈 N+1/无事务 | 仍存在 | 另发现后台时间口径错误 |
| CONT-07 浏览量可刷/死代码 | 仍存在 | `TrackVisitService` 无调用 |
| 版本接口 4 项 | 仍存在 | 见 CONT-10 |

---

## 七、修复优先级

1. **P0**：CONT-01（跨租户数据）、CONT-03（PG 500）。
2. **P1**：CONT-02（slug 冲突/串页）、CONT-04（内容安全）。
3. **P2**：CONT-05/06/07/08。
4. **P3**：CONT-09/10。
