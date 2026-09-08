# 意见反馈 — 后端 API 需求文档

> 前端页面已完成开发，当前使用 localStorage mock 数据。以下为后端接口需求，接口路径前缀建议 `/contents`。

---

## 1. 提交反馈

### 请求

```
POST /contents/suggests
```

**需登录**（`auth:sanctum`）

### 请求体

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 是 | 反馈类型，枚举值：`feature`（功能建议）、`bug`（问题反馈）、`account`（账号问题）、`other`（其他） |
| content | string | 是 | 反馈内容，5–500 字 |
| contact | string | 否 | 联系方式（手机号/微信号），最多 100 字 |

### 响应（201）

```json
{
    "suggest_id": 1001,
    "type": {
        "value": "bug",
        "label": "问题反馈",
        "color": "danger"
    },
    "contact": "13800138000",
    "status": {
        "value": "pending",
        "label": "待处理",
        "color": "warning"
    },
    "last_message_at": null,
    "created_at": "2026-09-08T10:30:00.000000Z"
}
```

### 响应字段

| 字段 | 类型 | 说明 |
|------|------|------|
| suggest_id | int | 反馈 ID |
| type | object | 反馈类型枚举 `{value, label, color}` |
| contact | string | 联系方式 |
| status | object | 状态枚举 `{value, label, color}` |
| last_message_at | string | 最后一条消息时间 |
| created_at | string | 提交时间（ISO 8601） |

### 错误响应

| 场景 | code | message |
|------|------|---------|
| 未登录 | 401 | — |
| content 为空 | 422 | 请填写反馈内容 |
| content 少于 5 字 | 422 | 反馈内容至少 5 个字 |
| content 超过 500 字 | 422 | 反馈内容不能超过 500 字 |
| type 不在枚举内 | 422 | 无效的反馈类型 |

---

## 2. 我的反馈列表

### 请求

```
GET /contents/suggests
```

**需登录**（`auth:sanctum`）

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | string | 否 | 按状态筛选，枚举值：`pending`、`resolved`、`closed`；不传返回全部 |
| page | int | 否 | 页码，默认 1 |
| per_page | int | 否 | 每页条数，默认 10，最大 50 |

### 响应

```json
{
    "list": [
        {
            "suggest_id": 1001,
            "type": {
                "value": "bug",
                "label": "问题反馈",
                "color": "danger"
            },
            "contact": "13800138000",
            "status": {
                "value": "pending",
                "label": "待处理",
                "color": "warning"
            },
            "last_message_at": "2026-09-08T10:30:00.000000Z",
            "created_at": "2026-09-08T10:30:00.000000Z"
        }
    ],
    "page": {
        "current_page": 1,
        "total_page": 3,
        "per_page": 10,
        "has_more": true,
        "total": 25
    }
}
```

### 响应字段

| 字段 | 类型 | 说明 |
|------|------|------|
| suggest_id | int | 反馈 ID |
| type | object | 反馈类型枚举 `{value, label, color}` |
| contact | string | 联系方式 |
| status | object | 状态枚举 `{value, label, color}` |
| last_message_at | string | 最后一条消息时间 |
| created_at | string | 提交时间 |

---

## 3. 反馈详情（对话列表）

### 请求

```
GET /contents/suggests/{suggest_id}
```

**需登录**（`auth:sanctum`）

### 路径参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| suggest_id | int | 是 | 反馈 ID |

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码，默认 1 |
| per_page | int | 否 | 每页条数，默认 20，最大 50 |

### 响应

