<?php
/**
 * 认证中间件
 */

namespace PandaAPI\Middleware;

class AuthMiddleware
{
    /**
     * @var array 配置
     */
    protected $config = [
        'token_header' => 'Authorization',
        'token_prefix' => 'Bearer',
    ];

    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 处理请求
     */
    public function handle($request, callable $next)
    {
        $header = $this->config['token_header'];
        $token = $request->header($header);
        
        if (empty($token)) {
            // 尝试从GET参数获取
            $token = $request->get('token');
        }
        
        if (empty($token)) {
            return \PandaAPI\Core\Response::json([
                'error' => 'Unauthorized',
                'message' => 'Authentication token required'
            ], 401);
        }
        
        // 处理Bearer Token
        if (strpos($token, $this->config['token_prefix'] . ' ') === 0) {
            $token = substr($token, strlen($this->config['token_prefix']) + 1);
        }
        
        // 验证Token（这里需要结合实际业务实现）
        $user = $this->validateToken($token);
        
        if ($user === null) {
            return \QuickAPI\Core\Response::json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or expired token'
            ], 401);
        }
        
        // 将用户信息注入请求
        $request->setUser($user);
        
        return $next($request);
    }

    /**
     * 验证Token（需要结合实际业务实现）
     */
    protected function validateToken(string $token): ?array
    {
        // 示例实现 - 实际项目中需要连接数据库或Redis验证
        // 这里简单验证token格式
        if (strlen($token) < 10) {
            return null;
        }
        
        // 返回模拟用户数据
        return [
            'id' => 1,
            'name' => 'User',
            'token' => $token
        ];
    }
}
