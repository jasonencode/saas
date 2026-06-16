/**
 * Finance - 财务管理模块
 *
 * 接口文档: docs/apis/finance.md
 * 全部接口需要登录认证（auth:sanctum）
 */
const { request } = require('../utils/request')

// ==================== 支付 ====================

/**
 * 发起支付
 * @param {Object} data
 * @param {number} data.amount 支付金额（≥0.01）
 * @param {string} data.gateway 支付网关
 * @param {string} [data.paymentable_type] 关联业务类型
 * @param {number} [data.paymentable_id] 关联业务 ID
 * @param {string} [data.remark] 备注
 * @returns {Promise<Object>}
 */
function createPayment(data) {
  return request({
    url: '/payments',
    method: 'POST',
    data,
  })
}

/**
 * 查询支付状态
 * @param {number} id 支付订单 ID
 * @returns {Promise<Object>}
 */
function getPayment(id) {
  return request({
    url: `/payments/${id}`,
    method: 'GET',
  })
}

/**
 * 申请退款
 * @param {number} paymentId 支付订单 ID
 * @param {number} amount 退款金额
 * @param {string} reason 退款原因
 * @returns {Promise<{refund_id: number, amount: string, status: string, status_label: string}>}
 */
function refundPayment(paymentId, amount, reason) {
  return request({
    url: `/payments/${paymentId}/refund`,
    method: 'POST',
    data: { amount, reason },
  })
}

// ==================== 结算凭据 ====================

/**
 * 获取结算凭据列表
 * @param {Object} [params]
 * @param {number} [params.per_page] 每页条数（默认15，最大50）
 * @returns {Promise<Object>}
 */
function getVouchers(params) {
  return request({
    url: '/vouchers',
    method: 'GET',
    data: params,
  })
}

module.exports = {
  createPayment,
  getPayment,
  refundPayment,
  getVouchers,
}
