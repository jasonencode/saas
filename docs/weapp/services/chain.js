/**
 * Chain - 区块链模块
 *
 * 接口文档: docs/apis/chain.md
 */
const { request } = require('../utils/request')

/**
 * 获取区块链网络列表
 * @returns {Promise<Array>}
 */
function getNetworks() {
  return request({
    url: '/chain/networks',
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 获取合约列表
 * @returns {Promise<Object>}
 */
function getContracts() {
  return request({
    url: '/chain/contracts',
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 获取合约详情
 * @param {number} id 合约 ID
 * @returns {Promise<Object>}
 */
function getContract(id) {
  return request({
    url: `/chain/contracts/${id}`,
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 获取证书列表
 * @returns {Promise<Object>}
 */
function getCertificates() {
  return request({
    url: '/chain/certificates',
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 创建证书
 * @returns {Promise<Object>}
 */
function createCertificate() {
  return request({
    url: '/chain/certificates',
    method: 'POST',
    noAuth: true,
  })
}

/**
 * 获取证书详情
 * @param {number} id 证书 ID
 * @returns {Promise<Object>}
 */
function getCertificate(id) {
  return request({
    url: `/chain/certificates/${id}`,
    method: 'GET',
    noAuth: true,
  })
}

/**
 * 获取区块链地址列表
 * @returns {Promise<Object>}
 */
function getChainAddresses() {
  return request({
    url: '/chain/addresses',
    method: 'GET',
    noAuth: true,
  })
}

module.exports = {
  getNetworks,
  getContracts,
  getContract,
  getCertificates,
  createCertificate,
  getCertificate,
  getChainAddresses,
}
