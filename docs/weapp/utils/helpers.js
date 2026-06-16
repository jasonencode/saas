/**
 * 工具函数
 */

/**
 * 生成指定长度的随机字符串
 * @param {number} length
 * @returns {string}
 */
function randomString(length = 16) {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
  let result = ''
  for (let i = 0; i < length; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return result
}

/**
 * 生成租户签名
 * @param {string} appSecret
 * @param {string} appKey
 * @param {number} timestamp
 * @param {string} nonce
 * @returns {string}
 */
function generateSignature(appSecret, appKey, timestamp, nonce) {
  const signStr = `app_key=${appKey}&timestamp=${timestamp}&nonce=${nonce}`
  return hmacSha256(appSecret, signStr)
}

/**
 * HMAC-SHA256 计算
 * 微信小程序基础库支持通过 wx.request 间接计算，这里使用 js 实现
 * @param {string} secret
 * @param {string} data
 * @returns {string}
 */
function hmacSha256(secret, data) {
  // 小程序环境没有原生 crypto，需要使用第三方库或服务端预处理
  // 这里提供两种方案：

  // 方案一：使用小程序云函数计算签名（推荐）
  // return await wx.cloud.callFunction({
  //   name: 'sign',
  //   data: { secret, data }
  // })

  // 方案二：使用 js 库（需要引入 crypto-js 或类似库）
  // const CryptoJS = require('./crypto-js')
  // return CryptoJS.HmacSHA256(data, secret).toString()

  // 方案三：直接由后端提供一个临时签名接口（最简单，但不安全）
  // 建议使用方案一或二

  // 以下为空实现，需要根据实际环境选择上述方案
  throw new Error('HMAC-SHA256 需要在项目中集成 crypto-js 或使用云函数')
}

/**
 * 获取当前时间戳（秒）
 * @returns {number}
 */
function nowTimestamp() {
  return Math.floor(Date.now() / 1000)
}

module.exports = {
  randomString,
  generateSignature,
  hmacSha256,
  nowTimestamp,
}
