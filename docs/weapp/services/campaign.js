/**
 * Campaign - 营销活动模块
 *
 * 接口文档: docs/apis/campaign.md
 */
const { request } = require('../utils/request')

// ==================== 优惠券 ====================

/**
 * 获取优惠券列表
 * @param {Object} [params]
 * @param {string} [params.type] 优惠券类型
 * @param {number} [params.min_amount] 最低门槛
 * @param {number} [params.max_amount] 最高门槛
 * @param {number} [params.limit] 每页条数
 * @returns {Promise<Object>}
 */
function getCoupons(params) {
  return request({
    url: '/campaign/coupons',
    method: 'GET',
    data: params,
    noAuth: true,
  })
}

/**
 * 获取我的优惠券
 * @param {Object} [params]
 * @param {boolean} [params.is_used] 是否已使用
 * @param {number} [params.limit] 每页条数
 * @returns {Promise<Object>}
 */
function getMyCoupons(params) {
  return request({
    url: '/campaign/coupons/my',
    method: 'GET',
    data: params,
  })
}

/**
 * 获取优惠券详情
 * @param {number} id 优惠券 ID
 * @returns {Promise<Object>}
 */
function getCoupon(id) {
  return request({
    url: `/campaign/coupons/${id}`,
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 领取优惠券
 * @param {number} id 优惠券 ID
 * @returns {Promise<Object>}
 */
function claimCoupon(id) {
  return request({
    url: `/campaign/coupons/${id}/claim`,
    method: 'POST',
  })
}

// ==================== 红包 ====================

/**
 * 获取红包活动列表
 * @param {Object} [params]
 * @param {string} [params.name] 活动名称
 * @param {boolean} [params.status] 活动状态
 * @param {number} [params.limit] 每页条数
 * @returns {Promise<Object>}
 */
function getRedpacks(params) {
  return request({
    url: '/campaign/redpacks',
    method: 'GET',
    data: params,
    noAuth: true,
  })
}

/**
 * 获取我的红包
 * @param {Object} [params]
 * @param {number} [params.limit] 每页条数
 * @returns {Promise<Object>}
 */
function getMyRedpacks(params) {
  return request({
    url: '/campaign/redpacks/my',
    method: 'GET',
    data: params,
  })
}

/**
 * 获取红包活动详情
 * @param {number} id 活动 ID
 * @returns {Promise<Object>}
 */
function getRedpack(id) {
  return request({
    url: `/campaign/redpacks/${id}`,
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 领取红包
 * @param {string} code 红包码
 * @returns {Promise<{amount: string, claimed_at: string}>}
 */
function claimRedpack(code) {
  return request({
    url: `/campaign/redpacks/${code}/claim`,
    method: 'POST',
  })
}

// ==================== 抽奖 ====================

/**
 * 获取抽奖活动列表
 * @param {Object} [params]
 * @param {string} [params.name] 活动名称
 * @param {boolean} [params.status] 活动状态
 * @param {number} [params.limit] 每页条数
 * @returns {Promise<Object>}
 */
function getLotteries(params) {
  return request({
    url: '/campaign/lotteries',
    method: 'GET',
    data: params,
    noAuth: true,
  })
}

/**
 * 获取抽奖活动详情
 * @param {number} id 活动 ID
 * @returns {Promise<Object>}
 */
function getLottery(id) {
  return request({
    url: `/campaign/lotteries/${id}`,
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 抽奖
 * @param {number} id 活动 ID
 * @returns {Promise<Object>}
 */
function drawLottery(id) {
  return request({
    url: `/campaign/lotteries/${id}/draw`,
    method: 'POST',
  })
}

/**
 * 我的抽奖记录
 * @param {number} id 活动 ID
 * @param {Object} [params]
 * @param {number} [params.limit]
 * @returns {Promise<Object>}
 */
function getMyDraws(id, params) {
  return request({
    url: `/campaign/lotteries/${id}/draws`,
    method: 'GET',
    data: params,
  })
}

/**
 * 我的中奖记录
 * @param {number} id 活动 ID
 * @param {Object} [params]
 * @param {number} [params.limit]
 * @returns {Promise<Object>}
 */
function getMyPrizes(id, params) {
  return request({
    url: `/campaign/lotteries/${id}/prizes`,
    method: 'GET',
    data: params,
  })
}

/**
 * 获取剩余抽奖次数
 * @param {number} id 活动 ID
 * @returns {Promise<{available_draws: number}>}
 */
function getAvailableDraws(id) {
  return request({
    url: `/campaign/lotteries/${id}/available-draws`,
    method: 'GET',
  })
}

module.exports = {
  // 优惠券
  getCoupons,
  getMyCoupons,
  getCoupon,
  claimCoupon,
  // 红包
  getRedpacks,
  getMyRedpacks,
  getRedpack,
  claimRedpack,
  // 抽奖
  getLotteries,
  getLottery,
  drawLottery,
  getMyDraws,
  getMyPrizes,
  getAvailableDraws,
}
