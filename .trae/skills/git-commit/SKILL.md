---
name: git-commit
description: "Git Commit 技能，规范化 Git 提交消息格式，涵盖提交类型、分支管理、工作流程、最佳实践和常见问题解决。适用于团队协作开发和代码版本管理。"
license: MIT
metadata:
  author: Jason.Chen
  version: 1.0.0
  last_updated: 2026-04-24
---

# Git Commit 技能

## 1. 核心规范

### 1.1 Commit 消息格式

```
<类型>(<范围>): <描述>

[可选的正文]

[可选的页脚]
```

### 1.2 类型说明

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

### 1.3 范围说明

- 范围应该是具体的模块或文件，如：
  - `auth` - 认证模块
  - `user` - 用户模块
  - `api` - API 接口
  - `database` - 数据库
  - `frontend` - 前端
  - `backend` - 后端

### 1.4 描述规范

- 简短：不超过 50 个字符
- 清晰：准确描述改动内容
- 主动语态：使用动词开头
- 首字母小写：不需要大写开头
- 不加句号：结尾不需要句号

## 2. 最佳实践

### 2.1 提交频率

- **小而频繁**：每次提交只包含一个逻辑改动
- **原子性**：确保每个提交都是独立的、可测试的
- **避免混合**：不要在一个提交中混合不同类型的改动

### 2.2 提交内容

- **只提交必要文件**：不要提交临时文件、日志文件、依赖文件
- **保持代码整洁**：提交前运行代码格式化工具
- **运行测试**：确保提交的代码通过测试

### 2.3 分支管理

- **主分支**：`main` 或 `master` - 稳定版本
- **开发分支**：`develop` - 开发中的版本
- **特性分支**：`feat/feature-name` - 新功能开发
- **修复分支**：`fix/bug-name` - bug 修复
- **发布分支**：`release/version` - 版本发布
- **热修复分支**：`hotfix/issue-name` - 紧急修复

### 2.4 提交消息示例

#### 好的示例：
- `feat(auth): 添加邮箱验证码登录功能`
- `fix(user): 修复用户信息更新失败问题`
- `docs(api): 更新 API 文档`
- `refactor(database): 优化数据库查询逻辑`
- `perf(frontend): 优化首页加载速度`

#### 不好的示例：
- `update` - 过于模糊
- `修复bug` - 缺少范围和具体描述
- `添加功能并修复问题` - 混合多个改动
- `feat: 添加了一个非常重要的功能，这个功能可以让用户更好地使用系统` - 描述过长

## 3. 工具与配置

### 3.1 Git 配置

```bash
# 配置用户名和邮箱
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# 配置默认分支名为 main
git config --global init.defaultBranch main

# 配置换行符处理
git config --global core.autocrlf input
```

### 3.2 预提交钩子

创建 `.git/hooks/pre-commit` 文件：

```bash
#!/bin/sh

# 运行代码格式化
vendor/bin/pint

# 运行测试
php artisan test

# 检查提交消息格式
commit_msg=$(cat .git/COMMIT_EDITMSG)
if ! echo "$commit_msg" | grep -E '^(feat|fix|docs|style|refactor|perf|test|build|ci|chore)\(.+\): .+'; then
  echo "错误：提交消息格式不正确"
  echo "正确格式：<类型>(<范围>): <描述>"
  exit 1
fi
```

### 3.3 工具推荐

- **Commitizen**：标准化提交消息
- **Husky**：Git 钩子管理
- **Commitlint**：提交消息检查
- **Gitmoji**：使用 emoji 增强提交消息

### 3.4 VS Code 扩展

- **GitLens**：增强 Git 功能
- **Git History**：查看 Git 历史
- **Conventional Commits**：提交消息模板

## 4. 常见问题解决

### 4.1 提交错误

- **误提交文件**：
  ```bash
  # 撤销上次提交但保留更改
  git reset --soft HEAD~1
  # 从暂存区移除文件
  git reset HEAD <file>
  # 重新提交
  git commit -m "正确的提交消息"
  ```

- **提交消息错误**：
  ```bash
  # 修改上次提交消息
  git commit --amend -m "新的提交消息"
  ```

### 4.2 分支冲突

- **解决冲突**：
  1. 手动编辑冲突文件
  2. 标记冲突已解决：`git add <file>`
  3. 完成合并：`git commit`

### 4.3 历史管理

- **查看提交历史**：
  ```bash
  git log --oneline --graph --decorate
  ```

- **查找特定提交**：
  ```bash
  git log --grep="关键词"
  ```

- **查看文件历史**：
  ```bash
  git log --follow -- <file>
  ```

## 5. 工作流程

### 5.1 标准工作流程

1. **创建分支**：`git checkout -b feat/feature-name`
2. **进行开发**：编写代码、测试
3. **提交更改**：`git add .` 和 `git commit -m "feat(scope): 描述"`
4. **推送分支**：`git push origin feat/feature-name`
5. **创建 PR**：在 GitHub/GitLab 上创建 Pull Request
6. **代码审查**：团队成员审查代码
7. **合并分支**：通过审查后合并到 develop 分支
8. **删除分支**：`git branch -d feat/feature-name`

### 5.2 发布流程

