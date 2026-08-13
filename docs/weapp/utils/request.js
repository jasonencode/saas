/**
 * 基础 HTTP 请求封装
 *
 * 功能：
 * - 自动附加 Authorization 头
 * - Token 有效期管理
 * - 统一错误处理
 * - 租户签名认证
 */
const config = require('../config')
const { nowTimestamp, randomString, generateSignature } = require('./helpers')

/**
 * 获取存储的用户 token
 * @returns {string|null}
 */
function getToken() {
  return wx.getStorageSync(config.TOKEN_KEY) || null
}

/**
 * 保存用户 token
 * @param {string} token
 */
function setToken(token) {
  wx.setStorageSync(config.TOKEN_KEY, token)
}

/**
 * 清除用户 token
 */
function clearToken() {
  wx.removeStorageSync(config.TOKEN_KEY)
}

/**
 * 检查租户 Token 是否有效
 * @returns {boolean}
 */
function isTenantTokenValid() {
  const expires = wx.getStorageSync(config.TENANT_TOKEN_EXPIRES_KEY)
  if (!expires) return false
  // 提前 5 分钟过期，避免边缘情况
  return nowTimestamp() < expires - 300
}

/**
 * 获取租户访问令牌（自动处理签名和缓存）
 * @returns {Promise<string>}
 */
async function getTenantToken() {
  if (isTenantTokenValid()) {
    return wx.getStorageSync(config.TENANT_TOKEN_KEY)
  }

  const timestamp = nowTimestamp()
  const nonce = randomString()
  const signature = generateSignature(config.APP_SECRET, config.APP_KEY, timestamp, nonce)

  const res = await rawRequest({
    url: `${config.API_DOMAIN}${config.API_PREFIX}/auth/tenant`,
    method: 'POST',
    data: {
      app_key: config.APP_KEY,
      timestamp,
      nonce,
      signature,
    },
    noAuth: true,
  })

  const token = res.access_token
  const expiresIn = res.expires_in || 7200

  wx.setStorageSync(config.TENANT_TOKEN_KEY, token)
  wx.setStorageSync(config.TENANT_TOKEN_EXPIRES_KEY, timestamp + expiresIn)

  return token
}

/**
 * 原始请求（不经过拦截器）
 * @param {Object} options
 * @returns {Promise<any>}
 */
function rawRequest(options) {
  return new Promise((resolve, reject) => {
    wx.request({
      url: options.url,
      method: options.method || 'GET',
      data: options.data,
      header: Object.assign({
        'Content-Type': 'application/json',
      }, options.header || {}),
      timeout: options.timeout || 15000,
      success(res) {
        if (res.statusCode >= 200 && res.statusCode < 300) {
          // 204 No Content 无返回数据
          if (res.statusCode === 204) {
            resolve(null)
            return
          }
          // 检查业务状态码
          if (res.data && res.data.code === 0) {
            resolve(res.data.data !== undefined ? res.data.data : res.data)
          } else {
            reject({
              code: res.data ? res.data.code : res.statusCode,
              message: res.data ? res.data.message : '请求失败',
              response: res.data,
            })
          }
        } else {
          reject({
            code: res.statusCode,
            message: getHttpStatusMessage(res.statusCode, res.data),
            response: res.data,
          })
        }
      },
      fail(err) {
        reject({
          code: -1,
          message: err.errMsg || '网络请求失败',
          response: err,
        })
      },
    })
  })
}

/**
 * 带认证的请求
 * @param {Object} options
 * @returns {Promise<any>}
 */
async function request(options) {
  const header = Object.assign({}, options.header || {})

  // 附加租户 Token
  if (!options.noTenant) {
    try {
      const tenantToken = await getTenantToken()
      header['Authorization'] = `Bearer ${tenantToken}`
    } catch (e) {
      // 租户 token 获取失败，继续尝试（部分接口不需要租户 token）
    }
  }

  // 附加用户 Token
  if (!options.noAuth) {
    const token = getToken()
    if (token) {
      header['Authorization'] = `Bearer ${token}`
    }
  }

  // 如果是上传文件，使用 wx.uploadFile
  if (options.upload) {
    return uploadFile(options, header)
  }

  return rawRequest({
    url: `${config.API_DOMAIN}${config.API_PREFIX}${options.url}`,
    method: options.method,
    data: options.data,
    header,
    timeout: options.timeout,
  })
}

/**
 * 文件上传
 * @param {Object} options
 * @param {Object} header
 * @returns {Promise<any>}
 */
function uploadFile(options, header) {
  return new Promise((resolve, reject) => {
    const uploadTask = wx.uploadFile({
      url: `${config.API_DOMAIN}${config.API_PREFIX}${options.url}`,
      filePath: options.filePath,
      name: options.fileName || 'file',
      formData: options.formData || {},
      header,
      success(res) {
        try {
          const data = JSON.parse(res.data)
          if (data.code === 0) {
            resolve(data.data !== undefined ? data.data : data)
          } else {
            reject({
              code: data.code || res.statusCode,
              message: data.message || '上传失败',
              response: data,
            })
          }
        } catch (e) {
          reject({
            code: res.statusCode,
            message: '上传返回数据解析失败',
            response: res.data,
          })
        }
      },
      fail(err) {
        reject({
          code: -1,
          message: err.errMsg || '上传失败',
          response: err,
        })
      },
    })

    // 支持上传进度回调
    if (options.onProgress) {
      uploadTask.onProgressUpdate(options.onProgress)
    }
  })
}

/**
 * HTTP 状态码消息映射
 */
function getHttpStatusMessage(status, data) {
  const messages = {
    400: '请求参数错误',
    401: '未登录或登录已过期',
    403: '无权限访问',
    404: '资源不存在',
    422: '参数验证失败',
    429: '请求过于频繁，请稍后重试',
    500: '服务器内部错误',
    502: '网关错误',
    503: '服务暂不可用',
  }

  if (data && data.message) {
    return data.message
  }

  return messages[status] || `请求异常（${status}）`
}

module.exports = {
  request,
  rawRequest,
  getToken,
  setToken,
  clearToken,
  getTenantToken,
  isTenantTokenValid,
}