```json
{
    "suggest": {
        "suggest_id": 1001,
        "type": {
            "value": "bug",
            "label": "问题反馈",
            "color": "danger"
        },
        "status": {
            "value": "pending",
            "label": "待处理",
            "color": "warning"
        }
    },
    "messages": [
        {
            "message_id": 1,
            "content": "商品详情页图片加载不出来",
            "is_from_user": true,
            "sender": {
                "user_id": 123,
                "nickname": "张三",
                "avatar": "https://..."
            },
            "created_at": "2026-09-08T10:30:00.000000Z"
        },
        {
            "message_id": 2,
            "content": "已修复，请清除缓存后重试",
            "is_from_user": false,
            "sender": {
                "admin_id": 1,
                "name": "客服小王"
            },
            "created_at": "2026-09-09T08:00:00.000000Z"
        }
    ],
    "page": {
        "current_page": 1,
        "total_page": 1,
        "per_page": 20,
        "has_more": false,
        "total": 2
    }
}
```

### 响应字段

| 字段 | 类型 | 说明 |
|------|------|------|
| message_id | int | 消息 ID |
| content | string | 消息内容 |
| is_from_user | bool | 是否来自用户 |
| sender | object | 发送者信息（根据类型返回不同字段） |
| created_at | string | 消息时间 |

---

## 4. 追加反馈消息

### 请求

```
POST /contents/suggests/{suggest_id}/messages
```

**需登录**（`auth:sanctum`）

### 路径参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| suggest_id | int | 是 | 反馈 ID |

### 请求体

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| content | string | 是 | 消息内容，1–500 字 |

### 状态变化

用户追加消息后，反馈状态自动重置为 `pending`（待处理），提醒管理员有新消息。

### 响应（201）

```json
{
    "message_id": 3,
    "content": "还是不行，已经清除缓存了",
    "is_from_user": true,
    "sender": {
        "user_id": 123,
        "nickname": "张三",
        "avatar": "https://..."
    },
    "created_at": "2026-09-09T10:00:00.000000Z"
}
```

### 错误响应

| 场景 | code | message |
|------|------|---------|
| 反馈不存在 | 404 | 反馈不存在 |
| 非自己的反馈 | 403 | 无权访问此反馈 |
| 反馈已关闭 | 400 | 反馈已关闭，无法继续回复 |
| content 为空 | 422 | 请填写消息内容 |
| content 超过 500 字 | 422 | 消息内容不能超过 500 字 |

---

## 5. 状态枚举

| 值 | 中文标签 | 说明 |
|----|---------|------|
| `pending` | 待处理 | 已提交，待管理员处理；用户追加消息后回到此状态 |
| `resolved` | 已回复 | 管理员已回复（用户继续追加消息则回到 `pending`） |
| `closed` | 已关闭 | 反馈已关闭，无法继续回复 |

---

## 6. 反馈类型枚举

| 值 | 中文标签 |
|----|---------|
| `feature` | 功能建议 |
| `bug` | 问题反馈 |
| `account` | 账号问题 |
| `other` | 其他 |

---

## 7. 前端调用对应关系

| 前端页面 | 调用方法 | 对应接口 |
|---------|---------|---------|
| 提交反馈页 `pages/service/feedback/index` | `feedbackApi.createFeedback({ type, content, contact })` | `POST /contents/suggests` |
| 反馈列表页 `pages/service/feedback/list/index` | `feedbackApi.getFeedbacks({ status, page })` | `GET /contents/suggests` |
| 反馈详情页 `pages/service/feedback/detail/index` | `feedbackApi.getFeedbackDetail(suggestId)` | `GET /contents/suggests/{suggest_id}` |
| 反馈详情页 `pages/service/feedback/detail/index` | `feedbackApi.appendMessage(suggestId, { content })` | `POST /contents/suggests/{suggest_id}/messages` |

---

## 8. 备注

- `type` 和 `status` 字段使用 `EnumResource` 统一输出为 `{value, label, color}` 结构，前端可直接使用。
- `content` 字段已从 `suggests` 表移除，反馈内容仅存储在 `suggest_messages` 表中。
- 列表页支持下拉刷新（重新请求 page=1）和触底加载更多（page++），分页格式与项目其他接口（订单、退款等）保持一致即可。
- 反馈提交后前端会清空表单并 toast 提示"提交成功"，无需后端返回额外数据。
- 对话列表按时间正序排列（最早的消息在前），支持分页加载历史消息。
- 用户追加消息后，反馈状态保持不变；管理员回复后，状态自动变更为 `resolved`。

