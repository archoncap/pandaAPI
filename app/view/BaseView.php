<?php
/**
 * 视图基类
 * 
 * 提供模板渲染能力
 * API 框架中主要用于生成 JSON 响应
 * 也可用于渲染 HTML 页面（如 API 文档页）
 */
namespace App\View;

class BaseView
{
    /** @var string 模板目录 */
    protected $templateDir;

    public function __construct()
    {
        $this->templateDir = dirname(__DIR__) . '/view/templates/';
    }

    /**
     * 渲染模板
     * 
     * @param string $template 模板文件名
     * @param array $data 模板变量
     * @return string
     */
    public function render(string $template, array $data = []): string
    {
        $file = $this->templateDir . $template . '.php';
        if (!file_exists($file)) {
            return "Template not found: {$template}";
        }

        // 提取变量到当前作用域
        extract($data, EXTR_SKIP);

        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * 渲染 JSON 响应
     * 
     * @param array $data 响应数据
     * @return string
     */
    public function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * 渲染错误页面
     */
    public function error(int $code, string $message): string
    {
        return $this->render('error', [
            'code'    => $code,
            'message' => $message,
        ]);
    }
}
