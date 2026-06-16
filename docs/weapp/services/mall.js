/**
 * Mall - 商城模块
 *
 * 接口文档: docs/apis/mall.md
 */
const { request } = require('../utils/request')

// ==================== 首页 ====================

/**
 * 商城首页
 * @returns {Promise<{banners: Array, brands: Array, products: Array}>}
 */
function getIndex() {
  return request({
    url: '/mall',
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 品牌列表
 * @returns {Promise<Array>}
 */
function getBrands() {
  return request({
    url: '/mall/brands',
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 轮播图列表
 * @returns {Promise<Array>}
 */
function getBanners() {
  return request({
    url: '/mall/banners',
    method: 'GET',
    noAuth: true,
  })
}

// ==================== 分类 ====================

/**
 * 获取商品分类列表
 * @returns {Promise<Array>}
 */
function getCategories() {
  return request({
    url: '/mall/categories',
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 获取分类详情
 * @param {number} id 分类 ID
 * @returns {Promise<Object>}
 */
function getCategory(id) {
  return request({
    url: `/mall/categories/${id}`,
    method: 'GET',
    noAuth: true,
  })
}

// ==================== 商品 ====================

/**
 * 获取商品列表
 * @param {Object} [params]
 * @param {string} [params.name] 商品名称（模糊搜索）
 * @param {number} [params.category_id] 分类 ID
 * @param {number} [params.brand_id] 品牌 ID
 * @param {number} [params.min_price] 最低价格
 * @param {number} [params.max_price] 最高价格
 * @param {string} [params.sort] 排序
 * @param {number} [params.limit] 每页条数（默认15）
 * @returns {Promise<Object>}
 */
function getProducts(params) {
  return request({
    url: '/mall/products',
    method: 'GET',
    data: params,
    noAuth: true,
  })
}

/**
 * 获取商品详情
 * @param {number} id 商品 ID
 * @returns {Promise<Object>}
 */
function getProduct(id) {
  return request({
    url: `/mall/products/${id}`,
    method: 'GET',
    noAuth: true,
  })
}

// ==================== 购物车 ====================

/**
 * 获取购物车列表
 * @returns {Promise<Object>}
 */
function getCart() {
  return request({
    url: '/mall/cart',
    method: 'GET',
  })
}

/**
 * 添加商品到购物车
 * @param {number} skuId 商品规格 ID
 * @param {number} qty 数量
 * @returns {Promise<Object>}
 */
function addToCart(skuId, qty) {
  return request({
    url: '/mall/cart/add',
    method: 'POST',
    data: { sku_id: skuId, qty },
  })
}

/**
 * 结算预览
 * @param {number[]} itemIds 购物车项目 ID 列表
 * @param {number} [addressId] 收货地址 ID
 * @returns {Promise<{items: Array, addresses: Array, total_amount: string, freight: string, payable_amount: string}>}
 */
function previewCheckout(itemIds, addressId) {
  return request({
    url: '/mall/cart/preview',
    method: 'POST',
    data: { item_ids: itemIds, address_id: addressId },
  })
}

/**
 * 从购物车创建订单
 * @param {number[]} itemIds 购物车项目 ID 列表
 * @param {number} addressId 收货地址 ID
 * @returns {Promise<null>}
 */
function checkout(itemIds, addressId) {
  return request({
    url: '/mall/cart/checkout',
    method: 'POST',
    data: { item_ids: itemIds, address_id: addressId },
    timeout: 30000, // 下单可能较慢
  })
}

/**
 * 更新购物车商品数量
 * @param {number} itemId 购物车项目 ID
 * @param {number} qty 新数量
 * @returns {Promise<Object>}
 */
function updateCartItem(itemId, qty) {
  return request({
    url: `/mall/cart/items/${itemId}`,
    method: 'PUT',
    data: { qty },
  })
}

/**
 * 删除购物车商品
 * @param {number} itemId 购物车项目 ID
 * @returns {Promise<Object>}
 */
function removeCartItem(itemId) {
  return request({
    url: `/mall/cart/items/${itemId}`,
    method: 'DELETE',
  })
}

/**
 * 清空购物车
 * @returns {Promise<Object>}
 */
function clearCart() {
  return request({
    url: '/mall/cart/clear',
    method: 'POST',
  })
}

// ==================== 订单 ====================

/**
 * 获取订单列表
 * @param {Object} [params]
 * @param {string} [params.status] 订单状态
 * @param {string} [params.keyword] 搜索关键字
 * @param {number} [params.limit] 每页条数（默认20）
 * @returns {Promise<Object>}
 */
function getOrders(params) {
  return request({
    url: '/mall/orders',
    method: 'GET',
    data: params,
  })
}

/**
 * 获取订单详情
 * @param {number} id 订单 ID
 * @returns {Promise<Object>}
 */
function getOrder(id) {
  return request({
    url: `/mall/orders/${id}`,
    method: 'GET',
  })
}

/**
 * 创建订单
 * @param {Object} data
 * @param {Array<{sku_id: number, qty: number, remark?: string}>} data.items 商品列表
 * @param {number} data.address_id 收货地址 ID
 * @returns {Promise<null>}
 */
function createOrder(data) {
  return request({
    url: '/mall/orders',
    method: 'POST',
    data,
    timeout: 30000,
  })
}

/**
 * 取消订单
 * @param {number} id 订单 ID
 * @returns {Promise<null>}
 */
function cancelOrder(id) {
  return request({
    url: `/mall/orders/${id}/cancel`,
    method: 'POST',
  })
}

/**
 * 删除订单
 * @param {number} id 订单 ID
 * @returns {Promise<null>}
 */
function deleteOrder(id) {
  return request({
    url: `/mall/orders/${id}`,
    method: 'DELETE',
  })
}

module.exports = {
  // 首页
  getIndex,
  getBrands,
  getBanners,
  // 分类
  getCategories,
  getCategory,
  // 商品
  getProducts,
  getProduct,
  // 购物车
  getCart,
  addToCart,
  previewCheckout,
  checkout,
  updateCartItem,
  removeCartItem,
  clearCart,
  // 订单
  getOrders,
  getOrder,
  createOrder,
  cancelOrder,
  deleteOrder,
}