---

## 9. 开发计划

### 9.1 数据库设计

**迁移文件**: `database/migrations/0002_01_00_000002_create_suggests_table.php`

**表结构**: `suggests`

| 字段 | 类型 | 说明 | 索引 |
|------|------|------|------|
| id | bigint unsigned | 主键 | PRIMARY |
| user_id | bigint unsigned | 用户 ID | INDEX |
| type | varchar(20) | 反馈类型（枚举） | INDEX |
| contact | varchar(100) | 联系方式 | - |
| status | varchar(20) | 状态（枚举） | INDEX |
| created_at | timestamp | 创建时间 | - |
| updated_at | timestamp | 更新时间 | - |

**索引**:
- `INDEX user_id, status, created_at`
- `INDEX status, created_at`
- `INDEX type`

**外键约束**:
- `user_id` → `users.id` (CASCADE DELETE)

---

**表结构**: `suggest_messages`

| 字段 | 类型 | 说明 | 索引 |
|------|------|------|------|
| id | bigint unsigned | 主键 | PRIMARY |
| suggest_id | bigint unsigned | 反馈 ID | INDEX |
| sender_type | varchar(255) | 发送者类型（User/Administrator） | INDEX |
| sender_id | bigint unsigned | 发送者 ID | INDEX |
| content | text | 消息内容 | - |
| created_at | timestamp | 创建时间 | - |
| updated_at | timestamp | 更新时间 | - |

**索引**:
- `INDEX created_at`

**外键约束**:
- `suggest_id` → `suggests.id` (CASCADE DELETE)

**说明**:
- 使用多态关联 `sender`，支持 User 和 Administrator 两种发送者
- 第一条消息（用户提交反馈时）自动创建，`sender` 为当前用户
- 管理员回复时，`sender` 为当前管理员

---

### 9.2 枚举类

**文件**: `app/Enums/Content/SuggestType.php`

```php
enum SuggestType: string implements HasColor, HasLabel
{
    case Feature = 'feature';
    case Bug = 'bug';
    case Account = 'account';
    case Other = 'other';
    
    // getLabel(): 功能建议/问题反馈/账号问题/其他
    // getColor(): primary/danger/warning/gray
}
```

**文件**: `app/Enums/Content/SuggestStatus.php`

```php
enum SuggestStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Closed = 'closed';
    
    // getLabel(): 待处理/已回复/已关闭
    // getColor(): warning/success/gray
}
```

---

### 9.3 模型

**文件**: `app/Models/Content/Suggest.php`

**Trait**:
- `BelongsToUser`: 用户关联
- `HasEasyStatus`: 状态管理

**属性**:
- `$casts`: `type` → `SuggestType::class`, `status` → `SuggestStatus::class`

**关联关系**:
- `user()`: BelongsTo (User)
- `messages()`: HasMany (SuggestMessage) - 对话消息列表

**访问器**:
- `last_message_at`: 最后一条消息时间（用于排序）

---

**文件**: `app/Models/Content/SuggestMessage.php`

**Trait**:
- 无

**属性**:
- `$casts`: 无特殊转换

**关联关系**:
- `suggest()`: BelongsTo (Suggest)
- `sender()`: MorphTo - 多态关联，支持 User 和 Administrator

**访问器**:
- `is_from_user`: 是否来自用户（`sender instanceof User`）
- `is_from_admin`: 是否来自管理员（`sender instanceof Administrator`）

---

### 9.4 API Resource

**文件**: `app/Http/Resources/Content/SuggestResource.php`

使用 `EnumResource` 统一处理枚举字段。

**字段映射**:
- `suggest_id` → `id`
- `type` → `EnumResource::make(type)` → `{value, label, color}`
- `contact` → `contact`
- `status` → `EnumResource::make(status)` → `{value, label, color}`
- `last_message_at` → `last_message_at`
- `created_at` → `created_at`

