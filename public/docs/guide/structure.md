# 目录结构

## 整体结构

```
├── app/
│   ├── Console/                 # Artisan 命令
│   ├── Exceptions/              # 异常处理
│   ├── Filament/                # Filament 资源
│   │   ├── Actions/             # 自定义 Actions
│   │   ├── Pages/              # 自定义页面
│   │   └── Resources/          # 资源定义
│   ├── Helpers/                 # 辅助函数
│   ├── Models/                  # 数据模型
│   │   ├── Traits/             # 模型 Trait
│   │   └── Enums/              # 枚举类
│   ├── Policies/                # 权限策略
│   └── Services/               # 业务服务
├── bootstrap/                   # 引导文件
├── config/                      # 配置文件
├── database/
│   ├── factories/              # 模型工厂
│   ├── migrations/              # 数据迁移
│   └── seeders/                # 数据填充
├── lang/                        # 语言文件
├── public/                      # 公共资源
│   └── docs/                    # 文档（Docsify）
├── resources/
│   └── views/                  # 视图文件
├── routes/                      # 路由定义
├── storage/                     # 存储文件
└── tests/                       # 测试文件
```

## 核心目录说明

### Filament 资源

`app/Filament/Resources/` 目录下包含：
- 资源类定义
- 关联的 Actions
- 自定义页面

### 策略类

`app/Policies/` 目录下包含：
- 每个模型的权限策略
- 使用 `#[PolicyName]` 注解定义权限名称