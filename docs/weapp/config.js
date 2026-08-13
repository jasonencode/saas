/**
 * SaaS.Foundation 小程序 SDK 配置
 *
 * 使用前请修改以下配置项
 */
module.exports = {
  // API 基础域名（必须修改）
  API_DOMAIN: 'https://your-api-domain.com',

  // API 路径前缀（后端所有接口注册在 /api 下，一般无需修改）
  API_PREFIX: '/api',

  // 租户认证信息（用于租户签名获取 token）
  APP_KEY: 'your_app_key',
  APP_SECRET: 'your_app_secret',

  // 令牌本地存储 Key
  TOKEN_KEY: 'saas_token',
  TENANT_TOKEN_KEY: 'saas_tenant_token',
  TENANT_TOKEN_EXPIRES_KEY: 'saas_tenant_token_expires',
}