**文件**: `app/Http/Resources/Content/SuggestCollection.php`

继承 `AnonymousResourceCollection`，处理分页数据。

---

**文件**: `app/Http/Resources/Content/SuggestMessageResource.php`

**字段映射**:
- `message_id` → `id`
- `content` → `content`
- `is_from_user` → `$this->is_from_user`（访问器）
- `sender` → 根据 `sender` 类型返回不同结构：
  - User: `{ user_id, nickname, avatar }`
  - Administrator: `{ admin_id, name }`
- `created_at` → `created_at`

**文件**: `app/Http/Resources/Content/SuggestMessageCollection.php`

继承 `AnonymousResourceCollection`，处理分页数据。

---

### 9.5 FormRequest

**文件**: `app/Http/Requests/Content/StoreSuggestRequest.php`

**验证规则**:
- `type`: `required|in:feature,bug,account,other`
- `content`: `required|string|min:5|max:500`
- `contact`: `nullable|string|max:100`

**自定义错误消息**:
- type.in → "无效的反馈类型"
- content.required → "请填写反馈内容"
- content.min → "反馈内容至少 5 个字"
- content.max → "反馈内容不能超过 500 字"

---

**文件**: `app/Http/Requests/Content/IndexSuggestRequest.php`

**验证规则**:
- `status`: `nullable|in:pending,resolved,closed`
- `page`: `nullable|integer|min:1`
- `per_page`: `nullable|integer|min:1|max:50`

---

**文件**: `app/Http/Requests/Content/StoreSuggestMessageRequest.php`

**验证规则**:
- `content`: `required|string|min:1|max:500`

**自定义错误消息**:
- content.required → "请填写消息内容"
- content.max → "消息内容不能超过 500 字"

---

### 9.6 Controller

**文件**: `app/Http/Controllers/Content/SuggestController.php`

**方法**:

1. `store(StoreSuggestRequest $request): JsonResponse`
   - 创建反馈记录（不含 content）
   - 创建第一条消息（用户提交的内容）
   - 返回 201 + SuggestResource

2. `index(IndexSuggestRequest $request): JsonResponse`
   - 查询当前用户的反馈列表
   - 支持按 status 筛选
   - 按 `updated_at` 降序排序
   - 分页返回 SuggestCollection

3. `messages(Request $request, Suggest $suggest): JsonResponse`
   - 查询指定反馈的消息列表
   - 验证反馈归属（只能查看自己的反馈）
   - 分页返回 SuggestMessageCollection

4. `storeMessage(StoreSuggestMessageRequest $request, Suggest $suggest): JsonResponse`
   - 追加消息到反馈
   - 验证反馈归属（只能在自己的反馈中追加）
   - 验证反馈状态（已关闭的反馈不能追加）
   - 创建消息，`sender` 为当前用户
   - 返回 201 + SuggestMessageResource

---

### 9.7 路由

**文件**: `routes/apis/content.php`

**新增路由**:
```php
// ---- 意见反馈 ----

// 提交反馈 (需登录)
$router->post('suggests', [SuggestController::class, 'store'])
    ->middleware('auth:sanctum');

// 我的反馈列表 (需登录)
$router->get('suggests', [SuggestController::class, 'index'])
    ->middleware('auth:sanctum');

// 反馈详情（对话列表）(需登录)
$router->get('suggests/{suggest}', [SuggestController::class, 'messages'])
    ->middleware('auth:sanctum')
    ->whereNumber('suggest');

// 追加反馈消息 (需登录)
$router->post('suggests/{suggest}/messages', [SuggestController::class, 'storeMessage'])
    ->middleware('auth:sanctum')
    ->whereNumber('suggest');
```

---

### 9.8 测试

**文件**: `tests/Feature/Content/SuggestTest.php`

**测试用例**:

