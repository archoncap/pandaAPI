<?php
/**
 * 验证中间件
 */

namespace PandaAPI\Middleware;

class ValidateMiddleware
{
    /**
     * @var array 验证规则
     */
    protected $rules = [];

    /**
     * @var array 自定义错误消息
     */
    protected $messages = [];

    /**
     * 构造函数
     */
    public function __construct(array $rules = [], array $messages = [])
    {
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * 处理请求
     */
    public function handle($request, callable $next)
    {
        $data = array_merge($request->all(), $request->query());
        
        $validator = new \PandaAPI\Validation\Validator($data, $this->rules, $this->messages);
        
        if ($validator->fails()) {
            return \PandaAPI\Core\Response::json([
                'error' => 'Validation Failed',
                'messages' => $validator->errors()
            ], 422);
        }
        
        // 注入验证后的数据
        $request->merge($validator->validated());
        
        return $next($request);
    }
}
