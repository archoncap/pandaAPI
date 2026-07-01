# QuickAPI API 测试脚本

## 功能测试

### 1. 健康检查

```bash
curl http://localhost:8080/api/v1/health
```

预期响应：
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "status": "healthy",
    "time": "2024-01-01 12:00:00",
    "version": "1.0.0",
    "db_queries": 0
  }
}
```

### 2. 用户列表

```bash
# 基础列表
curl http://localhost:8080/api/v1/users

# 带分页
curl "http://localhost:8080/api/v1/users?page=1&per_page=5"

# 筛选状态
curl "http://localhost:8080/api/v1/users?status=1"
```

### 3. 用户详情

```bash
curl http://localhost:8080/api/v1/users/1
```

### 4. 创建用户

```bash
curl -X POST http://localhost:8080/api/v1/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "测试用户",
    "email": "test@example.com",
    "password": "123456"
  }'
```

### 5. 更新用户

```bash
curl -X PUT http://localhost:8080/api/v1/users/1 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "新名称"
  }'
```

### 6. 删除用户

```bash
curl -X DELETE http://localhost:8080/api/v1/users/10
```

### 7. 文章列表

```bash
curl http://localhost:8080/api/v1/posts
curl "http://localhost:8080/api/v1/posts?page=1&per_page=5"
```

### 8. 文章详情

```bash
curl http://localhost:8080/api/v1/posts/1
```

### 9. 创建文章

```bash
curl -X POST http://localhost:8080/api/v1/posts \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "title": "测试文章",
    "content": "这是一篇测试文章的内容"
  }'
```

### 10. 统计接口

```bash
curl http://localhost:8080/api/v1/stats
```

### 11. 缓存测试

```bash
# 测试缓存
curl http://localhost:8080/api/v1/cache/test

# 清除缓存
curl -X DELETE http://localhost:8080/api/v1/cache
```

### 12. 事务测试

```bash
curl -X POST http://localhost:8080/api/v1/transaction \
  -H "Content-Type: application/json"
```

### 13. 用户登录

```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "zhangsan@example.com",
    "password": "123456"
  }'
```

### 14. 用户注册

```bash
curl -X POST http://localhost:8080/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "新用户",
    "email": "newuser@example.com",
    "password": "123456"
  }'
```

## PHP 测试脚本

```php
<?php
/**
 * API 功能测试脚本
 */

$baseUrl = 'http://localhost:8080/api/v1';

echo "========================================\n";
echo "QuickAPI 功能测试\n";
echo "========================================\n\n";

/**
 * 发送HTTP请求
 */
function request($method, $path, $data = null, $token = null) {
    global $baseUrl;
    
    $url = $baseUrl . $path;
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

echo "[1] 健康检查\n";
$result = request('GET', '/health');
echo "状态码: {$result['code']}\n";
echo "响应: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n\n";

echo "[2] 用户列表\n";
$result = request('GET', '/users');
echo "状态码: {$result['code']}\n";
echo "用户数: " . ($result['data']['pagination']['total'] ?? 0) . "\n\n";

echo "[3] 用户详情\n";
$result = request('GET', '/users/1');
echo "状态码: {$result['code']}\n";
echo "用户名: " . ($result['data']['data']['name'] ?? 'N/A') . "\n\n";

echo "[4] 用户登录\n";
$result = request('POST', '/auth/login', [
    'email' => 'zhangsan@example.com',
    'password' => '123456'
]);
echo "状态码: {$result['code']}\n";
$token = $result['data']['data']['token'] ?? null;
echo "Token: " . substr($token, 0, 20) . "...\n\n";

if ($token) {
    echo "[5] 获取当前用户\n";
    $result = request('GET', '/auth/me', null, $token);
    echo "状态码: {$result['code']}\n";
    echo "用户: " . ($result['data']['data']['name'] ?? 'N/A') . "\n\n";
    
    echo "[6] 个人资料（需认证）\n";
    $result = request('GET', '/private/profile', null, $token);
    echo "状态码: {$result['code']}\n\n";
}

echo "[7] 统计信息\n";
$result = request('GET', '/stats');
echo "状态码: {$result['code']}\n";
echo "用户数: " . ($result['data']['data']['users_count'] ?? 0) . "\n";
echo "文章数: " . ($result['data']['data']['posts_count'] ?? 0) . "\n\n";

echo "[8] 缓存测试\n";
$result = request('GET', '/cache/test');
echo "状态码: {$result['code']}\n";
echo "计数器: " . ($result['data']['data']['counter'] ?? 0) . "\n\n";

echo "========================================\n";
echo "测试完成！\n";
echo "========================================\n";
