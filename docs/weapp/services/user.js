/**
 * User - 用户中心模块
 *
 * 接口文档: docs/apis/user.md
 * 全部接口需要登录认证（auth:sanctum）
 */
const { request } = require('../utils/request')

// ==================== 用户资料 ====================

/**
 * 获取用户资料
 * @returns {Promise<Object>}
 */
function getProfile() {
  return request({
    url: '/user/profile',
    method: 'GET',
  })
}

/**
 * 修改用户资料
 * @param {Object} data
 * @param {string} [data.nickname] 昵称
 * @param {number} [data.gender] 性别（0=未知 1=男 2=女）
 * @param {string} [data.birthday] 生日（Y-m-d）
 * @param {string} [data.avatar] 头像 URL
 * @returns {Promise<Object>}
 */
function updateProfile(data) {
  return request({
    url: '/user/profile',
    method: 'PUT',
    data,
  })
}

// ==================== 账户信息 ====================

/**
 * 获取账户信息
 * @returns {Promise<{balance: string, frozen_balance: string, points: number, frozen_points: number}>}
 */
function getAccount() {
  return request({
    url: '/user/account',
    method: 'GET',
  })
}

/**
 * 获取账户变动日志
 * @param {Object} [params]
 * @param {number} [params.page]
 * @param {number} [params.limit]
 * @returns {Promise<Object>}
 */
function getAccountLogs(params) {
  return request({
    url: '/user/account/logs',
    method: 'GET',
    data: params,
  })
}

// ==================== 安全设置 ====================

/**
 * 获取登录记录
 * @returns {Promise<Object>}
 */
function getLoginRecords() {
  return request({
    url: '/user/safe/records',
    method: 'GET',
  })
}

/**
 * 修改密码
 * @param {string} oldPass 原密码
 * @param {string} newPass 新密码
 * @returns {Promise<null>}
 */
function changePassword(oldPass, newPass) {
  return request({
    url: '/user/safe/password',
    method: 'PUT',
    data: {
      old_pass: oldPass,
      new_pass: newPass,
      new_pass_confirmation: newPass,
    },
  })
}

/**
 * 退出登录
 * @returns {Promise<null>}
 */
function logout() {
  return request({
    url: '/user/safe/logout',
    method: 'POST',
  })
}

// ==================== 地址管理 ====================

/**
 * 获取地址列表
 * @returns {Promise<Array>}
 */
function getAddresses() {
  return request({
    url: '/user/addresses',
    method: 'GET',
  })
}

/**
 * 获取地址详情
 * @param {number} id 地址 ID
 * @returns {Promise<Object>}
 */
function getAddress(id) {
  return request({
    url: `/user/addresses/${id}`,
    method: 'GET',
  })
}

/**
 * 获取省市区列表
 * @param {Object} [params]
 * @param {number} [params.parent_id] 上级区域 ID（默认0）
 * @param {number} [params.layer] 层级（1=一级 2=二级含下级数）
 * @returns {Promise<Array>}
 */
function getRegions(params) {
  return request({
    url: '/user/addresses/regions',
    method: 'GET',
    data: params,
  })
}

/**
 * 新增地址
 * @param {Object} data
 * @param {string} data.name 收件人
 * @param {string} data.mobile 手机号
 * @param {number} data.province_id 省份 ID
 * @param {number} data.city_id 城市 ID
 * @param {number} data.district_id 区县 ID
 * @param {string} data.address 详细地址
 * @param {boolean} [data.is_default] 是否默认
 * @returns {Promise<Object>}
 */
function createAddress(data) {
  return request({
    url: '/user/addresses',
    method: 'POST',
    data,
  })
}

/**
 * 编辑地址
 * @param {number} id 地址 ID
 * @param {Object} data 同 createAddress 的字段
 * @returns {Promise<Object>}
 */
function updateAddress(id, data) {
  return request({
    url: `/user/addresses/${id}`,
    method: 'PUT',
    data,
  })
}

/**
 * 删除地址
 * @param {number} id 地址 ID
 * @returns {Promise<null>}
 */
function deleteAddress(id) {
  return request({
    url: `/user/addresses/${id}`,
    method: 'DELETE',
  })
}

/**
 * 设置默认地址
 * @param {number} id 地址 ID
 * @returns {Promise<null>}
 */
function setDefaultAddress(id) {
  return request({
    url: `/user/addresses/${id}/default`,
    method: 'PUT',
  })
}

// ==================== 发票抬头 ====================

/**
 * 获取发票抬头列表
 * @returns {Promise<Array>}
 */
function getInvoiceTitles() {
  return request({
    url: '/user/invoice-titles',
    method: 'GET',
  })
}

/**
 * 获取发票抬头详情
 * @param {number} id
 * @returns {Promise<Object>}
 */
function getInvoiceTitle(id) {
  return request({
    url: `/user/invoice-titles/${id}`,
    method: 'GET',
  })
}

/**
 * 新增发票抬头
 * @param {Object} data
 * @param {string} data.type 类型（个人/企业）
 * @param {string} data.name 抬头名称
 * @param {string} [data.tax_no] 税号
 * @param {boolean} [data.is_default] 是否默认
 * @returns {Promise<Object>}
 */
function createInvoiceTitle(data) {
  return request({
    url: '/user/invoice-titles',
    method: 'POST',
    data,
  })
}

