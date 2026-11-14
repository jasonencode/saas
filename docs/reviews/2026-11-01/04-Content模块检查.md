# Content 模块 检查报告

- **检查日期**：2026-11-01
- **检查范围**：`app/Models/Content/*`、`app/Services/Content/*`、Content 的 Controller / Request / Resource、`app/Policies/Content/*`、`app/Filament/Tenant/Clusters/Content/**`、`routes/apis/content.php`、Content 相关迁移
- **检查方式**：逐文件静态走读 + 关键查询/资源验证
- **结论**：模型与后台结构完整（内容/分类/标签/单页/评论/反馈/通知/敏感词），但 **API 层几乎没有租户与发布态约束**，评论缺少审核与敏感词过滤，分类树与反馈列表存在明显 N+1。本期共 **7 项**：高 2 / 中 3 / 低 2。

---

## 一、模块概览

| 子域 | 主要文件 | 现状 |
|---|---|---|
| 内容/分类/标签/单页 | `app/Models/Content/*`、`ContentController`、`CategoryController`、`TagController`、`SinglePageController` | 模型完备；API 无租户收窄，单页 slug 无唯一约束 |
| 评论 | `CommentController`、`Comment`、`HasComments` | 无父子结构、无审核、无敏感词过滤 |
| 反馈（Suggest） | `SuggestService`、`Suggest`、`SuggestMessage` | 列表/详情可用；写入无事务，列表 N+1 |
| 通知/敏感词 | `Notification`、`Sensitive`、`SensitiveService` | 敏感词服务存在但评论未接入 |
| 后台 | `app/Filament/Tenant/Clusters/Content/**` | 资源/表单/表格齐全；统计不含租户口径等见后 |

---

## 二、问题清单

### CONT-01 ｜ 高 ｜ 安全 / 租户隔离
**Content API 全部未按租户过滤，可跨租户读取文章/单页/分类/标签**

- **位置**：`app/Http/Controllers/Content/ContentController.php:25-38`、`TagController.php:20-21`、`SinglePageController.php:34-38`、`CategoryController`
- **代码**：
  ```php
  $content = Content::ofEnabled()
      ->with('tags')
      ->when($request->input('tag_id'), ...)
      ->paginate(...);
  ```
  ```php
  $tags = ContentTag::withCount('contents')->get();
  ```
- **失败场景**：`Content` / `SinglePage` / `ContentCategory` / `ContentTag` 都带 `tenant_id`，但 Content API 没有任何一处按 `X-Tenant-Id` 或用户授权租户收窄。A 租户的 H5 调用 `/api/contents` 会拿到 B、C 租户的文章（真实 ID、封面、正文），`/api/contents/{id}` 可直接枚举；`/api/contents/tags` 把全平台标签合并，`contents_count` 互相污染。对比 `CouponController::effectiveTenantIds()` 已实现租户收窄，说明这里是遗漏。
- **建议**：统一注入 `TenantResolver::current()`，无租户头返回 400 或空集；封装 `scopeOfCurrentTenant()`；补「带/不带 X-Tenant-Id」两组 Feature 测试。

### CONT-02 ｜ 高 ｜ 数据一致性 / 越权
**`single_pages` 无 `unique(tenant_id, slug)`，后台唯一校验却是全局的**

- **位置**：`database/migrations/0002_01_00_000001_create_contents_tables.php:86-110`、`SinglePageForm.php:35-38`、`SinglePageController.php:34-38`
- **代码**：
  ```php
  $table->string('slug')->comment('别名');
  ...
  $table->index(['tenant_id', 'status', 'sort']);
  ```
  ```php
  $singlePage = SinglePage::where('slug', $slug)->ofEnabled()->firstOrFail();
  ```
- **失败场景**：只有普通索引，无租户级唯一约束。后台 `->unique(ignoreRecord: true)` 是**全局唯一**，会误拦其他租户同名 slug；而 API 查询不看租户，`firstOrFail()` 可能命中任意租户的页面。A、B 都建 `about` 后，A 的用户可能看到 B 的「关于我们」。
- **建议**：迁移加 `unique(['tenant_id','slug'])`；表单用 `->scopedUnique()`；API 查询加 `where('tenant_id', ...)`。

### CONT-03 ｜ 中 ｜ 内容安全 / 审核缺失
**评论正文未接入敏感词过滤，`status` 硬编码为 true，图片可提交任意外链**

- **位置**：`app/Http/Controllers/Content/CommentController.php:55-61`、`app/Http/Requests/Content/StoreCommentRequest.php:16-21`
- **代码**：
  ```php
  $comment = $content->comments()->create([
      'user_id' => $request->user()->id,
      'content' => $request->safe()->string('content'),
      'star' => $request->safe()->integer('star', 0),
      'pictures' => $request->safe()->array('pictures', []),
      'status' => true,
  ]);
  ```
- **失败场景**：项目已有 `SensitiveService`（昵称、地址在用），评论正文完全没接入，也没有先审后发；`pictures.*` 只校验 `string|max:500`，可提交 `javascript:` 或外站图片地址，前端直接渲染即存储型 XSS/盗链；`star` 规则 `min:1`，控制器默认 `0`，产生规则外脏数据。
- **建议**：写入前 `SensitiveService::filter()` 或命中敏感词转 `status=false`；`pictures.*` 用 `url` + 域名白名单；统一 `star` 默认语义。

### CONT-04 ｜ 中 ｜ 设计缺失（评论树 / 计数器）
**评论表不支持回复层级，评分/评论数无冗余字段**

