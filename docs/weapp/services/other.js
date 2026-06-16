/**
 * Other - 其他模块
 *
 * 接口文档: docs/apis/other.md
 */
const { request } = require('../utils/request')

/**
 * 检测应用版本更新
 * @param {string} platform 平台（ios / android）
 * @param {string} version 当前版本号
 * @param {string} applicationId 应用标识
 * @returns {Promise<{update: boolean} | {update: true, version: string, description: string, force: boolean, download: string, publish_at: string}>}
 */
function checkVersion(platform, version, applicationId) {
  return request({
    url: '/app_version',
    method: 'GET',
    data: { platform, version, application_id: applicationId },
    noAuth: true,
    noTenant: true,
  })
}

/**
 * 服务器健康检查
 * @returns {Promise<string>}
 */
function healthCheck() {
  return request({
    url: '/',
    method: 'GET',
    noAuth: true,
    noTenant: true,
  })
}

/**
 * 上传单张图片
 * @param {string} filePath 本地图片路径（wx.chooseImage 返回的 tempFilePath）
 * @param {Function} [onProgress] 上传进度回调
 * @returns {Promise<{url: string, path: string, name: string, size: number}>}
 */
function uploadImage(filePath, onProgress) {
  return request({
    url: '/upload/image',
    method: 'POST',
    upload: true,
    filePath,
    fileName: 'file',
    onProgress,
  })
}

/**
 * 上传多张图片
 * @param {string[]} filePaths 本地图片路径数组
 * @param {Function} [onProgress] 上传进度回调
 * @returns {Promise<Array<{url: string, path: string, name: string, size: number}>>}
 *
 * @example
 * const images = await Promise.all(filePaths.map(f => uploadImage(f)))
 */
// 多图上传使用单图接口并行调用
// 如果后端需要多图一次性上传，后端接口是 POST /upload/images
// 这里使用单图上传接口逐个上传

module.exports = {
  checkVersion,
  healthCheck,
  uploadImage,
}
