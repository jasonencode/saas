/**
 * SaaS.Foundation 小程序 SDK 入口
 *
 * 组合所有模块并导出
 */
const { setToken, clearToken, getToken } = require('./utils/request')
const auth = require('./services/auth')
const user = require('./services/user')
const mall = require('./services/mall')
const campaign = require('./services/campaign')
const content = require('./services/content')
const chain = require('./services/chain')
const finance = require('./services/finance')
const other = require('./services/other')

module.exports = {
  // 模块
  auth,
  user,
  mall,
  campaign,
  content,
  chain,
  finance,
  other,

  // Token 管理
  setToken,
  clearToken,
  getToken,

  /**
   * 从本地存储恢复登录态（在 app.onLaunch 中调用）
   * @returns {boolean} 是否有有效 token
   */
  restoreToken() {
    const token = getToken()
    if (token) {
      wx.setStorageSync('hasLogin', true)
      return true
    }
    wx.setStorageSync('hasLogin', false)
    return false
  },
}
