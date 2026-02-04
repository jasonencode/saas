| 类型     | 说明               | 示例                                  |
|----------|--------------------|---------------------------------------|
| feat     | 新功能             | feat: 添加用户登录功能                |
| fix      | 修复 bug           | fix: 修复登录失效问题                 |
| docs     | 文档更新           | docs: 更新 API 文档                   |
| style    | 代码样式调整       | style: 调整代码缩进                   |
| refactor | 代码重构           | refactor: 重构用户模块                |
| perf     | 性能优化           | perf: 优化页面加载速度               |
| test     | 测试相关           | test: 添加单元测试                    |
| chore    | 构建/工具链变动    | chore: 更新依赖包                     |
| build    | 构建系统变动       | build: 更新 webpack 配置              |
| ci       | CI/CD 相关         | ci: 添加 GitHub Actions               |
| revert   | 回滚提交           | revert: 回滚某次提交                  |

## 提交示例

```text
feat(auth): 添加双因素认证

- 添加短信验证码登录
- 集成 Google Authenticator
- 更新用户设置界面

Closes #123
BREAKING CHANGE: 登录接口需要传递验证码参数
```

```text
feat(auth): 调整登录验证码视图并优化交互

- 将验证码图片移出 suffix，与输入框并排展示
- 点击图片即可刷新，统一使用 Tailwind 类替换内联样式
- 增加占位与关闭自动填充，提升输入体验
```

```text
chore(build): 更新前端构建产物与 manifest

- 替换旧版 CSS 文件
- 同步 public/build/manifest.json
```

```text
refactor(blockchain): 抽离适配器密钥逻辑为通用 trait

- 统一 secp256k1 私钥生成与校验
- 适配各链的地址派生方法
```

```text
feat(blockchain): ChainType/NetworkAdapter 增加地址与密钥方法

- 新增创建私钥/公钥/地址的接口约定
- 为各链实现方法并修复语法错误
```

```text
chore(migrations): 补充表与字段的注释说明

- 为缓存、队列、用户、内容等表添加中文注释
- 明确业务含义，便于后续维护与数据审计
```