1. **提交反馈**
   - 成功提交反馈（完整数据）
   - 成功提交反馈（无联系方式）
   - 提交后自动创建第一条消息
   - 未登录提交反馈（401）
   - content 为空（422）
   - content 少于 5 字（422）
   - content 超过 500 字（422）
   - type 不在枚举内（422）

2. **反馈列表**
   - 成功获取列表（无筛选）
   - 成功获取列表（按状态筛选）
   - 仅显示自己的反馈
   - 未登录获取列表（401）
   - 无效的状态值（422）

3. **反馈详情（对话列表）**
   - 成功获取对话列表
   - 未登录获取详情（401）
   - 获取不存在的反馈（404）
   - 获取他人的反馈（403）

4. **追加反馈消息**
   - 成功追加消息
   - 未登录追加消息（401）
   - 追加到不存在的反馈（404）
   - 追加到他人的反馈（403）
   - 追加到已关闭的反馈（400）
   - content 为空（422）
   - content 超过 500 字（422）

---

### 9.9 Filament Backend 资源管理

**文件**: `app/Filament/Backend/Clusters/Content/Resources/Suggests/SuggestResource.php`

**导航**:
- `$cluster`: `ContentCluster::class`
- `$navigationIcon`: `Heroicon::OutlinedChatBubbleBottomCenterText`
- `$navigationGroup`: '内容'
- `$navigationLabel`: '意见反馈'
- `$modelLabel`: '反馈'
- `$pluralModelLabel`: '反馈'

**列表页** (`Pages/ListSuggests.php`):

**表格列**:
- `id`: ID
- `user.name`: 用户
- `type`: 类型（带颜色标签）
- `contact`: 联系方式
- `status`: 状态（带颜色标签）
- `messages_count`: 消息数量（统计字段）
- `updated_at`: 最后消息时间
- `created_at`: 提交时间

**表格筛选**:
- `SelectFilter::make('type')`: 按类型筛选
- `SelectFilter::make('status')`: 按状态筛选

**表格排序**:
- 默认按 `updated_at` 降序

**表格操作**:
- `ViewAction`: 查看详情（跳转到查看页）
- `EditAction`: 编辑状态

---

**查看页** (`Pages/ViewSuggest.php`):

**头部操作**:
- `EditAction`: 编辑

---

**编辑页** (`Pages/EditSuggest.php`):

**表单字段**:
- `Section::make('反馈信息')`:
  - `TextInput::make('type')`: 类型（只读，显示中文标签）
  - `TextInput::make('contact')`: 联系方式（只读）
  - `TextInput::make('created_at')`: 提交时间（只读）

- `Section::make('状态管理')`:
  - `Select::make('status')`: 状态（pending/resolved/closed）

**页面**:
- `ListSuggests`: 列表页
- `ViewSuggest`: 查看页
- `EditSuggest`: 编辑页（仅修改状态）

**权限**:
- Policy: `app/Policies/Content/SuggestPolicy.php`
- 仅 backend guard 的管理员可访问

---

### 9.10 开发顺序

1. 创建迁移文件 → `php artisan migrate`
   - `create_suggests_table`
   - `create_suggest_messages_table`
2. 创建枚举类 → `SuggestType`, `SuggestStatus`
3. 创建模型 → `Suggest`, `SuggestMessage`
4. 创建 Policy → `SuggestPolicy`
5. 创建 API Resource → `SuggestResource`, `SuggestCollection`, `SuggestMessageResource`, `SuggestMessageCollection`
6. 创建 FormRequest → `StoreSuggestRequest`, `IndexSuggestRequest`, `StoreSuggestMessageRequest`
7. 创建 Controller → `SuggestController`
8. 添加路由 → `routes/apis/content.php`
9. 创建 Filament Resource → `SuggestResource` (Backend/Clusters/Content)
10. 编写测试 → `tests/Feature/Content/SuggestTest.php`
11. 运行测试 → `php artisan test --compact tests/Feature/Content/SuggestTest.php`
12. 代码格式化 → `vendor/bin/pint --dirty --format agent`