- **位置**：`database/migrations/0002_02_00_000001_create_comments_table.php:13-30`、`CommentController.php:31-35`
- **失败场景**：评论表没有 `parent_id`/`root_id`/`reply_to_user_id`，控制器也不接收父级，「回复」在产品层面不存在；`star` 无聚合回 `contents`（表里无 `rating`/`comments_count`），列表要展示评分/评论数只能每行 `count()`，多租户列表必然 N+1；`Comment` 无 `tenant_id`，只能通过 `commentable` 间接归属。
- **建议**：补 `parent_id`/`root_id` 与 `(commentable_type, commentable_id, parent_id)` 索引；`Content` 增加 `comments_count`/`rating_avg` 冗余列并在评论事件维护；短期不做回复则在 API 契约中明确「扁平评论」。

### CONT-05 ｜ 中 ｜ 性能（N+1 + 递归）
**分类树序列化每层 2 条查询**

- **位置**：`app/Http/Resources/Content/CategoryResource.php:21-23`
- **代码**：
  ```php
  'children' => $this->when($this->resource->children()->ofEnabled()->exists(), function () {
      return CategoryResource::collection($this->resource->children()->ofEnabled()->get());
  }),
  ```
- **失败场景**：`CategoryController@index` 一次 `get()` 后逐条序列化，每个分类固定 2 条查询（`exists` + `get`）并递归子分类；100 个分类（3 级）就是 150~200 条 SQL。公开接口极易被打，且 `exists()` 与 `get()` 之间并发修改会出现「有 children 字段但内容为空」。
- **建议**：一次取全量分类，用项目已有 `list2tree()` 内存组树；删除多余 `exists()`。

### CONT-06 ｜ 中 ｜ 性能 / 事务
**反馈列表逐行 `latest()->value()`，创建无事务**

- **位置**：`app/Models/Content/Suggest.php:39-43`、`SuggestResource.php:20`、`SuggestService.php:27-36`
- **代码**：
  ```php
  public function getLastMessageAtAttribute(): ?Carbon
  {
      return $this->messages()->latest('created_at')->value('created_at');
  }
  ```
- **失败场景**：`SuggestController@index` 已 `with('messages')`，访问器仍逐行发 SQL（一页 20 条 = 20 次额外查询）；`store()` 先建 `suggest` 再建首条 `message`，无事务，消息写入失败会留下「内容为空的反馈」。
- **建议**：列表用 `withMax('messages','created_at')`；`store()` 包 `DB::transaction`。

### CONT-07 ｜ 低 ｜ 统计可信度 / 死代码
**`views` 可被刷、又可后台手改；`TrackVisitService` 无人调用且 key 不含模型类型**

- **位置**：`ContentController.php:51`、`ContentForm.php:96-99`、`app/Services/Content/TrackVisitService.php:27-31`
- **代码**：
  ```php
  $content->increment('views');
  ```
  ```php
  return $this->keyPrefix.$model->getKey();   // 不含 morphClass
  ```
- **失败场景**：`views` 无去重/频控，脚本循环即可刷到任意值；后台表单又把 `views` 做成可编辑字段，口径不可信。`TrackVisitService`（Redis HLL）全仓无调用，且 key 为 `unique_visits:{id}`，`Content#1` 与 `SinglePage#1` 会共用同一个 HLL。
- **建议**：接入 `TrackVisitService`，key 改 `unique_visits:{morphClass}:{id}`，或 `Cache::add` 做 24h 去重；`views` 从表单移除。

---

## 三、后台（Tenant/Content）观察

- 资源结构统一（`Resources/<名称>/{Schemas,Tables,Pages}`），表单/表格风格与 Mall/Finance 一致。
- `Contents` 的发布态、置顶、标签关联完整；`SinglePages` 的 slug 唯一性问题见 CONT-02。
- `Comments` 后台可审核，但 API 侧默认 `status=true`（CONT-03），后台审核实际上是「事后补救」。
- `ContentStatsWidget` 等统计直接 `Content::whereDate(...)->count()`，由于 Filament 资源模型带租户作用域，此处在租户面板内是正确的；但 `Comment` 不是资源模型，统计会跨租户（与 Campaign 的 CAMP-12 同类）。

---

## 补充：App 版本接口（`/app_version`）

- **位置**：`app/Http/Controllers/System/AppVersionController.php:20-70`、`app/Http/Requests/VersionRequest.php`
- **问题**：
  1. 版本排序使用 PostgreSQL 专用 `split_part(version,'.',n)::int`（`:48-52`），SQLite/MySQL 下直接报错；现有测试只覆盖 `AppVersionService`，接口本身没有回归。
  2. `select([... 'version', ..., 'version', ...])` 重复列（`:26-28`）。
  3. `where('publish_at','<',now())` 把 `publish_at` 为 null 的「草稿版本」永久排除；后台若只填版本不设置发布时间，接口永远查不到，行为不直观。
  4. `version_compare($version, $app->version, '<')` 对客户端传入的任意字符串做比较，`version` 仅 `required` 未限制格式（如 `abc`），结果不可预期。
- **建议**：版本号正则校验（`^\d+(\.\d+){0,3}$`），排序改为「应用层解析 + `sort` 字段」或使用跨库的 `string_to_array` 替代；补齐接口测试；明确 `publish_at=null` 的语义。

---

## 四、修复优先级

1. **P0**：CONT-01（跨租户内容泄露）、CONT-02（slug 串页）。
2. **P1**：CONT-03（评论审核/敏感词/XSS）。
3. **P2**：CONT-05（公开接口 N+1）、CONT-06（反馈事务/N+1）。
4. **P3**：CONT-04（评论树）、CONT-07（浏览量口径）。