/**
 * 编辑发票抬头
 * @param {number} id
 * @param {Object} data
 * @returns {Promise<Object>}
 */
function updateInvoiceTitle(id, data) {
  return request({
    url: `/user/invoice-titles/${id}`,
    method: 'PUT',
    data,
  })
}

/**
 * 删除发票抬头
 * @param {number} id
 * @returns {Promise<null>}
 */
function deleteInvoiceTitle(id) {
  return request({
    url: `/user/invoice-titles/${id}`,
    method: 'DELETE',
  })
}

/**
 * 设置默认发票抬头
 * @param {number} id
 * @returns {Promise<null>}
 */
function setDefaultInvoiceTitle(id) {
  return request({
    url: `/user/invoice-titles/${id}/default`,
    method: 'PUT',
  })
}

// ==================== 发票管理 ====================

/**
 * 获取发票申请列表
 * @returns {Promise<Object>}
 */
function getInvoiceApplications() {
  return request({
    url: '/user/invoices/applications',
    method: 'GET',
  })
}

/**
 * 获取发票申请详情
 * @param {number} id
 * @returns {Promise<Object>}
 */
function getInvoiceApplication(id) {
  return request({
    url: `/user/invoices/applications/${id}`,
    method: 'GET',
  })
}

/**
 * 提交发票申请
 * @param {Object} data
 * @param {number} data.invoice_title_id 发票抬头 ID
 * @param {number} data.amount 开票金额
 * @param {string} data.reason 开票事由
 * @param {string} [data.remark] 备注
 * @param {number[]} [data.order_ids] 关联订单
 * @returns {Promise<Object>}
 */
function applyInvoice(data) {
  return request({
    url: '/user/invoices/applications',
    method: 'POST',
    data,
  })
}

/**
 * 获取已开具发票列表
 * @returns {Promise<Object>}
 */
function getInvoices() {
  return request({
    url: '/user/invoices',
    method: 'GET',
  })
}

/**
 * 获取发票详情
 * @param {number} id
 * @returns {Promise<Object>}
 */
function getInvoice(id) {
  return request({
    url: `/user/invoices/${id}`,
    method: 'GET',
  })
}

// ==================== 通知 ====================

/**
 * 获取通知列表
 * @param {Object} [params]
 * @param {string} [params.type] 通知类型
 * @returns {Promise<Object>}
 */
function getNotifications(params) {
  return request({
    url: '/user/notifications',
    method: 'GET',
    data: params,
  })
}

/**
 * 获取通知分组列表
 * @returns {Promise<Array>}
 */
function getNotificationGroups() {
  return request({
    url: '/user/notifications/group',
    method: 'GET',
  })
}

/**
 * 获取通知详情
 * @param {string} id 通知 UUID
 * @returns {Promise<Object>}
 */
function getNotification(id) {
  return request({
    url: `/user/notifications/${id}`,
    method: 'GET',
  })
}

/**
 * 标记单条通知已读
 * @param {string} id 通知 UUID
 * @returns {Promise<null>}
 */
function markNotificationRead(id) {
  return request({
    url: `/user/notifications/${id}/read`,
    method: 'PUT',
  })
}

/**
 * 标记全部通知已读
 * @param {Object} [params]
 * @param {string} [params.type] 仅标记指定类型
 * @returns {Promise<null>}
 */
function markAllNotificationsRead(params) {
  return request({
    url: '/user/notifications/read',
    method: 'PUT',
    data: params,
  })
}

/**
 * 获取通知数量
 * @param {Object} [params]
 * @param {string} [params.type] 通知类型
 * @returns {Promise<{total: number, unread: number}>}
 */
function getNotificationCount(params) {
  return request({
    url: '/user/notifications/count',
    method: 'GET',
    data: params,
  })
}

/**
 * 删除全部已读通知
 * @param {Object} [params]
 * @param {string} [params.type] 通知类型
 * @returns {Promise<null>}
 */
function deleteAllReadNotifications(params) {
  return request({
    url: '/user/notifications/read',
    method: 'DELETE',
    data: params,
  })
}

/**
 * 删除单条通知
 * @param {string} id 通知 UUID
 * @returns {Promise<null>}
 */
function deleteNotification(id) {
  return request({
    url: `/user/notifications/${id}`,
    method: 'DELETE',
  })
}

module.exports = {
  // 资料
  getProfile,
  updateProfile,
  // 账户
  getAccount,
  getAccountLogs,
  // 安全
  getLoginRecords,
  changePassword,
  logout,
  // 地址
  getAddresses,
  getAddress,
  getRegions,
  createAddress,
  updateAddress,
  deleteAddress,
  setDefaultAddress,
  // 发票抬头
  getInvoiceTitles,
  getInvoiceTitle,
  createInvoiceTitle,
  updateInvoiceTitle,
  deleteInvoiceTitle,
  setDefaultInvoiceTitle,
  // 发票
  getInvoiceApplications,
  getInvoiceApplication,
  applyInvoice,
  getInvoices,
  getInvoice,
  // 通知
  getNotifications,
  getNotificationGroups,
  getNotification,
  markNotificationRead,
  markAllNotificationsRead,
  getNotificationCount,
  deleteAllReadNotifications,
  deleteNotification,
}
