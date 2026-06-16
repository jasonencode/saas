/**
 * Auth - 认证模块
 *
 * 接口文档: docs/apis/auth.md
 */
const { request, setToken, clearToken } = require('../utils/request')

/**
 * 获取图形验证码
 * @returns {Promise<{key: string, img: string}>}
 */
function getCaptcha() {
  return request({
    url: '/auth/captcha',
    method: 'GET',
    noAuth: true,
    noTenant: true,
  })
}

/**
 * 账户密码登录
 * @param {string} username 用户名
 * @param {string} password 密码
 * @param {Object} [captcha] 验证码
 * @param {string} [captcha.key] 验证码 Key
 * @param {string} [captcha.code] 验证码
 * @returns {Promise<{user: Object, token: string, token_type: string}>}
 */
async function login(username, password, captcha) {
  const data = { username, password }
  if (captcha) {
    data.captcha_key = captcha.key
    data.captcha_code = captcha.code
  }

  const res = await request({
    url: '/auth/password',
    method: 'POST',
    data,
    noAuth: true,
    noTenant: false,
  })

  // 自动保存 token
  if (res.token) {
    setToken(res.token)
  }

  return res
}

/**
 * 租户获取访问令牌
 * @returns {Promise<{access_token: string, token_type: string, expires_in: number}>}
 */
function getTenantToken() {
  return request({
    url: '/auth/tenant',
    method: 'POST',
    noAuth: true,
    noTenant: true,
  })
}

/**
 * 用户注册
 * @param {string} username 用户名
 * @param {string} password 密码
 * @returns {Promise<{user: Object, token: string, token_type: string}>}
 */
async function register(username, password) {
  const res = await request({
    url: '/auth/register',
    method: 'POST',
    data: { username, password },
    noAuth: true,
  })

  if (res.token) {
    setToken(res.token)
  }

  return res
}

/**
 * 发送短信验证码
 * @param {string} phone 手机号
 * @returns {Promise<null>}
 */
function sendSms(phone) {
  return request({
    url: '/auth/sms',
    method: 'POST',
    data: { phone },
    noAuth: true,
    noTenant: false,
  })
}

/**
 * 微信小程序手机号登录
 * @param {string} code 手机号授权 code
 * @returns {Promise<{user: Object, token: string, token_type: string}>}
 */
async function miniProgramLogin(code) {
  const res = await request({
    url: '/auth/mini/phone',
    method: 'POST',
    data: { code },
    noAuth: true,
  })

  if (res.token) {
    setToken(res.token)
  }

  return res
}

/**
 * 退出登录
 */
function logout() {
  clearToken()
}

module.exports = {
  getCaptcha,
  login,
  getTenantToken,
  register,
  sendSms,
  miniProgramLogin,
  logout,
}