1. **创建发布分支**：`git checkout -b release/1.0.0`
2. **版本号更新**：更新 package.json、composer.json 等
3. **测试**：运行完整测试套件
4. **提交更改**：`git commit -m "build: 发布版本 1.0.0"`
5. **合并到 main**：将发布分支合并到 main 分支
6. **创建标签**：`git tag v1.0.0`
7. **推送标签**：`git push origin v1.0.0`
8. **合并到 develop**：将发布分支合并回 develop 分支

### 5.3 热修复流程

1. **创建热修复分支**：`git checkout -b hotfix/issue-name main`
2. **修复问题**：编写修复代码
3. **测试**：验证修复是否有效
4. **提交更改**：`git commit -m "fix(scope): 修复问题"`
5. **合并到 main**：将热修复分支合并到 main 分支
6. **创建标签**：`git tag v1.0.1`
7. **推送标签**：`git push origin v1.0.1`
8. **合并到 develop**：将热修复分支合并回 develop 分支

## 6. 团队协作

### 6.1 代码审查

- **审查要点**：
  - 代码质量和可读性
  - 功能实现是否正确
  - 性能和安全性
  - 测试覆盖情况

- **审查工具**：
  - GitHub/GitLab PR 审查
  - 代码审查工具（如 CodeClimate）

### 6.2 分支策略

- **Git Flow**：标准分支管理策略
- **GitHub Flow**：简化的分支管理策略
- **GitLab Flow**：结合 CI/CD 的分支策略

### 6.3 团队约定

- **提交频率**：每天至少提交一次
- **分支命名**：使用统一的命名规范
- **提交消息**：遵循统一的格式
- **代码风格**：使用统一的代码风格

## 7. 性能优化

### 7.1 Git 性能

- **大文件处理**：使用 Git LFS 管理大文件
- **历史压缩**：定期执行 `git gc` 优化仓库
- **浅克隆**：使用 `git clone --depth 1` 快速克隆

### 7.2 网络优化

- **代理设置**：配置 Git 代理加速
- **缓存设置**：使用 `git config --global credential.helper cache`
- **推送策略**：使用 `git push --atomic` 确保原子性

## 8. 安全最佳实践

### 8.1 敏感信息保护

- **.gitignore**：排除敏感文件
- **环境变量**：使用 .env 文件管理敏感信息
- **密码管理**：使用密码管理器
- **密钥管理**：使用 SSH 密钥，定期更新

### 8.2 代码安全

- **定期扫描**：使用安全扫描工具
- **依赖检查**：检查依赖包安全漏洞
- **代码审查**：关注安全相关代码

## 9. 常见命令

### 9.1 基础命令

- **初始化仓库**：`git init`
- **克隆仓库**：`git clone <url>`
- **查看状态**：`git status`
- **添加文件**：`git add <file>` 或 `git add .`
- **提交更改**：`git commit -m "消息"`
- **推送更改**：`git push`
- **拉取更改**：`git pull`

### 9.2 分支命令

- **查看分支**：`git branch`
- **创建分支**：`git branch <name>`
- **切换分支**：`git checkout <name>`
- **创建并切换**：`git checkout -b <name>`
- **合并分支**：`git merge <name>`
- **删除分支**：`git branch -d <name>`

### 9.3 历史命令

- **查看历史**：`git log`
- **查看差异**：`git diff`
- **撤销更改**：`git checkout -- <file>`
- **重置提交**：`git reset <commit>`
- **恢复提交**：`git revert <commit>`

## 10. 示例工作流

### 10.1 新功能开发

```bash
# 从 develop 分支创建特性分支
git checkout develop
git pull
git checkout -b feat/user-registration

# 开发新功能
# ... 编写代码 ...

# 运行测试
php artisan test

# 提交更改
git add .
git commit -m "feat(auth): 添加用户注册功能"

# 推送分支
git push origin feat/user-registration

# 创建 PR 并等待审查
# ... 审查过程 ...

# 合并到 develop
git checkout develop
git merge feat/user-registration
git push
git branch -d feat/user-registration
```

### 10.2 Bug 修复

```bash
# 从 develop 分支创建修复分支
git checkout develop
git pull
git checkout -b fix/login-error

# 修复 bug
# ... 编写代码 ...

# 运行测试
php artisan test

# 提交更改
git add .
git commit -m "fix(auth): 修复登录失败问题"

# 推送分支
git push origin fix/login-error

# 创建 PR 并等待审查
# ... 审查过程 ...

# 合并到 develop
git checkout develop
git merge fix/login-error
git push
git branch -d fix/login-error
```

## 11. 总结

Git 提交规范是团队协作的重要基础，遵循统一的规范可以：

- **提高代码质量**：清晰的提交消息便于理解代码变更
- **简化代码审查**：结构化的提交消息使审查更加高效
- **方便问题定位**：详细的提交消息有助于快速定位问题
- **改善团队协作**：统一的规范减少沟通成本
- **提升项目质量**：良好的版本控制习惯有助于项目长期维护

通过本技能文件，团队成员可以了解并遵循统一的 Git 提交规范，提高代码管理的质量和效率。

## 12. 版本信息

- **技能版本**：1.0.0
- **适用 Git 版本**：2.0+
- **最后更新**：2026-04-24
