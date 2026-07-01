#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
熊猫API框架 (PandaAPI) 中文用户手册 PDF 生成器
使用 wqy-zenhei 中文字体，确保中文正常显示
"""

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.lib.colors import HexColor
from reportlab.lib.styles import ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak
)
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.enums import TA_LEFT, TA_CENTER

# ============================================================
# 注册中文字体（使用 wqy-zenhei）
# ============================================================
pdfmetrics.registerFont(
    TTFont('WQY', '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc', subfontIndex=0)
)
pdfmetrics.registerFont(
    TTFont('Mono', '/usr/share/fonts/truetype/liberation2/LiberationMono-Regular.ttf')
)
CJK = 'WQY'
MONO = 'Mono'

# ============================================================
# 颜色定义
# ============================================================
PRIMARY   = HexColor('#1a365d')
ACCENT    = HexColor('#2b6cb0')
LIGHT_BG  = HexColor('#f7fafc')
GRAY      = HexColor('#718096')
DARK      = HexColor('#2d3748')
WHITE     = HexColor('#ffffff')
CODE_BG   = HexColor('#edf2f7')

# ============================================================
# 页面参数
# ============================================================
PAGE_W, PAGE_H = A4
LM = RM = 18 * mm
TM = BM = 18 * mm
CW = PAGE_W - LM - RM  # 内容宽度

# ============================================================
# 样式
# ============================================================
S = {
    'title':    ParagraphStyle('T', fontName=CJK, fontSize=28, leading=38,
                              textColor=PRIMARY, alignment=TA_CENTER, spaceAfter=16),
    'subtitle': ParagraphStyle('ST', fontName=CJK, fontSize=13, leading=18,
                              textColor=GRAY, alignment=TA_CENTER, spaceAfter=10),
    'h1':       ParagraphStyle('H1', fontName=CJK, fontSize=16, leading=24,
                              textColor=PRIMARY, spaceBefore=20, spaceAfter=8),
    'h2':       ParagraphStyle('H2', fontName=CJK, fontSize=13, leading=18,
                              textColor=ACCENT, spaceBefore=14, spaceAfter=6),
    'h3':       ParagraphStyle('H3', fontName=CJK, fontSize=11, leading=16,
                              textColor=DARK, spaceBefore=10, spaceAfter=4),
    'body':     ParagraphStyle('B', fontName=CJK, fontSize=10, leading=16,
                              textColor=DARK, spaceAfter=5),
    'li':       ParagraphStyle('LI', fontName=CJK, fontSize=10, leading=16,
                              textColor=DARK, leftIndent=18, spaceAfter=3),
    'code':     ParagraphStyle('C', fontName=MONO, fontSize=8, leading=11,
                              textColor=DARK, backColor=CODE_BG,
                              leftIndent=8, rightIndent=8,
                              spaceBefore=4, spaceAfter=4),
    'caption':  ParagraphStyle('CAP', fontName=CJK, fontSize=9, leading=12,
                              textColor=GRAY, alignment=TA_CENTER,
                              spaceBefore=4, spaceAfter=10),
}

# ============================================================
# 工具函数
# ============================================================
def p(text, style='body'):
    return Paragraph(text, S[style])

def sp(h=10):
    return Spacer(1, h)

def code(text):
    """代码块：转义 HTML 特殊字符"""
    import re
    safe = text.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;')
    # 去掉过长的装饰性分隔线，避免PDF排版异常
    safe = re.sub(r'//\s*={5,}\s*.*?={5,}\s*$', '', safe, flags=re.MULTILINE)
    safe = re.sub(r'//\s*-{5,}\s*$', '', safe, flags=re.MULTILINE)
    return Paragraph(safe, S['code'])

def li(text):
    return Paragraph('\u2022 ' + text, S['li'])

def tbl(data, col_widths=None):
    """创建统一风格的表格"""
    if col_widths is None:
        col_widths = [CW * 0.35, CW * 0.65]
    t = Table(data, colWidths=col_widths)
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), ACCENT),
        ('TEXTCOLOR',  (0, 0), (-1, 0), WHITE),
        ('FONTNAME',   (0, 0), (-1, -1), CJK),
        ('FONTSIZE',   (0, 0), (-1, 0), 10),
        ('FONTSIZE',   (0, 1), (-1, -1), 9),
        ('ALIGN',      (0, 0), (-1, -1), 'LEFT'),
        ('VALIGN',     (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING',    (0, 0), (-1, -1), 5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 5),
        ('LEFTPADDING',   (0, 0), (-1, -1), 6),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [WHITE, LIGHT_BG]),
        ('GRID', (0, 0), (-1, -1), 0.5, HexColor('#e2e8f0')),
    ]))
    return t

# ============================================================
# 构建文档
# ============================================================
doc = SimpleDocTemplate(
    '/workspace/pandaapi/熊猫API框架中文手册.pdf',
    pagesize=A4,
    leftMargin=LM, rightMargin=RM,
    topMargin=TM, bottomMargin=BM,
)

story = []

# ==================== 封面 ====================
story.append(sp(100))
story.append(p('<b>熊猫API框架</b>', 'title'))
story.append(p('PandaAPI Framework', 'subtitle'))
story.append(sp(20))
story.append(p('PHP 7.4+ | PandaDB | inhere/sroute', 'subtitle'))
story.append(sp(30))
story.append(p('中文用户手册 v1.0', 'subtitle'))
story.append(PageBreak())

# ==================== 目录 ====================
story.append(p('目  录', 'h1'))
story.append(sp(8))
for i, t in enumerate([
    '1.  简介',
    '2.  核心特性',
    '3.  快速开始',
    '4.  PandaDB 数据库操作',
    '5.  缓存系统',
    '6.  路由系统',
    '7.  请求与响应',
    '8.  中间件',
    '9.  验证器',
    '10. 性能优化',
    '11. 配置参考',
    '附录 A  目录结构',
    '附录 B  许可证',
], 1):
    story.append(p(t, 'body'))
story.append(PageBreak())

# ==================== 第1章 简介 ====================
story.append(p('1. 简介', 'h1'))
story.append(sp(6))
story.append(p(
    '熊猫API框架 (PandaAPI) 是一个基于 PHP 7.4+ 的高性能 API 框架，'
    '融合了 ThinkPHP 的简洁链式调用语法和 PandaDB 的快捷操作方法，'
    '同时集成 inhere/sroute 的极速路由匹配。'
    '框架默认关闭 PHP Session，实现无状态设计，最大化运行性能。'
))
story.append(sp(6))
story.append(p('1.1 主要特点', 'h2'))
story.append(li('PandaDB 数据库类 - 融合 ThinkPHP 链式调用和 PandaDB 快捷方法'))
story.append(li('ThinkPHP 风格 - 熟悉的 Db::table()->where()->select() 语法'))
story.append(li('PandaDB 兼容 - 支持 select/get/insert/update/delete 等快捷方法'))
story.append(li('多缓存后端 - File / Memcache / Redis / OpenResty'))
story.append(li('极速路由匹配 - O(1) 时间复杂度，不受路由数量影响'))
story.append(li('无状态设计 - 关闭 PHP Session，最大化性能'))
story.append(li('PDO 预处理 - 防止 SQL 注入'))
story.append(li('查询性能统计 - 识别慢查询'))
story.append(PageBreak())

# ==================== 第2章 核心特性 ====================
story.append(p('2. 核心特性', 'h1'))
story.append(sp(6))

story.append(p('2.1 PandaDB 数据库类', 'h2'))
story.append(p('框架提供 PandaDB 类，融合两种流行的数据库操作方式：'))
story.append(sp(4))
story.append(p('<b>ThinkPHP 风格链式调用：</b>'))
story.append(code(
    "PandaDB::table('user')->where('status', 1)->select();\n"
    "PandaDB::table('user')->where('id', 1)->find();\n"
    "PandaDB::table('user')->insert(['name' => 'test']);\n"
    "PandaDB::table('user')->where('id', 1)->update(['name' => 'new']);\n"
    "PandaDB::table('user')->where('id', 1)->delete();"
))
story.append(sp(4))
story.append(p('<b>PandaDB 风格快捷方法：</b>'))
story.append(code(
    "PandaDB::select('user', '*', ['status' => 1]);\n"
    "PandaDB::get('user', '*', ['id' => 1]);\n"
    "PandaDB::insert('user', ['name' => 'test']);\n"
    "PandaDB::update('user', ['name' => 'new'], ['id' => 1]);\n"
    "PandaDB::delete('user', ['id' => 1]);"
))
story.append(sp(8))

story.append(p('2.2 性能优化措施', 'h2'))
story.append(tbl([
    ['特性', '说明'],
    ['PDO 预处理', '原生 PDO 预处理，防止 SQL 注入'],
    ['查询缓存', '支持多种缓存后端自动缓存'],
    ['查询构建器', '链式调用，延迟执行'],
    ['性能统计', '记录查询次数、执行时间，识别慢查询'],
]))
story.append(sp(10))

story.append(p('2.3 路由匹配优势', 'h2'))
story.append(p('与 inhere/sroute 融合，具备以下优势：'))
story.append(li('极速路由匹配 - O(1) 时间复杂度，不受路由数量影响'))
story.append(li('支持路由组 - 便于 API 版本管理'))
story.append(li('中间件支持 - 灵活的请求处理管道'))
story.append(li('命名路由 - 便于生成 URL'))
story.append(li('参数匹配 - 支持动态路径参数'))
story.append(PageBreak())

# ==================== 第3章 快速开始 ====================
story.append(p('3. 快速开始', 'h1'))
story.append(sp(6))

story.append(p('3.1 安装', 'h2'))
story.append(code('composer require pandaapi/framework'))
story.append(sp(6))

story.append(p('3.2 配置数据库', 'h2'))
story.append(code(
    "use PandaAPI\\Database\\PandaDB;\n\n"
    "PandaDB::config([\n"
    "    'driver'   => 'mysql',\n"
    "    'host'     => '127.0.0.1',\n"
    "    'port'     => 3306,\n"
    "    'database' => 'pandaapi',\n"
    "    'username' => 'root',\n"
    "    'password' => '',\n"
    "]);"
))
story.append(sp(6))

story.append(p('3.3 配置缓存', 'h2'))
story.append(code(
    "use PandaAPI\\Cache\\Cache;\n\n"
    "Cache::config([\n"
    "    'driver' => 'redis',\n"
    "    'redis' => [\n"
    "        'host' => '127.0.0.1',\n"
    "        'port' => 6379,\n"
    "    ],\n"
    "]);"
))
story.append(sp(6))

story.append(p('3.4 定义路由', 'h2'))
story.append(code(
    "use PandaAPI\\Route\\Route;\n"
    "use PandaAPI\\Core\\Response;\n\n"
    "Route::get('/users', function() {\n"
    "    return Response::json(PandaDB::table('user')->select());\n"
    "});\n\n"
    "Route::get('/user/{id}', function($id) {\n"
    "    return Response::json(\n"
    "        PandaDB::table('user')->where('id', $id)->find()\n"
    "    );\n"
    "});"
))
story.append(sp(6))

story.append(p('3.5 运行应用', 'h2'))
story.append(code(
    "use PandaAPI\\Core\\App;\n\n"
    "$app = new App();\n"
    "$app->run();"
))
story.append(PageBreak())

# ==================== 第4章 数据库 ====================
story.append(p('4. PandaDB 数据库操作', 'h1'))
story.append(sp(6))

story.append(p('4.1 ThinkPHP 风格链式方法', 'h2'))
story.append(tbl([
    ['方法', '说明'],
    ['table($table)', '设置表名'],
    ['name($name)', '设置表名（自动添加前缀）'],
    ['field($fields)', '指定查询字段（字符串或数组）'],
    ['where($field, $value)', 'WHERE 等值条件'],
    ['whereOr($field, $value)', 'OR WHERE 条件'],
    ['whereIn($field, $values)', 'WHERE IN 条件'],
    ['whereNotIn($field, $values)', 'WHERE NOT IN 条件'],
    ['whereBetween($field, $min, $max)', 'WHERE BETWEEN 条件'],
    ['whereLike($field, $value)', 'WHERE LIKE 模糊匹配'],
    ['whereNull($field)', 'WHERE IS NULL'],
    ['whereNotNull($field)', 'WHERE IS NOT NULL'],
    ['join($table, $on, $type)', 'JOIN 关联查询'],
    ['leftJoin($table, $on)', 'LEFT JOIN'],
    ['rightJoin($table, $on)', 'RIGHT JOIN'],
    ['order($field, $dir)', '排序（ASC / DESC）'],
    ['group($field)', '分组'],
    ['having($cond)', 'HAVING 条件'],
    ['limit($n, $offset)', '限制条数'],
    ['page($page, $perPage)', '分页查询'],
]))
story.append(sp(8))

story.append(p('4.2 PandaDB 风格快捷方法', 'h2'))
story.append(tbl([
    ['方法', '说明'],
    ['select($table, $columns, $where)', '查询多条记录'],
    ['get($table, $columns, $where)', '查询单条记录'],
    ['insert($table, $data)', '插入数据，返回自增 ID'],
    ['insertAll($table, $dataSet)', '批量插入'],
    ['update($table, $data, $where)', '更新数据，返回影响行数'],
    ['delete($table, $where)', '删除数据，返回影响行数'],
    ['count($table, $where)', '统计记录数量'],
    ['sum($table, $column, $where)', '求和'],
    ['avg($table, $column, $where)', '平均值'],
    ['max($table, $column, $where)', '最大值'],
    ['min($table, $column, $where)', '最小值'],
]))
story.append(sp(8))

story.append(p('4.3 查询示例', 'h2'))
story.append(code(
    "// ThinkPHP 风格\n"
    "PandaDB::table('users')->select();\n\n"
    "// 条件查询\n"
    "PandaDB::table('users')->where('status', 1)->select();\n\n"
    "// 链式调用\n"
    "PandaDB::table('users')\n"
    "    ->field('id, name, email')\n"
    "    ->where('status', 1)\n"
    "    ->order('created_at', 'desc')\n"
    "    ->limit(10)\n"
    "    ->select();\n\n"
    "// PandaDB 风格\n"
    "PandaDB::select('users', '*', ['status' => 1]);\n\n"
    "// 分页查询\n"
    "$result = PandaDB::table('users')->paginate(15, $page);\n"
    "// $result['data']       - 当前页数据\n"
    "// $result['total']      - 总记录数\n"
    "// $result['current_page'] - 当前页码\n"
    "// $result['last_page']  - 最后一页\n"
    "// $result['per_page']   - 每页条数"
))
story.append(sp(8))

story.append(p('4.4 写入示例', 'h2'))
story.append(code(
    "// ThinkPHP 风格 - 插入单条\n"
    "$id = PandaDB::table('users')->insert([\n"
    "    'name' => '张三',\n"
    "    'email' => 'zhangsan@test.com',\n"
    "    'password' => password_hash('123456', PASSWORD_DEFAULT),\n"
    "]);\n\n"
    "// PandaDB 风格 - 插入\n"
    "$id = PandaDB::insert('users', [\n"
    "    'name' => '张三',\n"
    "    'email' => 'zhangsan@test.com',\n"
    "]);\n\n"
    "// 批量插入\n"
    "PandaDB::table('users')->insertAll([\n"
    "    ['name' => 'A', 'email' => 'a@test.com'],\n"
    "    ['name' => 'B', 'email' => 'b@test.com'],\n"
    "]);\n\n"
    "// ThinkPHP 风格 - 更新\n"
    "PandaDB::table('users')->where('id', 1)->update(['name' => '新名字']);\n\n"
    "// PandaDB 风格 - 更新\n"
    "PandaDB::update('users', ['name' => '新名字'], ['id' => 1]);\n\n"
    "// 删除\n"
    "PandaDB::table('users')->where('id', 1)->delete();\n"
    "PandaDB::delete('users', ['id' => 1]);"
))
story.append(sp(8))

story.append(p('4.5 聚合查询', 'h2'))
story.append(code(
    "// ThinkPHP 风格\n"
    "PandaDB::table('users')->count();\n"
    "PandaDB::table('orders')->sum('amount');\n"
    "PandaDB::table('users')->avg('score');\n"
    "PandaDB::table('products')->max('price');\n"
    "PandaDB::table('products')->min('price');\n\n"
    "// PandaDB 风格\n"
    "PandaDB::count('users');\n"
    "PandaDB::sum('orders', 'amount');\n"
    "PandaDB::avg('users', 'score');\n"
    "PandaDB::max('products', 'price');\n"
    "PandaDB::min('products', 'price');"
))
story.append(sp(8))

story.append(p('4.6 事务操作', 'h2'))
story.append(code(
    "// 方式一：回调事务\n"
    "PandaDB::transaction(function() {\n"
    "    PandaDB::table('users')->insert([...]);\n"
    "    PandaDB::table('logs')->insert([...]);\n"
    "});\n\n"
    "// 方式二：手动事务\n"
    "PandaDB::beginTransaction();\n"
    "try {\n"
    "    PandaDB::table('users')->insert([...]);\n"
    "    PandaDB::commit();\n"
    "} catch (Exception $e) {\n"
    "    PandaDB::rollback();\n"
    "}"
))
story.append(sp(8))

story.append(p('4.7 原生 SQL', 'h2'))
story.append(code(
    "// 查询\n"
    "$rows = PandaDB::query('SELECT * FROM users WHERE status = ?', [1]);\n\n"
    "// 执行\n"
    "$affected = PandaDB::execute('DELETE FROM logs WHERE created_at < ?', ['2024-01-01']);\n\n"
    "// 获取单条\n"
    "$row = PandaDB::getOne('SELECT * FROM users WHERE id = ?', [1]);\n\n"
    "// 获取单个值\n"
    "$count = PandaDB::getValue('SELECT COUNT(*) FROM users');\n\n"
    "// 获取某一列\n"
    "$names = PandaDB::getColumn('SELECT name FROM users');"
))
story.append(PageBreak())

# ==================== 第5章 缓存 ====================
story.append(p('5. 缓存系统', 'h1'))
story.append(sp(6))

story.append(p('5.1 支持的缓存驱动', 'h2'))
story.append(tbl([
    ['驱动', '配置项', '说明'],
    ['file', 'path', '文件缓存，无需额外服务'],
    ['redis', 'host, port, password', '高性能内存缓存'],
    ['memcache', 'host, port', '分布式内存缓存'],
    ['openresty', 'host, port', 'OpenResty 共享内存缓存'],
], col_widths=[CW*0.18, CW*0.42, CW*0.40]))
story.append(sp(8))

story.append(p('5.2 配置示例', 'h2'))
story.append(code(
    "// Redis 缓存\n"
    "Cache::config([\n"
    "    'driver' => 'redis',\n"
    "    'redis' => [\n"
    "        'host'     => '127.0.0.1',\n"
    "        'port'     => 6379,\n"
    "        'password' => null,\n"
    "        'database' => 0,\n"
    "        'prefix'   => 'pandaapi:',\n"
    "    ],\n"
    "]);\n\n"
    "// 文件缓存\n"
    "Cache::config([\n"
    "    'driver' => 'file',\n"
    "    'file' => ['path' => '/tmp/cache'],\n"
    "]);"
))
story.append(sp(8))

story.append(p('5.3 基本操作', 'h2'))
story.append(code(
    "// 设置 / 获取 / 删除\n"
    "Cache::set('key', 'value', 300);  // 缓存 300 秒\n"
    "Cache::get('key', 'default');      // 获取，不存在返回默认值\n"
    "Cache::has('key');                 // 检查是否存在\n"
    "Cache::delete('key');              // 删除\n"
    "Cache::forget('key');              // 删除（别名）\n"
    "Cache::clear();                    // 清空所有缓存\n\n"
    "// 记住模式（缓存穿透自动回源）\n"
    "$users = Cache::remember('user_list', function() {\n"
    "    return PandaDB::table('users')->select();\n"
    "}, 300);  // 缓存 5 分钟\n\n"
    "// 获取并删除\n"
    "$value = Cache::pull('key');\n\n"
    "// 原子递增 / 递减\n"
    "Cache::increment('counter', 1);\n"
    "Cache::decrement('counter', 1);\n\n"
    "// 批量操作\n"
    "Cache::setMultiple(['k1'=>'v1', 'k2'=>'v2'], 300);\n"
    "Cache::getMultiple(['k1', 'k2']);\n"
    "Cache::deleteMultiple(['k1', 'k2']);"
))
story.append(sp(8))

story.append(p('5.4 标签缓存', 'h2'))
story.append(code(
    "// 设置带标签的缓存\n"
    "Cache::tag('users')->set('list', $data);\n"
    "Cache::tag('users')->set('count', $count);\n\n"
    "// 清空某标签下所有缓存\n"
    "Cache::tag('users')->flush();"
))
story.append(sp(8))

story.append(p('5.5 缓存锁', 'h2'))
story.append(code(
    "// 获取锁（10秒超时）\n"
    "if (Cache::lock('resource:1', 10)) {\n"
    "    // 执行独占操作\n"
    "    Cache::unlock('resource:1');\n"
    "}"
))
story.append(PageBreak())

# ==================== 第6章 路由 ====================
story.append(p('6. 路由系统', 'h1'))
story.append(sp(6))

story.append(p('6.1 HTTP 方法路由', 'h2'))
story.append(code(
    "Route::get($path, $handler);       // GET\n"
    "Route::post($path, $handler);      // POST\n"
    "Route::put($path, $handler);       // PUT\n"
    "Route::patch($path, $handler);     // PATCH\n"
    "Route::delete($path, $handler);    // DELETE\n"
    "Route::options($path, $handler);   // OPTIONS\n"
    "Route::any($path, $handler);       // 任意方法\n"
    "Route::match(['GET','POST'], $path, $handler);  // 指定多个方法"
))
story.append(sp(8))

story.append(p('6.2 路由组', 'h2'))
story.append(code(
    "// 带前缀的路由组\n"
    "Route::group(['prefix' => '/api/v1'], function() {\n"
    "    Route::get('/users', 'UserController@index');\n"
    "    Route::get('/users/{id}', 'UserController@show');\n"
    "});\n\n"
    "// 带中间件的路由组\n"
    "Route::group(['middleware' => ['Auth', 'Cors']], function() {\n"
    "    Route::get('/profile', 'ProfileController@show');\n"
    "});\n\n"
    "// 嵌套路由组\n"
    "Route::group(['prefix' => '/api'], function() {\n"
    "    Route::group(['prefix' => '/v1'], function() {\n"
    "        Route::get('/users', 'UserController@index');\n"
    "    });\n"
    "});"
))
story.append(sp(8))

story.append(p('6.3 参数约束', 'h2'))
story.append(code(
    "// 数字约束\n"
    "Route::get('/user/{id}', 'Controller@show')\n"
    "    ->whereNumber('id');\n\n"
    "// 字母约束\n"
    "Route::get('/post/{slug}', 'Controller@show')\n"
    "    ->whereAlpha('slug');\n\n"
    "// 字母数字约束\n"
    "Route::get('/tag/{name}', 'Controller@show')\n"
    "    ->whereAlphaNum('name');\n\n"
    "// UUID 约束\n"
    "Route::get('/order/{uuid}', 'Controller@show')\n"
    "    ->whereUuid('uuid');\n\n"
    "// 自定义正则约束\n"
    "Route::get('/file/{path}', 'Controller@show')\n"
    "    ->where('path', '[a-z0-9/]+');"
))
story.append(sp(8))

story.append(p('6.4 资源路由', 'h2'))
story.append(code(
    "Route::resource('users', 'UserController');\n\n"
    "// 自动生成以下路由:\n"
    "// GET    /users           index   (列表)\n"
    "// GET    /users/{id}      show    (详情)\n"
    "// POST   /users           store   (创建)\n"
    "// PUT    /users/{id}      update  (更新)\n"
    "// DELETE /users/{id}      destroy (删除)"
))
story.append(sp(8))

story.append(p('6.5 其他路由功能', 'h2'))
story.append(code(
    "// 命名路由\n"
    "Route::get('/profile', 'Controller@show')->name('profile');\n"
    "$url = Route::route('profile');  // 生成 URL\n\n"
    "// 重定向\n"
    "Route::redirect('/old', '/new', 301);\n\n"
    "// 404 处理\n"
    "Route::set404(function($path) {\n"
    "    return Response::error('接口不存在', 404);\n"
    "});"
))
story.append(PageBreak())

# ==================== 第7章 请求与响应 ====================
story.append(p('7. 请求与响应', 'h1'))
story.append(sp(6))

story.append(p('7.1 Request 请求方法', 'h2'))
story.append(tbl([
    ['方法', '说明'],
    ['method()', '获取 HTTP 方法（GET / POST 等）'],
    ['uri()', '获取请求路径'],
    ['url()', '获取完整 URL'],
    ['all()', '获取所有参数（GET + POST）'],
    ['query($key)', '获取 GET 参数'],
    ['post($key)', '获取 POST 参数'],
    ['header($key)', '获取请求头'],
    ['json()', '获取 JSON 请求体（自动解析）'],
    ['file($key)', '获取上传文件'],
    ['cookie($key)', '获取 Cookie'],
    ['ip()', '获取客户端 IP'],
    ['user()', '获取当前认证用户'],
    ['isAjax()', '是否 AJAX 请求'],
    ['isJson()', '是否 JSON 请求'],
]))
story.append(sp(8))

story.append(p('7.2 Response 响应方法', 'h2'))
story.append(code(
    "// JSON 响应\n"
    "Response::json(['data' => $value]);\n"
    "Response::json($data, 201);  // 指定状态码\n\n"
    "// 成功 / 错误快捷方法\n"
    "Response::success(['user' => $user]);\n"
    "Response::error('操作失败', 500);\n\n"
    "// 分页响应\n"
    "Response::paginate($data, $total, $page, $perPage);\n\n"
    "// 其他类型\n"
    "Response::text('纯文本');\n"
    "Response::html('<h1>HTML</h1>');\n"
    "Response::redirect('/new-url', 301);\n"
    "Response::noContent();  // 204\n\n"
    "// 链式调用\n"
    "Response::json($data)\n"
    "    ->status(201)\n"
    "    ->header(['X-Custom' => 'value'])\n"
    "    ->send();"
))
story.append(PageBreak())

# ==================== 第8章 中间件 ====================
story.append(p('8. 中间件', 'h1'))
story.append(sp(6))

story.append(p('8.1 内置中间件', 'h2'))
story.append(tbl([
    ['中间件', '类名', '说明'],
    ['CORS', 'CorsMiddleware', '跨域资源共享处理'],
    ['Auth', 'AuthMiddleware', 'Token 用户认证'],
    ['Throttle', 'ThrottleMiddleware', '请求频率限流'],
    ['Log', 'LogMiddleware', '请求日志记录'],
    ['Validate', 'ValidateMiddleware', '请求数据验证'],
], col_widths=[CW*0.15, CW*0.35, CW*0.50]))
story.append(sp(8))

story.append(p('8.2 使用方式', 'h2'))
story.append(code(
    "// 全局中间件（在入口文件配置）\n"
    "$app->middleware(['Cors', 'Log']);\n\n"
    "// 路由组中间件\n"
    "Route::group(['middleware' => ['Auth']], function() {\n"
    "    Route::get('/profile', 'ProfileController@show');\n"
    "});\n\n"
    "// 单个路由中间件\n"
    "Route::get('/admin', 'AdminController@index')\n"
    "    ->middleware(['Auth', 'Throttle']);"
))
story.append(sp(8))

story.append(p('8.3 自定义中间件', 'h2'))
story.append(code(
    "class MyMiddleware\n"
    "{\n"
    "    public function handle($request, callable $next)\n"
    "    {\n"
    "        // 前置逻辑\n"
    "        $response = $next($request);\n"
    "        // 后置逻辑\n"
    "        return $response;\n"
    "    }\n"
    "}\n\n"
    "// 注册\n"
    "Middleware::register('my', MyMiddleware::class);"
))
story.append(PageBreak())

# ==================== 第9章 验证器 ====================
story.append(p('9. 验证器', 'h1'))
story.append(sp(6))

story.append(p('9.1 内置验证规则', 'h2'))
story.append(tbl([
    ['规则', '参数', '说明'],
    ['required', '-', '必填'],
    ['email', '-', '合法邮箱'],
    ['min', 'n', '最小长度或最小值'],
    ['max', 'n', '最大长度或最大值'],
    ['numeric', '-', '数字'],
    ['integer', '-', '整数'],
    ['string', '-', '字符串'],
    ['array', '-', '数组'],
    ['in', 'v1,v2,...', '值在列表中'],
    ['not_in', 'v1,v2,...', '值不在列表中'],
    ['between', 'min,max', '在范围内'],
    ['regex', 'pattern', '匹配正则'],
    ['url', '-', '合法 URL'],
    ['ip', '-', '合法 IP'],
    ['alpha', '-', '仅字母'],
    ['alpha_num', '-', '字母和数字'],
    ['date', 'format', '合法日期（默认 Y-m-d）'],
    ['confirmed', '-', '与字段_confirmation 匹配'],
]))
story.append(sp(8))

story.append(p('9.2 使用示例', 'h2'))
story.append(code(
    "$validator = new Validator($data, [\n"
    "    'name'     => 'required|string|min:2|max:50',\n"
    "    'email'    => 'required|email',\n"
    "    'password' => 'required|string|min:6|confirmed',\n"
    "    'age'      => 'numeric|between:18,100',\n"
    "]);\n\n"
    "if ($validator->fails()) {\n"
    "    return Response::json([\n"
    "        'error'    => '验证失败',\n"
    "        'messages' => $validator->errors(),\n"
    "    ], 422);\n"
    "}\n\n"
    "// 获取验证后的安全数据\n"
    "$validated = $validator->validated();\n\n"
    "// 获取第一条错误\n"
    "$firstError = $validator->firstError();"
))
story.append(PageBreak())

# ==================== 第10章 性能优化 ====================
story.append(p('10. 性能优化', 'h1'))
story.append(sp(6))

story.append(p('10.1 查询缓存', 'h2'))
story.append(code(
    "// 使用 Cache::remember 自动缓存查询结果\n"
    "$users = Cache::remember('active_users', function() {\n"
    "    return PandaDB::table('users')\n"
    "        ->where('status', 1)\n"
    "        ->order('created_at', 'desc')\n"
    "        ->limit(50)\n"
    "        ->select();\n"
    "}, 300);  // 缓存 5 分钟"
))
story.append(sp(8))

story.append(p('10.2 性能统计', 'h2'))
story.append(code(
    "// 获取查询日志\n"
    "$queries = PandaDB::getQueryLog();\n\n"
    "// 单独获取\n"
    "PandaDB::getQueryCount();       // 查询次数\n"
    "PandaDB::getTotalQueryTime();    // 总查询耗时（毫秒）\n"
    "PandaDB::getLastQueryTime();    // 最后一次查询耗时\n"
    "PandaDB::getSlowQueries();       // 慢查询列表\n\n"
    "// 设置慢查询阈值（毫秒）\n"
    "PandaDB::setSlowThreshold(100);"
))
story.append(sp(8))

story.append(p('10.3 无状态设计', 'h2'))
story.append(p(
    '框架默认关闭 PHP Session，通过 Token 机制实现认证，'
    '避免 Session 读写带来的 I/O 开销，最大化并发性能。'
    '入口文件中已包含以下处理：'
))
story.append(code(
    "// 关闭已启动的 Session\n"
    "if (session_status() === PHP_SESSION_ACTIVE) {\n"
    "    session_write_close();\n"
    "}\n"
    "// 禁止自动启动\n"
    "ini_set('session.auto_start', '0');"
))
story.append(PageBreak())

# ==================== 第11章 配置参考 ====================
story.append(p('11. 配置参考', 'h1'))
story.append(sp(6))

story.append(p('11.1 完整配置示例', 'h2'))
story.append(code(
    "return [\n"
    "    'env'      => 'development',\n"
    "    'debug'    => true,\n"
    "    'timezone' => 'Asia/Shanghai',\n\n"
    "    // 数据库配置\n"
    "    'database' => [\n"
    "        'driver'   => 'mysql',\n"
    "        'host'     => '127.0.0.1',\n"
    "        'port'     => 3306,\n"
    "        'database' => 'pandaapi',\n"
    "        'username' => 'root',\n"
    "        'password' => '',\n"
    "        'charset'  => 'utf8mb4',\n"
    "        'prefix'   => '',\n"
    "    ],\n\n"
    "    // 缓存配置\n"
    "    'cache' => [\n"
    "        'driver' => 'redis',\n"
    "        'redis' => [\n"
    "            'host'     => '127.0.0.1',\n"
    "            'port'     => 6379,\n"
    "            'password' => null,\n"
    "            'database' => 0,\n"
    "            'prefix'   => 'pandaapi:',\n"
    "        ],\n"
    "        'default_ttl' => 3600,\n"
    "    ],\n\n"
    "    'middleware' => ['Cors', 'Log'],\n\n"
    "    'throttle' => [\n"
    "        'max_attempts'  => 60,\n"
    "        'decay_minutes' => 1,\n"
    "    ],\n\n"
    "    'cors' => [\n"
    "        'origin'  => '*',\n"
    "        'methods' => 'GET, POST, PUT, DELETE, OPTIONS',\n"
    "        'headers' => 'Content-Type, Authorization',\n"
    "    ],\n"
    "];"
))
story.append(PageBreak())

# ==================== 附录 ====================
story.append(p('附录 A  目录结构', 'h1'))
story.append(sp(6))
story.append(code(
    "pandaapi/\n"
    "+-- src/\n"
    "|   +-- Core/            # 核心类 (App, Request, Response)\n"
    "|   +-- Database/        # PandaDB 数据库类\n"
    "|   +-- Cache/           # 缓存 (Cache, Drivers, QueryCache)\n"
    "|   +-- Route/           # 路由 (Route, RouteItem, Middleware)\n"
    "|   +-- Middleware/      # 内置中间件\n"
    "|   +-- Validation/      # 验证器\n"
    "|   +-- Exception/       # 异常类\n"
    "+-- demo/                # 完整演示项目\n"
    "|   +-- index.php        # 演示入口\n"
    "|   +-- Controllers.php  # 控制器示例\n"
    "|   +-- config.php       # 配置文件\n"
    "|   +-- init_database.php# 数据库初始化\n"
    "|   +-- api_test.php     # API 测试脚本\n"
    "+-- app/Controller/      # 应用控制器\n"
    "+-- config/              # 配置文件\n"
    "+-- routes/              # 路由定义\n"
    "+-- tests/               # 单元测试\n"
    "+-- public/              # Web 入口\n"
    "+-- composer.json"
))
story.append(sp(20))

story.append(p('附录 B  许可证', 'h1'))
story.append(sp(6))
story.append(p('MIT License - 可自由使用、修改和分发。'))
story.append(sp(30))
story.append(p('(c) 2024 熊猫API框架 (PandaAPI). 保留所有权利。', 'caption'))

# ============================================================
# 生成 PDF
# ============================================================
doc.build(story)
print('OK: /workspace/pandaapi/熊猫API框架中文手册.pdf')
