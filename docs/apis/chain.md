# Chain - 区块链 API

**前缀**: `/chain`  
**认证**: 全部接口需要 `auth:sanctum` 中间件

---

## 网络

### 1. 区块链网络列表

```
GET /chain/networks
```

获取已启用的区块链网络列表。

---

## 智能合约

### 2. 合约列表

```
GET /chain/contracts
```

### 3. 合约详情

```
GET /chain/contracts/{contract}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| contract | int | 合约 ID |

仅返回已部署的合约。

---

## 证书

### 4. 证书列表

```
GET /chain/certificates
```

### 5. 创建证书

```
POST /chain/certificates
```

### 6. 证书详情

```
GET /chain/certificates/{certificate}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| certificate | int | 证书 ID |

---

## 区块链地址

### 7. 区块链地址列表

```
GET /chain/addresses
```
