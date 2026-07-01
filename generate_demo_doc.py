#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
熊猫API框架 Demo 项目代码文档生成器
Macan 格式 - 详细展示每个代码片段
- 代码块使用 XPreformatted 保留换行和缩进
- PHP 语法高亮
- 1.5 倍行距
"""

import re
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.lib.colors import HexColor, black, white
from reportlab.lib.styles import ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    PageBreak, Preformatted
)
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.enums import TA_LEFT, TA_CENTER

# ============================================================
# 注册中文字体
# ============================================================
pdfmetrics.registerFont(
    TTFont('WQY', '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc', subfontIndex=0)
)
pdfmetrics.registerFont(
    TTFont('Mono', '/usr/share/fonts/truetype/liberation2/LiberationMono-Regular.ttf')
)
pdfmetrics.registerFont(
    TTFont('MonoBold', '/usr/share/fonts/truetype/liberation2/LiberationMono-Bold.ttf')
)
CJK = 'WQY'
MONO = 'Mono'

# ============================================================
# 语法高亮颜色（VS Code Dark+ 舒适配色）
# ============================================================
C_KEYWORD   = HexColor('#c586c0')   # 淡紫   - if/else/return/function/class
C_STRING    = HexColor('#ce9178')   # 暖橙   - 字符串
C_COMMENT   = HexColor('#d4d4d4')   # 奶白   - 注释（高对比度）
C_NUMBER    = HexColor('#b5cea8')   # 浅绿   - 数字
C_VARIABLE  = HexColor('#9cdcfe')   # 浅蓝   - $变量
C_FUNCTION  = HexColor('#dcdcaa')   # 淡黄   - 函数名
C_TAG       = HexColor('#c586c0')   # 淡紫   - PHP标签
C_DEFAULT   = HexColor('#d4d4d4')   # 浅灰白 - 默认文字
C_BUILTIN   = HexColor('#4ec9b0')   # 青绿   - 类名 PandaDB/Route
C_OPERATOR  = HexColor('#d4d4d4')   # 白色   - => ->
C_CODE_BG   = HexColor('#1e1e1e')   # 纯深色背景
C_LINE_NUM  = HexColor('#4b5263')   # 行号颜色

# ============================================================
# 文档颜色
# ============================================================
PRIMARY   = HexColor('#1a365d')
ACCENT    = HexColor('#2b6cb0')
LIGHT_BG  = HexColor('#f7fafc')
GRAY      = HexColor('#718096')
DARK      = HexColor('#2d3748')
WHITE     = HexColor('#ffffff')

# ============================================================
# 页面参数
# ============================================================
PAGE_W, PAGE_H = A4
LM = RM = 15 * mm
TM = BM = 15 * mm
CW = PAGE_W - LM - RM

# ============================================================
# 样式
# ============================================================
S = {
    'title':    ParagraphStyle('T', fontName=CJK, fontSize=24, leading=32,
                              textColor=PRIMARY, alignment=TA_CENTER, spaceAfter=12),
    'subtitle': ParagraphStyle('ST', fontName=CJK, fontSize=12, leading=16,
                              textColor=GRAY, alignment=TA_CENTER, spaceAfter=20),
    'h1':       ParagraphStyle('H1', fontName=CJK, fontSize=18, leading=26,
                              textColor=PRIMARY, spaceBefore=24, spaceAfter=10),
    'h2':       ParagraphStyle('H2', fontName=CJK, fontSize=13, leading=19,
                              textColor=ACCENT, spaceBefore=16, spaceAfter=6),
    'h3':       ParagraphStyle('H3', fontName=CJK, fontSize=11, leading=16,
                              textColor=DARK, spaceBefore=12, spaceAfter=6),
    'body':     ParagraphStyle('B', fontName=CJK, fontSize=10, leading=16,
                              textColor=DARK, spaceAfter=6),
    'li':       ParagraphStyle('LI', fontName=CJK, fontSize=10, leading=16,
                              textColor=DARK, leftIndent=20, spaceAfter=4),
    'caption':  ParagraphStyle('CAP', fontName=CJK, fontSize=8, leading=12,
                              textColor=GRAY, alignment=TA_CENTER,
                              spaceBefore=4, spaceAfter=10),
    'code_title': ParagraphStyle('CT', fontName=CJK, fontSize=9, leading=13,
                              textColor=WHITE, backColor=ACCENT,
                              leftIndent=10, rightIndent=10,
                              spaceBefore=8, spaceAfter=0),
    # 代码块样式 - 深色背景，1.5倍行距
    'code_xpre': ParagraphStyle('CX', fontName=MONO, fontSize=7.5, leading=12,
                              textColor=C_DEFAULT, backColor=C_CODE_BG,
                              leftIndent=10, rightIndent=10,
                              spaceBefore=8, spaceAfter=8,
                              borderPadding=8),
}

# ============================================================
# PHP 语法高亮
# ============================================================
PHP_KEYWORDS = {
    'if', 'else', 'elseif', 'while', 'for', 'foreach', 'foreach', 'as',
    'return', 'function', 'class', 'new', 'public', 'private', 'protected',
    'static', 'var', 'const', 'try', 'catch', 'finally', 'throw',
    'use', 'namespace', 'require_once', 'require', 'include', 'include_once',
    'true', 'false', 'null', 'array', 'echo', 'print', 'isset', 'unset',
    'empty', 'switch', 'case', 'break', 'continue', 'default',
    'abstract', 'interface', 'implements', 'extends', 'trait',
}

PHP_BUILTINS = {
    'count', 'strlen', 'substr', 'explode', 'implode', 'array_keys',
    'array_values', 'array_column', 'array_merge', 'array_fill',
    'array_rand', 'in_array', 'is_array', 'is_string', 'is_int',
    'is_null', 'date', 'time', 'strtotime', 'microtime', 'json_encode',
    'json_decode', 'password_hash', 'password_verify', 'bin2hex',
    'random_bytes', 'intval', 'floatval', 'strval', 'trim', 'strtolower',
    'strtoupper', 'preg_match', 'preg_replace', 'curl_init', 'curl_setopt',
    'curl_exec', 'curl_getinfo', 'curl_close', 'max', 'min', 'round',
    'ceil', 'floor', 'abs', 'sprintf', 'printf', 'var_dump', 'print_r',
    'end', 'array_shift', 'array_pop', 'array_push', 'array_map',
    'array_filter', 'array_reduce', 'array_unique', 'array_slice',
    'sort', 'usort', 'ksort', 'array_key_exists', 'array_search',
    'func_num_args', 'func_get_args', 'call_user_func',
    'method_exists', 'property_exists', 'class_exists', 'get_class',
    'gettype', 'settype', 'defined', 'define', 'compact', 'extract',
    'header', 'exit', 'session_status', 'session_write_close',
    'ini_set', 'file_exists', 'file_get_contents', 'file_put_contents',
}

def php_highlight(line):
    """对单行 PHP 代码进行语法高亮，返回 reportlab XML 标签字符串"""
    # 先处理注释行
    stripped = line.lstrip()
    if stripped.startswith('//') or stripped.startswith('#'):
        return f'<font name="{CJK}" color="{C_COMMENT.hexval()}">{esc(line)}</font>'
    if stripped.startswith('/*') or stripped.startswith('*'):
        return f'<font name="{CJK}" color="{C_COMMENT.hexval()}">{esc(line)}</font>'

    result = ''
    i = 0
    n = len(line)

    while i < n:
        c = line[i]

        # 单行注释
        if c == '/' and i + 1 < n and line[i + 1] == '/':
            result += f'<font name="{CJK}" color="{C_COMMENT.hexval()}">{esc(line[i:])}</font>'
            break

        if c == '/' and i + 1 < n and line[i + 1] == '*':
            result += f'<font name="{CJK}" color="{C_COMMENT.hexval()}">{esc(line[i:])}</font>'
            break

        # 字符串（单引号和双引号）
        if c in ('"', "'"):
            quote = c
            j = i + 1
            while j < n and line[j] != quote:
                if line[j] == '\\':
                    j += 1
                j += 1
            j = min(j + 1, n)
            result += f'<font color="{C_STRING.hexval()}">{esc(line[i:j])}</font>'
            i = j
            continue

        # $变量
        if c == '$':
            j = i + 1
            while j < n and (line[j].isalnum() or line[j] == '_'):
                j += 1
            result += f'<font color="{C_VARIABLE.hexval()}">{esc(line[i:j])}</font>'
            i = j
            continue

        # 数字
        if c.isdigit():
            j = i
            while j < n and (line[j].isdigit() or line[j] == '.'):
                j += 1
            result += f'<font color="{C_NUMBER.hexval()}">{esc(line[i:j])}</font>'
            i = j
            continue

        # 标识符 / 关键字 / 函数
        if c.isalpha() or c == '_':
            j = i
            while j < n and (line[j].isalnum() or line[j] == '_'):
                j += 1
            word = line[i:j]

            # PHP 开标签
            if word in ('php', '?php'):
                result += f'<font color="{C_TAG.hexval()}">&lt;?php</font>'
                i = j
                continue

            # 看后面是否跟着 (
            k = j
            while k < n and line[k] == ' ':
                k += 1

            if word.lower() in PHP_KEYWORDS:
                result += f'<font color="{C_KEYWORD.hexval()}">{esc(word)}</font>'
            elif word in PHP_BUILTINS or (k < n and line[k] == '('):
                result += f'<font color="{C_FUNCTION.hexval()}">{esc(word)}</font>'
            elif word[0].isupper():
                # 类名如 PandaDB, Route, Response
                result += f'<font color="{C_BUILTIN.hexval()}">{esc(word)}</font>'
            else:
                result += esc(word)
            i = j
            continue

        # -> 和 => 操作符
        if c == '-' and i + 1 < n and line[i + 1] == '>':
            result += f'<font color="{C_OPERATOR.hexval()}">-&gt;</font>'
            i += 2
            continue
        if c == '=' and i + 1 < n and line[i + 1] == '>':
            result += f'<font color="{C_OPERATOR.hexval()}">=&gt;</font>'
            i += 2
            continue

        # < 特殊处理（可能是 HTML 标签或 PHP 标签）
        if c == '<':
            if line[i:i+5] == '<?php':
                result += f'<font color="{C_TAG.hexval()}">&lt;?php</font>'
                i += 5
                continue
            else:
                result += '&lt;'
                i += 1
                continue

        if c == '>':
            result += '&gt;'
            i += 1
            continue

        if c == '&':
            result += '&amp;'
            i += 1
            continue

        # 默认字符
        result += c
        i += 1

    return result


def esc(text):
    """转义 XML 特殊字符"""
    return text.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;')


def wrap_cjk(text):
    """后处理：将尚未被 font 标签包裹的中文字符用 CJK 字体包裹"""
    def _replace(m):
        char = m.group(0)
        return f'<font name="{CJK}">{char}</font>'
    parts = re.split(r'(<font[^>]*>.*?</font>)', text, flags=re.DOTALL)
    result = []
    for i, part in enumerate(parts):
        if part.startswith('<font'):
            result.append(part)
        else:
            result.append(re.sub(r'[\u4e00-\u9fff\u3000-\u303f\uff00-\uffef\u2000-\u206f]', _replace, part))
    return ''.join(result)


def clean_code(text):
    """清理代码：去掉装饰性分隔线"""
    text = re.sub(r'//\s*={5,}.*?={5,}\s*$', '', text, flags=re.MULTILINE)
    text = re.sub(r'//\s*-{5,}\s*$', '', text, flags=re.MULTILINE)
    # 去掉多余空行（保留最多一个空行）
    text = re.sub(r'\n{3,}', '\n\n', text)
    return text.strip()


# ============================================================
# 工具函数
# ============================================================
def p(text, style='body'):
    return Paragraph(text, S[style])

def sp(h=8):
    return Spacer(1, h)

def code(text, title=None):
    """生成带语法高亮的代码块（深色主题）"""
    elements = []
    if title:
        elements.append(Paragraph(title, S['code_title']))

    text = clean_code(text)
    lines = text.split('\n')

    # 逐行高亮
    highlighted_lines = []
    for line in lines:
        highlighted = php_highlight(line)
        highlighted = wrap_cjk(highlighted)
        highlighted_lines.append(highlighted)

    # 用 <br/> 换行，&nbsp; 保留空格缩进
    code_html = '<br/>'.join(highlighted_lines)
    # 将行首空格转为 &nbsp;
    code_html = re.sub(r'^  ', lambda m: '&nbsp;' * len(m.group()), code_html, flags=re.MULTILINE)
    code_html = re.sub(r'    ', '&nbsp;&nbsp;&nbsp;&nbsp;', code_html)

    # 用 Paragraph 解析 HTML 标签（font color）
    code_block = Paragraph(code_html, S['code_xpre'])

    elements.append(code_block)
    return elements


def li(text):
    return Paragraph('• ' + text, S['li'])


# ============================================================
# 代码片段数据
# ============================================================

CODE_SNIPPETS = {
    'index.php': {
        'description': 'Demo 项目主入口文件，包含完整的 API 路由定义和所有功能示例',
        'sections': [
            {
                'title': '片段 1：文件头部和命名空间导入',
                'code': '''<?php
/**
 * 熊猫API框架 (PandaAPI) 完整演示项目
 * 本Demo展示了框架的所有功能使用方法
 * 包含：PandaDB数据库操作、缓存系统、路由、中间件、验证器等
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PandaAPI\\Core\\App;
use PandaAPI\\Core\\Request;
use PandaAPI\\Core\\Response;
use PandaAPI\\Database\\PandaDB;
use PandaAPI\\Route\\Route;
use PandaAPI\\Cache\\Cache;
use PandaAPI\\Validation\\Validator;'''
            },
            {
                'title': '片段 2：数据库和缓存配置',
                'code': '''// 配置数据库连接（使用 PandaDB）
PandaDB::config([
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'pandaapi_demo',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
    'prefix'   => 'panda_',
]);

// 配置缓存（支持：file/redis/memcache/openresty）
Cache::config([
    'driver' => 'file',
    'file' => [
        'path' => __DIR__ . '/../runtime/cache',
    ],
    'default_ttl' => 3600,
]);

// 设置慢查询阈值（毫秒）
PandaDB::setSlowThreshold(100);'''
            },
            {
                'title': '片段 3：数据库初始化函数',
                'code': '''/**
 * 初始化数据库表结构
 * 实际项目中通常通过迁移工具管理
 */
function initDatabase()
{
    // 创建用户表
    $createUsersTable = "
        CREATE TABLE IF NOT EXISTS panda_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            status TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // 创建文章表
    $createPostsTable = "
        CREATE TABLE IF NOT EXISTS panda_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            views INT DEFAULT 0,
            status TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES panda_users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    try {
        PandaDB::execute($createUsersTable);
        PandaDB::execute($createPostsTable);
        return true;
    } catch (Exception $e) {
        return false;
    }
}'''
            },
            {
                'title': '片段 4：健康检查接口',
                'code': '''/**
 * 健康检查接口
 * GET /api/v1/health
 */
Route::get('/api/v1/health', function() {
    return Response::success([
        'framework'  => 'PandaAPI',
        'name'       => '熊猫API框架',
        'status'     => 'healthy',
        'time'       => date('Y-m-d H:i:s'),
        'version'    => '1.0.0',
        'db_queries' => PandaDB::getQueryCount(),
        'uptime'     => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
    ]);
});'''
            },
            {
                'title': '片段 5：路由组定义',
                'code': '''/**
 * API版本组 v1
 * 所有接口统一前缀 /api/v1，并启用 CORS 中间件
 */
Route::group(['prefix' => '/api/v1', 'middleware' => ['Cors']], function() {

    // 用户相关接口 (公开)
    // 文章相关接口 (公开)
    // 聚合查询示例
    // 缓存操作示例
    // 原生SQL查询示例
    // 事务示例
    // PandaDB 风格快捷方法示例

});'''
            },
            {
                'title': '片段 6：用户列表接口（ThinkPHP 风格分页）',
                'code': '''/**
 * 获取用户列表
 * GET /api/v1/users?page=1&per_page=15&status=1
 */
Route::get('/users', function() {
    $request = new Request();
    $page    = (int)$request->query('page', 1);
    $perPage = (int)$request->query('per_page', 15);
    $status  = $request->query('status');

    // 使用 PandaDB 构建查询
    $builder = PandaDB::table('users');

    // 条件筛选
    if ($status !== null) {
        $builder->where('status', (int)$status);
    }

    // 排序 + 分页查询
    $builder->order('created_at', 'desc');
    $result = $builder->paginate($perPage, $page);

    return Response::paginate(
        $result['data'],
        $result['total'],
        $result['current_page'],
        $result['per_page']
    );
});'''
            },
            {
                'title': '片段 7：用户详情接口（缓存穿透回源）',
                'code': '''/**
 * 获取单个用户
 * GET /api/v1/users/{id}
 *
 * 使用缓存示例：先查缓存，不存在则查数据库
 */
Route::get('/users/{id}', function($id) {
    $cacheKey = "user:{$id}";

    // Cache::remember 自动处理缓存穿透
    $user = Cache::remember($cacheKey, function() use ($id) {
        return PandaDB::table('users')
            ->where('id', (int)$id)
            ->find();
    }, 300); // 缓存5分钟

    if (!$user) {
        return Response::error('用户不存在', 404);
    }

    return Response::success($user);
})->whereNumber('id');'''
            },
            {
                'title': '片段 8：创建用户接口（数据验证 + 唯一性检查）',
                'code': '''/**
 * 创建用户
 * POST /api/v1/users
 */
Route::post('/users', function() {
    $request = new Request();
    $data = $request->all();

    // 数据验证
    $validator = new Validator($data, [
        'name'     => 'required|string|min:2|max:50',
        'email'    => 'required|email',
        'password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
        return Response::json([
            'error'    => '验证失败',
            'messages' => $validator->errors()
        ], 422);
    }

    // 检查邮箱唯一性
    $exists = PandaDB::table('users')
        ->where('email', $data['email'])
        ->count();

    if ($exists > 0) {
        return Response::error('邮箱已被注册', 422);
    }

    // 密码加密 + 插入
    $data['password']   = password_hash($data['password'], PASSWORD_DEFAULT);
    $data['created_at'] = date('Y-m-d H:i:s');
    $id = PandaDB::table('users')->insert($data);

    // 返回创建的用户（移除密码字段）
    $user = PandaDB::table('users')->where('id', $id)->find();
    unset($user['password']);

    return Response::json($user, 201);
});'''
            },
            {
                'title': '片段 9：更新和删除用户接口',
                'code': '''/**
 * 更新用户  PUT /api/v1/users/{id}
 */
Route::put('/users/{id}', function($id) {
    $request = new Request();
    $data = $request->all();

    $validator = new Validator($data, [
        'name'  => 'string|min:2|max:50',
        'email' => 'email',
    ]);

    if ($validator->fails()) {
        return Response::json(['errors' => $validator->errors()], 422);
    }

    $user = PandaDB::table('users')->where('id', (int)$id)->find();
    if (!$user) {
        return Response::error('用户不存在', 404);
    }

    if (!empty($data)) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        PandaDB::table('users')->where('id', (int)$id)->update($data);
        Cache::forget("user:{$id}"); // 清除缓存
    }

    $user = PandaDB::table('users')->where('id', (int)$id)->find();
    unset($user['password']);
    return Response::success($user);
})->whereNumber('id');

/**
 * 删除用户  DELETE /api/v1/users/{id}
 */
Route::delete('/users/{id}', function($id) {
    $user = PandaDB::table('users')->where('id', (int)$id)->find();
    if (!$user) {
        return Response::error('用户不存在', 404);
    }

    PandaDB::table('users')->where('id', (int)$id)->delete();
    Cache::forget("user:{$id}");

    return Response::success(['message' => '删除成功']);
})->whereNumber('id');'''
            },
            {
                'title': '片段 10：文章列表（JOIN 关联查询）',
                'code': '''/**
 * 获取文章列表
 * GET /api/v1/posts?page=1&per_page=10
 */
Route::get('/posts', function() {
    $request = new Request();
    $page    = (int)$request->query('page', 1);
    $perPage = (int)$request->query('per_page', 10);

    // JOIN查询示例：关联用户表获取作者名
    $result = PandaDB::table('posts')
        ->field('posts.*, users.name as author_name')
        ->join('users', 'posts.user_id = users.id')
        ->where('posts.status', 1)
        ->order('posts.created_at', 'desc')
        ->paginate($perPage, $page);

    return Response::paginate(
        $result['data'],
        $result['total'],
        $result['current_page'],
        $result['per_page']
    );
});'''
            },
            {
                'title': '片段 11：聚合统计接口',
                'code': '''/**
 * 统计接口
 * GET /api/v1/stats
 */
Route::get('/stats', function() {
    $stats = [
        'users_count'     => PandaDB::table('users')->count(),
        'posts_count'     => PandaDB::table('posts')->count(),
        'posts_views_sum' => PandaDB::table('posts')->sum('views'),
        'posts_avg_views' => round(PandaDB::table('posts')->avg('views'), 2),
        'active_users'    => PandaDB::table('users')
                              ->where('status', 1)->count(),
    ];

    return Response::success($stats);
});'''
            },
            {
                'title': '片段 12：缓存操作示例',
                'code': '''/**
 * 缓存测试接口
 * GET /api/v1/cache/test
 */
Route::get('/cache/test', function() {
    $key = 'test:counter';

    // 原子递增
    if (!Cache::has($key)) {
        Cache::set($key, 0, 3600);
    }
    $count = Cache::increment($key);

    // 批量操作
    Cache::setMultiple([
        'cache:item1' => 'value1',
        'cache:item2' => 'value2',
        'cache:item3' => ['array' => 'data'],
    ], 600);

    $items = Cache::getMultiple([
        'cache:item1', 'cache:item2', 'cache:item3'
    ]);

    return Response::success([
        'counter' => $count,
        'items'   => $items,
    ]);
});'''
            },
            {
                'title': '片段 13：原生 SQL 查询',
                'code': '''/**
 * 原生SQL查询
 * GET /api/v1/sql
 */
Route::get('/sql', function() {
    // 原生查询（带参数绑定，防止SQL注入）
    $users = PandaDB::query(
        "SELECT * FROM panda_users
         WHERE status = ?
         ORDER BY created_at DESC
         LIMIT 10",
        [1]
    );

    // 原生聚合查询
    $avgViews = PandaDB::query(
        "SELECT AVG(views) as avg_views
         FROM panda_posts
         WHERE status = 1"
    );

    return Response::success([
        'users'     => $users,
        'avg_views' => $avgViews[0]['avg_views'] ?? 0,
    ]);
});'''
            },
            {
                'title': '片段 14：事务操作示例',
                'code': '''/**
 * 事务操作示例
 * POST /api/v1/transaction
 */
Route::post('/transaction', function() {
    try {
        // PandaDB::transaction 自动提交/回滚
        $result = PandaDB::transaction(function() {
            // 创建用户
            $userId = PandaDB::table('users')->insert([
                'name'     => '事务测试用户',
                'email'    => 'transaction_' . time() . '@test.com',
                'password' => password_hash('123456', PASSWORD_DEFAULT),
                'status'   => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // 创建用户的默认文章
            PandaDB::table('posts')->insert([
                'user_id'    => $userId,
                'title'      => '欢迎文章',
                'content'    => '这是用户的欢迎文章',
                'status'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return ['user_id' => $userId];
        });

        return Response::success($result);
    } catch (Exception $e) {
        return Response::error('事务失败: ' . $e->getMessage(), 500);
    }
});'''
            },
            {
                'title': '片段 15：PandaDB 风格快捷方法',
                'code': '''/**
 * PandaDB 双风格操作演示
 * GET /api/v1/pandadb-demo
 */
Route::get('/pandadb-demo', function() {
    // PandaDB 风格快捷方法
    $user = PandaDB::get(
        'users',
        ['id', 'name', 'email'],
        ['id' => 1]
    );

    // ThinkPHP 风格链式调用
    $users = PandaDB::table('users')
        ->where('status', 1)
        ->order('id', 'desc')
        ->limit(5)
        ->select();

    // name() 自动添加表前缀
    $count = PandaDB::name('users')->count();

    return Response::success([
        'single_user'  => $user,
        'active_users' => $users,
        'total_count'  => $count,
    ]);
});'''
            },
            {
                'title': '片段 16：用户登录接口',
                'code': '''/**
 * 用户登录
 * POST /api/v1/auth/login
 */
Route::post('/login', function() {
    $request = new Request();
    $data = $request->all();

    if (empty($data['email']) || empty($data['password'])) {
        return Response::error('邮箱和密码不能为空', 400);
    }

    // 查找用户
    $user = PandaDB::table('users')
        ->where('email', $data['email'])
        ->find();

    if (!$user) {
        return Response::error('邮箱或密码错误', 401);
    }

    // 验证密码
    if (!password_verify($data['password'], $user['password'])) {
        return Response::error('邮箱或密码错误', 401);
    }

    // 生成Token并存入缓存（7天有效期）
    $token = bin2hex(random_bytes(32));
    Cache::set("token:{$token}", [
        'user_id'    => $user['id'],
        'email'      => $user['email'],
        'created_at' => time(),
    ], 86400 * 7);

    return Response::success([
        'token' => $token,
        'user'  => [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
        ],
    ]);
});'''
            },
            {
                'title': '片段 17：应用启动',
                'code': '''// 创建应用实例
$app = new App();

// 配置应用
$app->config([
    'env'       => 'development',
    'debug'     => true,
    'timezone'  => 'Asia/Shanghai',
    'middleware' => ['Cors', 'Log'],
]);

// 运行
$app->run();'''
            },
        ]
    },
    'Controllers.php': {
        'description': '控制器类示例，展示如何使用控制器组织代码',
        'sections': [
            {
                'title': '片段 18：UserController 完整控制器',
                'code': '''<?php
namespace Demo\\Controller;

use PandaAPI\\Core\\Request;
use PandaAPI\\Core\\Response;
use PandaAPI\\Database\\PandaDB;
use PandaAPI\\Cache\\Cache;
use PandaAPI\\Validation\\Validator;

class UserController
{
    /**
     * 用户列表  GET /users
     */
    public function index(Request $request)
    {
        $page    = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 15);
        $status  = $request->query('status');

        $builder = PandaDB::table('users');
        if ($status !== null) {
            $builder->where('status', (int)$status);
        }

        $result = $builder->paginate($perPage, $page);

        return Response::paginate(
            $result['data'],
            $result['total'],
            $result['current_page'],
            $result['per_page']
        );
    }

    /**
     * 用户详情  GET /users/{id}
     */
    public function show(Request $request, $id)
    {
        $cacheKey = "user:{$id}";

        $user = Cache::remember($cacheKey, function() use ($id) {
            return PandaDB::table('users')
                ->where('id', (int)$id)
                ->find();
        }, 300);

        if (!$user) {
            return Response::error('用户不存在', 404);
        }

        unset($user['password']);
        return Response::success($user);
    }

    /**
     * 创建用户  POST /users
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $validator = new Validator($data, [
            'name'     => 'required|string|min:2|max:50',
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return Response::json([
                'error'    => '验证失败',
                'messages' => $validator->errors()
            ], 422);
        }

        $exists = PandaDB::table('users')
            ->where('email', $data['email'])
            ->count();

        if ($exists > 0) {
            return Response::error('邮箱已被注册', 422);
        }

        $data['password']   = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['status']     = 1;
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = PandaDB::table('users')->insert($data);

        $user = PandaDB::table('users')->where('id', $id)->find();
        unset($user['password']);

        return Response::json($user, 201);
    }

    /**
     * 更新用户  PUT /users/{id}
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $user = PandaDB::table('users')->where('id', (int)$id)->find();

        if (!$user) {
            return Response::error('用户不存在', 404);
        }

        if (!empty($data)) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            PandaDB::table('users')->where('id', (int)$id)->update($data);
            Cache::forget("user:{$id}");
        }

        $user = PandaDB::table('users')->where('id', (int)$id)->find();
        unset($user['password']);
        return Response::success($user);
    }

    /**
     * 删除用户  DELETE /users/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = PandaDB::table('users')->where('id', (int)$id)->find();

        if (!$user) {
            return Response::error('用户不存在', 404);
        }

        PandaDB::table('users')->where('id', (int)$id)->delete();
        Cache::forget("user:{$id}");

        return Response::success(['message' => '删除成功']);
    }
}'''
            },
        ]
    },
    'config.php': {
        'description': '配置文件示例，包含数据库、缓存、中间件等配置',
        'sections': [
            {
                'title': '片段 19：完整配置文件',
                'code': '''<?php
return [
    // 环境配置
    'env'      => 'development',
    'debug'    => true,
    'timezone' => 'Asia/Shanghai',

    // 数据库配置
    'database' => [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'pandaapi_demo',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
        'prefix'   => 'panda_',
    ],

    // 缓存配置
    'cache' => [
        'driver' => 'file',   // file / redis / memcache / openresty
        'file' => [
            'path' => __DIR__ . '/../runtime/cache',
        ],
        'redis' => [
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'password' => null,
            'database' => 0,
            'prefix'   => 'pandaapi:',
        ],
        'default_ttl' => 3600,
    ],

    // 全局中间件
    'middleware' => [
        'Cors',   // 跨域
        'Log',    // 日志
    ],

    // 限流配置
    'throttle' => [
        'max_attempts'  => 60,
        'decay_minutes' => 1,
    ],

    // CORS配置
    'cors' => [
        'origin'  => '*',
        'methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
        'headers' => 'Content-Type, Authorization, X-Requested-With',
    ],

    // 慢查询阈值(毫秒)
    'slow_query_threshold' => 100,
];'''
            },
        ]
    },
    'init_database.php': {
        'description': '数据库初始化脚本，创建表结构和示例数据',
        'sections': [
            {
                'title': '片段 20：数据库初始化脚本',
                'code': '''<?php
/**
 * 数据库初始化脚本
 * 运行: php init_database.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PandaAPI\\Database\\PandaDB;
use PandaAPI\\Cache\\Cache;

echo "PandaAPI Demo 数据库初始化\\n";

// 配置数据库
PandaDB::config([
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'pandaapi_demo',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
    'prefix'   => 'panda_',
]);

try {
    // 创建用户表
    PandaDB::execute("
        CREATE TABLE IF NOT EXISTS panda_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            status TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表'
    ");
    echo "OK: 用户表创建成功\\n";

    // 创建文章表
    PandaDB::execute("
        CREATE TABLE IF NOT EXISTS panda_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            views INT DEFAULT 0,
            status TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            FOREIGN KEY (user_id) REFERENCES panda_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章表'
    ");
    echo "OK: 文章表创建成功\\n";

    // 插入示例用户
    $users = [
        ['name' => '张三', 'email' => 'zhangsan@example.com',
         'password' => password_hash('123456', PASSWORD_DEFAULT)],
        ['name' => '李四', 'email' => 'lisi@example.com',
         'password' => password_hash('123456', PASSWORD_DEFAULT)],
        ['name' => '王五', 'email' => 'wangwu@example.com',
         'password' => password_hash('123456', PASSWORD_DEFAULT)],
    ];

    $userIds = [];
    foreach ($users as $user) {
        $user['created_at'] = date('Y-m-d H:i:s');
        $user['status']     = 1;
        $userIds[] = PandaDB::table('users')->insert($user);
    }
    echo "OK: 插入 " . count($userIds) . " 个用户\\n";

    // 插入示例文章
    $articles = [
        ['title' => 'PHP 7.4 新特性详解',
         'content' => 'PHP 7.4 引入了许多新特性...', 'views' => 1250],
        ['title' => 'MySQL 性能优化技巧',
         'content' => '本文介绍如何优化 MySQL...', 'views' => 890],
        ['title' => 'Redis 缓存实战',
         'content' => 'Redis 是一个高性能的键值存储...', 'views' => 1560],
    ];

    foreach ($articles as $index => $article) {
        $article['user_id']    = $userIds[array_rand($userIds)];
        $article['status']     = 1;
        $article['created_at'] = date('Y-m-d H:i:s', strtotime("-{$index} days"));
        PandaDB::table('posts')->insert($article);
    }
    echo "OK: 插入 " . count($articles) . " 篇文章\\n";

} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\\n";
    exit(1);
}

echo "\\n初始化完成！\\n";
echo "测试账号: zhangsan@example.com / 123456\\n";'''
            },
        ]
    },
}

# ============================================================
# 构建文档
# ============================================================
doc = SimpleDocTemplate(
    '/workspace/pandaapi/PandaAPI_Demo_代码文档.pdf',
    pagesize=A4,
    leftMargin=LM, rightMargin=RM,
    topMargin=TM, bottomMargin=BM,
)

story = []

# ==================== 封面 ====================
story.append(sp(80))
story.append(p('<b>熊猫API框架</b>', 'title'))
story.append(p('<b>Demo 项目代码文档</b>', 'title'))
story.append(sp(20))
story.append(p('PandaAPI Framework - Demo Project Documentation', 'subtitle'))
story.append(sp(30))
story.append(p('PHP 语法高亮 | VS Code 暗色主题风格', 'subtitle'))
story.append(sp(50))
story.append(p('版本: v1.0.0', 'caption'))
story.append(p('日期: 2024年', 'caption'))
story.append(PageBreak())

# ==================== 目录 ====================
story.append(p('目  录', 'h1'))
story.append(sp(10))

toc_items = [
    '1. 项目概述',
    '2. index.php - 主入口文件（片段 1~17）',
    '3. Controllers.php - 控制器示例（片段 18）',
    '4. config.php - 配置文件（片段 19）',
    '5. init_database.php - 数据库初始化（片段 20）',
]
for item in toc_items:
    story.append(p(item, 'body'))
    story.append(sp(4))

story.append(PageBreak())

# ==================== 第1章 项目概述 ====================
story.append(p('1. 项目概述', 'h1'))
story.append(sp(10))

story.append(p('1.1 项目简介', 'h2'))
story.append(p(
    '本 Demo 项目展示了熊猫API框架 (PandaAPI) 的所有功能使用方法，'
    '包括 PandaDB 数据库操作、缓存系统、路由定义、中间件使用、数据验证等。'
))

story.append(p('1.2 文件结构', 'h2'))
story.extend(code('''demo/
+-- index.php          # 主入口文件，包含所有API路由
+-- Controllers.php    # 控制器类示例
+-- config.php         # 配置文件
+-- init_database.php  # 数据库初始化脚本
+-- api_test.php       # API测试脚本
+-- README.md          # 项目说明'''))

story.append(p('1.3 主要功能', 'h2'))
story.append(li('用户管理：增删改查、分页、缓存'))
story.append(li('文章管理：JOIN查询、浏览量统计'))
story.append(li('认证系统：登录、注册、Token'))
story.append(li('缓存操作：文件缓存、Redis缓存'))
story.append(li('数据库操作：ThinkPHP风格、PandaDB风格'))
story.append(li('事务处理：回调式、手动式'))

story.append(PageBreak())

# ==================== 第2~5章 ====================
chapter_num = 2
for filename, file_data in CODE_SNIPPETS.items():
    story.append(p(f'{chapter_num}. {filename}', 'h1'))
    story.append(sp(6))
    story.append(p(file_data['description']))
    story.append(sp(10))

    for section in file_data['sections']:
        story.append(p(section['title'], 'h2'))
        for elem in code(section['code']):
            story.append(elem)
        story.append(sp(8))

    story.append(PageBreak())
    chapter_num += 1

# ==================== 结尾 ====================
story.append(sp(30))
story.append(p('— 文档结束 —', 'caption'))

# ============================================================
# 生成 PDF
# ============================================================
doc.build(story)
print('OK: /workspace/pandaapi/PandaAPI_Demo_代码文档.pdf')
