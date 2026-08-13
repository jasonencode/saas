/**
 * Content - 内容管理模块
 *
 * 接口文档: docs/apis/content.md
 */
const { request } = require('../utils/request')

/**
 * 获取内容列表
 * @returns {Promise<Object>}
 */
function getContents() {
  return request({
    url: '/contents',
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 获取内容详情
 * @param {number} id 内容 ID
 * @returns {Promise<Object>}
 */
function getContent(id) {
  return request({
    url: `/contents/${id}`,
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 获取内容评论列表
 * @param {number} contentId 内容 ID
 * @param {Object} [params]
 * @param {number} [params.per_page] 每页条数（默认15，最大50）
 * @returns {Promise<Object>}
 */
function getComments(contentId, params) {
  return request({
    url: `/contents/${contentId}/comments`,
    method: 'GET',
    data: params,
    noAuth: true,
  })
}

/**
 * 发表评论
 * @param {number} contentId 内容 ID
 * @param {Object} data
 * @param {string} [data.content] 评论内容（与pictures二选一）
 * @param {number} [data.star] 评分（1-5）
 * @param {string[]} [data.pictures] 图片列表
 * @returns {Promise<Object>}
 */
function createComment(contentId, data) {
  return request({
    url: `/contents/${contentId}/comments`,
    method: 'POST',
    data,
  })
}

/**
 * 获取内容分类列表
 * @returns {Promise<Array>}
 */
function getContentCategories() {
  return request({
    url: '/contents/categories',
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 获取内容分类详情
 * @param {number} id 分类 ID
 * @returns {Promise<Object>}
 */
function getContentCategory(id) {
  return request({
    url: `/contents/categories/${id}`,
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 内容标签列表
 * @returns {Promise<Array>}
 */
function getContentTags() {
  return request({
    url: '/contents/tags',
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 单页内容列表
 * @returns {Promise<Object>}
 */
function getSinglePages() {
  return request({
    url: '/contents/single-pages',
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 单页内容详情
 * @param {string} slug 单页别名
 * @returns {Promise<Object>}
 */
function getSinglePage(slug) {
  return request({
    url: `/contents/single-pages/${slug}`,
    method: 'GET',
    noAuth: true,
  })
}

module.exports = {
  getContents,
  getContent,
  getComments,
  createComment,
  getContentCategories,
  getContentCategory,
  getContentTags,
  getSinglePages,
  getSinglePage,
}
