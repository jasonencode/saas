# Git Commit 规范

## 1. Commit 消息格式

```
<类型>(<范围>): <描述>

[可选的正文]

[可选的页脚]
```

## 2. 类型说明

| 类型 | 描述 | 示例 |
|------|------|------|
| feat | 新功能 | `feat(auth): 添加登录验证功能` |
| fix | 修复 bug | `fix(user): 修复用户头像上传失败问题` |
| docs | 文档更新 | `docs(readme): 更新项目 README.md` |
| style | 代码风格 | `style: 统一代码缩进为 4 空格` |
| refactor | 代码重构 | `refactor(api): 重构 API 响应格式` |
| perf | 性能优化 | `perf(database): 优化数据库查询性能` |
| test | 测试相关 | `test: 添加用户注册功能测试` |
| build | 构建相关 | `build: 更新依赖版本` |
| ci | CI/CD 配置 | `ci: 优化 GitHub Actions 配置` |
| chore | 其他改动 | `chore: 清理临时文件` |

## 3. 范围说明

- 范围应该是具体的模块或文件，如：
  - `auth` - 认证模块
  - `user` - 用户模块
  - `api` - API 接口
  - `database` - 数据库
  - `frontend` - 前端
  - `backend` - 后端
  - `finance` - 财务模块
  - `invoice` - 发票模块

## 4. 描述规范

- 简短：不超过 50 个字符
- 清晰：准确描述改动内容
- 主动语态：使用动词开头
- 首字母小写：不需要大写开头
- 不加句号：结尾不需要句号

## 5. 最佳实践

### 5.1 提交频率

- **小而频繁**：每次提交只包含一个逻辑改动
- **原子性**：确保每个提交都是独立的、可测试的
- **避免混合**：不要在一个提交中混合不同类型的改动

### 5.2 提交内容

- **只提交必要文件**：不要提交临时文件、日志文件、依赖文件
- **保持代码整洁**：提交前运行代码格式化工具
- **运行测试**：确保提交的代码通过测试

### 5.3 分支管理

- **主分支**：`main` 或 `master` - 稳定版本
- **开发分支**：`develop` - 开发中的版本
- **特性分支**：`feat/feature-name` - 新功能开发
- **修复分支**：`fix/bug-name` - bug 修复
- **发布分支**：`release/version` - 版本发布
- **热修复分支**：`hotfix/issue-name` - 紧急修复

## 6. 提交消息示例

### 好的示例：
- `feat(auth): 添加邮箱验证码登录功能`
- `fix(user): 修复用户信息更新失败问题`
- `docs(api): 更新 API 文档`
- `refactor(database): 优化数据库查询逻辑`
- `perf(frontend): 优化首页加载速度`
- `feat(invoice): 添加发票申请提交事件`
- `fix(finance): 修复发票状态枚举转换错误`

### 不好的示例：
- `update` - 过于模糊
- `修复bug` - 缺少范围和具体描述
- `添加功能并修复问题` - 混合多个改动
- `feat: 添加了一个非常重要的功能，这个功能可以让用户更好地使用系统` - 描述过长

## 7. 工作流程

### 7.1 标准工作流程

1. **创建分支**：`git checkout -b feat/feature-name`
2. **进行开发**：编写代码、测试
3. **提交更改**：`git add .` 和 `git commit -m "feat(scope): 描述"`
4. **推送分支**：`git push origin feat/feature-name`
5. **创建 PR**：在 GitHub/GitLab 上创建 Pull Request
6. **代码审查**：团队成员审查代码
7. **合并分支**：通过审查后合并到 develop 分支
8. **删除分支**：`git branch -d feat/feature-name`

### 7.2 发布流程

1. **创建发布分支**：`git checkout -b release/1.0.0`
2. **版本号更新**：更新 package.json、composer.json 等
3. **测试**：运行完整测试套件
4. **提交更改**：`git commit -m "build: 发布版本 1.0.0"`
5. **合并到 main**：将发布分支合并到 main 分支
6. **创建标签**：`git tag v1.0.0`
7. **推送标签**：`git push origin v1.0.0`
8. **合并到 develop**：将发布分支合并回 develop 分支

### 7.3 热修复流程

1. **创建热修复分支**：`git checkout -b hotfix/issue-name main`
2. **修复问题**：编写修复代码
3. **测试**：验证修复是否有效
4. **提交更改**：`git commit -m "fix(scope): 修复问题"`
5. **合并到 main**：将热修复分支合并到 main 分支
6. **创建标签**：`git tag v1.0.1`
7. **推送标签**：`git push origin v1.0.1`
8. **合并到 develop**：将热修复分支合并回 develop 分支

## 8. 常见命令

### 8.1 基础命令

```bash
# 初始化仓库
git init

# 克隆仓库
git clone <url>

# 查看状态
git status

# 添加文件
git add <file>        # 添加单个文件
git add .              # 添加所有文件

# 提交更改
git commit -m "消息"

# 推送更改
git push

# 拉取更改
git pull
```

### 8.2 分支命令

```bash
# 查看分支
git branch

# 创建分支
git branch <name>

# 切换分支
git checkout <name>

# 创建并切换
git checkout -b <name>

# 合并分支
git merge <name>

# 删除分支
git branch -d <name>
```

### 8.3 历史命令

```bash
# 查看历史
git log

# 查看差异
git diff

# 撤销更改
git checkout -- <file>

# 重置提交
git reset <commit>

# 恢复提交
git revert <commit>
```

## 9. 常见问题解决

### 9.1 提交错误

**误提交文件**：
```bash
# 撤销上次提交但保留更改
git reset --soft HEAD~1

# 从暂存区移除文件
git reset HEAD <file>

# 重新提交
git commit -m "正确的提交消息"
```

**提交消息错误**：
```bash
# 修改上次提交消息
git commit --amend -m "新的提交消息"
```

### 9.2 分支冲突

1. 手动编辑冲突文件
2. 标记冲突已解决：`git add <file>`
3. 完成合并：`git commit`

## 10. Git 配置

```bash
# 配置用户名和邮箱
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# 配置默认分支名为 main
git config --global init.defaultBranch main

# 配置换行符处理
git config --global core.autocrlf input
```

---

**版本**：1.0.0
**最后更新**：2026-04-24