#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
熊猫API框架 完整功能 Demo 说明书 PDF 生成器
基于 generate_demo_doc.py 中的 PHP 语法高亮方案
- 代码块使用 Paragraph + <br/> + &nbsp; 方案
- VS Code Dark+ 配色
- 1.5 倍行距 (fontSize=7.5, leading=12)
"""

import re
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.lib.colors import HexColor, black, white
from reportlab.lib.styles import ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    PageBreak
)
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.enums import TA_LEFT, TA_CENTER

# ============================================================
# 注册字体
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
# 语法高亮颜色（VS Code Dark+ 配色方案）
# ============================================================
C_KEYWORD   = HexColor('#c586c0')
C_STRING    = HexColor('#ce9178')
C_COMMENT   = HexColor('#d4d4d4')
C_NUMBER    = HexColor('#b5cea8')
C_VARIABLE  = HexColor('#9cdcfe')
C_FUNCTION  = HexColor('#dcdcaa')
C_TAG       = HexColor('#c586c0')
C_DEFAULT   = HexColor('#d4d4d4')
C_BUILTIN   = HexColor('#4ec9b0')
C_OPERATOR  = HexColor('#d4d4d4')
C_CODE_BG   = HexColor('#1e1e1e')
C_LINE_NUM  = HexColor('#4b5263')

# ============================================================
# 文档颜色
# ============================================================
PRIMARY   = HexColor('#1a365d')
ACCENT    = HexColor('#2b6cb0')
LIGHT_BG  = HexColor('#f7fafc')
GRAY      = HexColor('#718096')
DARK      = HexColor('#2d3748')
WHITE     = HexColor('#ffffff')
TABLE_HEAD_BG = HexColor('#2b6cb0')
TABLE_ALT_BG  = HexColor('#ebf4ff')

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
    'title':    ParagraphStyle('T', fontName=CJK, fontSize=26, leading=36,
                              textColor=PRIMARY, alignment=TA_CENTER, spaceAfter=12),
    'subtitle': ParagraphStyle('ST', fontName=CJK, fontSize=13, leading=18,
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
    'if', 'else', 'elseif', 'while', 'for', 'foreach', 'as',
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
    """对单行 PHP/Bash 代码进行语法高亮，返回 reportlab XML 标签字符串"""
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

        # 多行注释开始
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

            if word in ('php', '?php'):
                result += f'<font color="{C_TAG.hexval()}">&lt;?php</font>'
                i = j
                continue

            k = j
            while k < n and line[k] == ' ':
                k += 1

            if word.lower() in PHP_KEYWORDS:
                result += f'<font color="{C_KEYWORD.hexval()}">{esc(word)}</font>'
            elif word in PHP_BUILTINS or (k < n and line[k] == '('):
                result += f'<font color="{C_FUNCTION.hexval()}">{esc(word)}</font>'
            elif word[0].isupper():
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

        # < 特殊处理
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

        result += c
        i += 1

    return result


def esc(text):
    """转义 XML 特殊字符"""
    return text.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;')


def wrap_cjk(text):
    """后处理：将尚未被 font 标签包裹的中文字符用 CJK 字体包裹"""
    # 匹配不在 <font> 标签内的中文字符（含中文标点）
    def _replace(m):
        char = m.group(0)
        return f'<font name="{CJK}">{char}</font>'
    # 只处理不在已有 font 标签内的中文
    # 先把已有 font 标签内容保护起来
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

    highlighted_lines = []
    for line in lines:
        highlighted = php_highlight(line)
        highlighted = wrap_cjk(highlighted)
        highlighted_lines.append(highlighted)

    code_html = '<br/>'.join(highlighted_lines)
    code_html = re.sub(r'^  ', lambda m: '&nbsp;' * len(m.group()), code_html, flags=re.MULTILINE)
    code_html = re.sub(r'    ', '&nbsp;&nbsp;&nbsp;&nbsp;', code_html)

    code_block = Paragraph(code_html, S['code_xpre'])
    elements.append(code_block)
    return elements


def li(text):
    return Paragraph('• ' + text, S['li'])


def make_table(headers, rows, col_widths=None):
    """创建美观表格（蓝色表头、交替行色）"""
    data = [headers] + rows
    if col_widths is None:
        col_widths = [CW / len(headers)] * len(headers)

    t = Table(data, colWidths=col_widths, repeatRows=1)
    style_cmds = [
        ('BACKGROUND', (0, 0), (-1, 0), TABLE_HEAD_BG),
        ('TEXTCOLOR', (0, 0), (-1, 0), WHITE),
        ('FONTNAME', (0, 0), (-1, 0), CJK),
        ('FONTSIZE', (0, 0), (-1, 0), 9),
        ('FONTNAME', (0, 1), (-1, -1), CJK),
        ('FONTSIZE', (0, 1), (-1, -1), 8),
        ('TEXTCOLOR', (0, 1), (-1, -1), DARK),
        ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('GRID', (0, 0), (-1, -1), 0.5, HexColor('#cbd5e0')),
        ('TOPPADDING', (0, 0), (-1, -1), 5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 5),
        ('LEFTPADDING', (0, 0), (-1, -1), 6),
        ('RIGHTPADDING', (0, 0), (-1, -1), 6),
    ]
    for i in range(1, len(data)):
        if i % 2 == 0:
            style_cmds.append(('BACKGROUND', (0, i), (-1, i), TABLE_ALT_BG))

    t.setStyle(TableStyle(style_cmds))
    return t


# ============================================================
# 构建 PDF 文档
# ============================================================
OUTPUT = '/workspace/pandaapi/熊猫API_Demo完整说明书.pdf'

doc = SimpleDocTemplate(
    OUTPUT,
    pagesize=A4,
    leftMargin=LM, rightMargin=RM,
    topMargin=TM, bottomMargin=BM,
)

story = []

# ==================== 封面 ====================
story.append(sp(100))
story.append(p('<b>熊猫API框架</b>', 'title'))
story.append(p('<b>完整功能 Demo 说明书</b>', 'title'))
story.append(sp(20))
story.append(p('PandaAPI Framework - Complete Demo Guide', 'subtitle'))
story.append(sp(40))
story.append(p('版本: v1.0', 'caption'))
story.append(p('日期: 2024年', 'caption'))
story.append(PageBreak())

# ==================== 目录 ====================
story.append(p('目  录', 'h1'))
story.append(sp(10))

toc_items = [
    '第1章  框架概述',
    '第2章  项目结构',
    '第3章  PandaDB 数据库操作',
    '第4章  缓存系统',
    '第5章  路由系统',
    '第6章  验证器',
    '第7章  中间件',
    '第8章  公共函数',
    '第9章  MVC 应用开发',
    '第10章 API 接口测试',
]
for item in toc_items:
    story.append(p(item, 'body'))
    story.append(sp(4))

story.append(PageBreak())

# ==================== 第1章 框架概述 ====================
story.append(p('第1章  框架概述', 'h1'))
story.append(sp(8))

story.append(p('1.1 框架简介', 'h2'))
story.append(p(
    '熊猫API框架 (PandaAPI) 是一个轻量级、高性能的 PHP API 开发框架，'
    '融合了 ThinkPHP 和 PandaDB 两种数据库操作风格，内置缓存系统、路由引擎、'
    '验证器、中间件等完整功能，旨在帮助开发者快速构建 RESTful API 应用。'
))
story.append(p(
    '框架遵循简洁优雅的设计理念，提供直观的链式调用 API，'
    '同时保持极低的性能开销，适合中小型 API 项目快速开发。'
))

story.append(p('1.2 技术优势', 'h2'))
story.append(sp(4))
story.append(make_table(
    ['序号', '特性', '说明'],
    [
        ['1', '双风格数据库操作', '同时支持 ThinkPHP 链式查询和 PandaDB 快捷方法'],
        ['2', '多缓存驱动', '支持 File / Redis / Memcache / OpenResty'],
        ['3', '灵活路由系统', '支持 RESTful 路由、路由组、参数约束'],
        ['4', '内置验证器', '提供丰富的验证规则，支持自定义扩展'],
        ['5', '中间件机制', '支持全局中间件和路由级中间件'],
        ['6', 'MVC 架构', '支持控制器/模型分层，也支持闭包路由快速开发'],
        ['7', '事务支持', '提供回调式和手动式两种事务操作方式'],
        ['8', 'SQL 日志', '内置慢查询检测和 SQL 执行日志'],
    ],
    [30, 100, CW - 130]
))

story.append(sp(8))
story.append(p('1.3 环境要求', 'h2'))
story.append(li('PHP >= 7.4（推荐 8.0+）'))
story.append(li('PDO PHP 扩展（必需）'))
story.append(li('Redis 扩展（可选，用于 Redis 缓存驱动）'))
story.append(li('Composer（用于依赖管理）'))
story.append(li('Web 服务器：Apache / Nginx / PHP 内置服务器'))

story.append(p('1.4 快速启动', 'h2'))
story.extend(code('''# 1. 克隆项目
git clone https://github.com/pandaapi/pandaapi-demo.git
cd pandaapi-demo

# 2. 安装依赖
composer install

# 3. 配置数据库（编辑 config/database.php）
# 4. 初始化数据库
php init_database.php

# 5. 启动开发服务器
php -S localhost:8080 -t public

# 6. 访问测试
curl http://localhost:8080/api/v1/health''', '快速启动命令'))

story.append(PageBreak())

# ==================== 第2章 项目结构 ====================
story.append(p('第2章  项目结构', 'h1'))
story.append(sp(8))

story.append(p('2.1 目录结构', 'h2'))
story.extend(code('''pandaapi/
+-- app/                    # 应用目录
|   +-- Controllers/        # 控制器目录
|   |   +-- UserController.php
|   |   +-- PostController.php
|   +-- Models/             # 模型目录
|   |   +-- UserModel.php
|   |   +-- PostModel.php
|   +-- common.php          # 公共函数文件
+-- config/                 # 配置目录
|   +-- database.php        # 数据库配置
|   +-- cache.php           # 缓存配置
|   +-- app.php             # 应用配置
+-- public/                 # Web 根目录
|   +-- index.php           # 入口文件
+-- routes/                 # 路由目录
|   +-- api.php             # API 路由定义
|   +-- web.php             # Web 路由定义
+-- runtime/                # 运行时目录
|   +-- cache/              # 缓存文件
|   +-- logs/               # 日志文件
+-- vendor/                 # Composer 依赖
+-- composer.json           # Composer 配置
+-- init_database.php       # 数据库初始化脚本''', '项目目录结构'))

story.append(p('2.2 MVC 架构说明', 'h2'))
story.append(p(
    '框架采用经典的 MVC（Model-View-Controller）架构模式：'
))
story.append(li('<b>Model（模型）</b>：负责数据库交互和数据逻辑，继承 PandaDB 的链式查询能力'))
story.append(li('<b>View（视图）</b>：API 框架中视图层由 JSON 响应替代，通过 Response 类统一输出'))
story.append(li('<b>Controller（控制器）</b>：处理请求逻辑，调用模型获取数据，返回响应'))
story.append(p(
    '同时，框架也支持闭包路由模式，适合小型项目快速开发，无需创建控制器文件。'
))

story.append(p('2.3 请求生命周期', 'h2'))
story.extend(code('''// 请求生命周期流程：
// 1. 用户请求 -> public/index.php
// 2. 加载 Composer 自动加载器
// 3. 创建 App 实例，加载配置
// 4. 执行全局中间件（CORS、日志等）
// 5. 路由匹配 -> 找到对应处理函数/控制器
// 6. 执行路由级中间件（Auth、Throttle等）
// 7. 执行业务逻辑（控制器方法 / 闭包函数）
// 8. 返回 JSON 响应
// 9. 记录日志、发送响应

// 入口文件示例：
// public/index.php
require_once __DIR__ . '/../vendor/autoload.php';

$app = new PandaAPI\\Core\\App();
$app->run();''', '请求生命周期'))

story.append(PageBreak())

# ==================== 第3章 PandaDB 数据库操作 ====================
story.append(p('第3章  PandaDB 数据库操作', 'h1'))
story.append(sp(8))

story.append(p('3.1 数据库配置', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Database\\PandaDB;

// 数据库连接配置
PandaDB::config([
    'driver'   => 'mysql',       // 数据库类型
    'host'     => '127.0.0.1',   // 数据库主机
    'port'     => 3306,          // 端口
    'database' => 'pandaapi_demo', // 数据库名
    'username' => 'root',        // 用户名
    'password' => '',            // 密码
    'charset'  => 'utf8mb4',     // 字符集
    'prefix'   => 'panda_',      // 表前缀
]);''', '数据库配置'))

story.append(p('3.2 ThinkPHP 风格链式查询', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Database\\PandaDB;

// 查询多条记录 (select)
$users = PandaDB::table('users')
    ->where('status', 1)
    ->order('created_at', 'desc')
    ->limit(10)
    ->select();

// 查询单条记录 (find)
$user = PandaDB::table('users')
    ->where('id', 1)
    ->find();

// 多条件查询
$users = PandaDB::table('users')
    ->where('status', 1)
    ->where('age', '>=', 18)
    ->order('id', 'asc')
    ->limit(0, 20)
    ->select();

// 指定字段
$users = PandaDB::table('users')
    ->field('id, name, email')
    ->where('status', 1)
    ->select();''', 'ThinkPHP 风格链式查询'))

story.append(p('3.3 PandaDB 风格快捷方法', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Database\\PandaDB;

// 查询多条 (select)
$users = PandaDB::select('users', '*', [
    'status' => 1,
    'ORDER'  => ['created_at' => 'DESC'],
    'LIMIT'  => 10,
]);

// 查询单条 (get)
$user = PandaDB::get('users', ['id', 'name', 'email'], [
    'id' => 1,
]);

// 插入数据 (insert)
$id = PandaDB::insert('users', [
    'name'       => '张三',
    'email'      => 'zhangsan@example.com',
    'password'   => password_hash('123456', PASSWORD_DEFAULT),
    'status'     => 1,
    'created_at' => date('Y-m-d H:i:s'),
]);

// 更新数据 (update)
PandaDB::update('users', [
    'name'       => '李四',
    'updated_at' => date('Y-m-d H:i:s'),
], ['id' => 1]);

// 删除数据 (delete)
PandaDB::delete('users', ['id' => 1]);''', 'PandaDB 风格快捷方法'))

story.append(p('3.4 聚合查询', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Database\\PandaDB;

// count - 计数
$total = PandaDB::table('users')->count();
$active = PandaDB::table('users')->where('status', 1)->count();

// sum - 求和
$totalViews = PandaDB::table('posts')->sum('views');

// avg - 平均值
$avgViews = PandaDB::table('posts')->avg('views');

// max - 最大值
$maxViews = PandaDB::table('posts')->max('views');

// min - 最小值
$minViews = PandaDB::table('posts')->min('views');

// 综合统计
$stats = [
    'total_users' => PandaDB::table('users')->count(),
    'total_posts' => PandaDB::table('posts')->count(),
    'total_views' => PandaDB::table('posts')->sum('views'),
    'avg_views'   => round(PandaDB::table('posts')->avg('views'), 2),
];''', '聚合查询'))

story.append(p('3.5 JOIN 关联查询', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Database\\PandaDB;

// INNER JOIN 查询
$posts = PandaDB::table('posts')
    ->field('posts.*, users.name as author_name')
    ->join('users', 'posts.user_id = users.id')
    ->where('posts.status', 1)
    ->order('posts.created_at', 'desc')
    ->select();

// LEFT JOIN 查询
$posts = PandaDB::table('posts AS p')
    ->field('p.id, p.title, p.views, u.name as author_name')
    ->leftJoin('users AS u', 'p.user_id = u.id')
    ->where('p.status', 1)
    ->select();

// 多表 JOIN
$result = PandaDB::table('orders AS o')
    ->field('o.*, u.name, p.title')
    ->join('users AS u', 'o.user_id = u.id')
    ->join('products AS p', 'o.product_id = p.id')
    ->where('o.status', 'completed')
    ->select();''', 'JOIN 关联查询'))

story.append(p('3.6 分页查询', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Database\\PandaDB;

// paginate 分页查询
$page    = (int)($_GET['page'] ?? 1);
$perPage = (int)($_GET['per_page'] ?? 15);

$result = PandaDB::table('users')
    ->where('status', 1)
    ->order('created_at', 'desc')
    ->paginate($perPage, $page);

// 返回结构：
// $result = [
//     'data'         => [...],       // 当前页数据
//     'total'        => 100,         // 总记录数
//     'current_page' => 1,           // 当前页码
//     'per_page'     => 15,          // 每页条数
//     'last_page'    => 7,           // 最后一页
// ];

// 配合 Response::paginate 返回 JSON
return Response::paginate(
    $result['data'],
    $result['total'],
    $result['current_page'],
    $result['per_page']
);''', '分页查询'))

story.append(p('3.7 WHERE 高级条件', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Database\\PandaDB;

// whereIn - IN 查询
$users = PandaDB::table('users')
    ->whereIn('id', [1, 2, 3, 4, 5])
    ->select();

// whereNotIn - NOT IN 查询
$users = PandaDB::table('users')
    ->whereNotIn('status', [0, -1])
    ->select();

// whereBetween - 范围查询
$users = PandaDB::table('users')
    ->whereBetween('age', [18, 60])
    ->select();

// whereLike - 模糊查询
$users = PandaDB::table('users')
    ->whereLike('name', '%张%')
    ->select();

// whereNull - NULL 查询
$users = PandaDB::table('users')
    ->whereNull('deleted_at')
    ->select();

// whereNotNull - NOT NULL 查询
$users = PandaDB::table('users')
    ->whereNotNull('email')
    ->select();

// whereOr - OR 条件
$users = PandaDB::table('users')
    ->where('status', 1)
    ->whereOr('role', 'admin')
    ->select();

// whereRaw - 原生条件
$users = PandaDB::table('users')
    ->whereRaw("DATE(created_at) = '2024-01-01'")
    ->select();''', 'WHERE 高级条件'))

story.append(p('3.8 原生 SQL', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Database\\PandaDB;

// query - 原生查询（带参数绑定，防SQL注入）
$users = PandaDB::query(
    "SELECT * FROM panda_users WHERE status = ? ORDER BY id DESC LIMIT ?",
    [1, 10]
);

// execute - 原生执行（INSERT/UPDATE/DELETE）
PandaDB::execute(
    "UPDATE panda_users SET status = ? WHERE id = ?",
    [0, 5]
);

// 原生聚合查询
$result = PandaDB::query(
    "SELECT COUNT(*) as total, AVG(views) as avg_views FROM panda_posts"
);

// 复杂原生查询
$stats = PandaDB::query(
    "SELECT u.name, COUNT(p.id) as post_count
     FROM panda_users u
     LEFT JOIN panda_posts p ON u.id = p.user_id
     GROUP BY u.id
     HAVING post_count > 0
     ORDER BY post_count DESC"
);''', '原生 SQL'))

story.append(p('3.9 事务操作', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Database\\PandaDB;

// 方式一：回调式事务（推荐）
try {
    $result = PandaDB::transaction(function() {
        $userId = PandaDB::table('users')->insert([
            'name'       => '事务测试用户',
            'email'      => 'tx_' . time() . '@test.com',
            'password'   => password_hash('123456', PASSWORD_DEFAULT),
            'status'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        PandaDB::table('posts')->insert([
            'user_id'    => $userId,
            'title'      => '欢迎文章',
            'content'    => '这是用户的欢迎文章',
            'status'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['user_id' => $userId];
    });

    // 事务自动提交
} catch (Exception $e) {
    // 事务自动回滚
    echo '事务失败: ' . $e->getMessage();
}

// 方式二：手动事务
PandaDB::beginTransaction();
try {
    PandaDB::table('users')->insert([...]);
    PandaDB::table('posts')->insert([...]);
    PandaDB::commit();
} catch (Exception $e) {
    PandaDB::rollBack();
}''', '事务操作'))

story.append(p('3.10 慢查询与 SQL 日志', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Database\\PandaDB;

// 设置慢查询阈值（毫秒）
PandaDB::setSlowThreshold(100);

// 获取查询次数
$queryCount = PandaDB::getQueryCount();

// 获取 SQL 日志
$logs = PandaDB::getQueryLog();

// 在健康检查接口中使用
Route::get('/api/v1/health', function() {
    return Response::success([
        'framework'  => 'PandaAPI',
        'status'     => 'healthy',
        'db_queries' => PandaDB::getQueryCount(),
        'query_log'  => PandaDB::getQueryLog(),
    ]);
});

// SQL 日志输出示例：
// [
//     ['sql' => 'SELECT * FROM panda_users WHERE id = ?', 'time' => 1.23],
//     ['sql' => 'SELECT * FROM panda_posts ...', 'time' => 150.5], // 慢查询!
// ]''', '慢查询与 SQL 日志'))

story.append(PageBreak())

# ==================== 第4章 缓存系统 ====================
story.append(p('第4章  缓存系统', 'h1'))
story.append(sp(8))

story.append(p('4.1 缓存配置', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Cache\\Cache;

// 文件缓存配置
Cache::config([
    'driver' => 'file',
    'file' => [
        'path' => __DIR__ . '/../runtime/cache',
    ],
    'default_ttl' => 3600,
]);

// Redis 缓存配置
Cache::config([
    'driver' => 'redis',
    'redis' => [
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'password' => null,
        'database' => 0,
        'prefix'   => 'pandaapi:',
    ],
    'default_ttl' => 3600,
]);''', '缓存配置'))

story.append(p('4.2 基础操作', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Cache\\Cache;

// set - 设置缓存（带过期时间，单位秒）
Cache::set('user:1', ['id' => 1, 'name' => '张三'], 3600);
Cache::set('config:site', ['title' => '我的网站'], 7200);

// get - 获取缓存
$user = Cache::get('user:1');
// 返回: ['id' => 1, 'name' => '张三'] 或 null

// get 带默认值
$user = Cache::get('user:999', ['id' => 0, 'name' => '游客']);

// has - 检查缓存是否存在
if (Cache::has('user:1')) {
    $user = Cache::get('user:1');
}

// delete / forget - 删除缓存
Cache::delete('user:1');
Cache::forget('user:1');

// clear - 清空所有缓存
Cache::clear();''', '缓存基础操作'))

story.append(p('4.3 缓存回源 remember', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Cache\\Cache;
use PandaAPI\\Database\\PandaDB;

// Cache::remember - 缓存回源模式
// 如果缓存存在则直接返回，不存在则执行回调并自动缓存结果
$user = Cache::remember("user:{$id}", function() use ($id) {
    return PandaDB::table('users')
        ->where('id', (int)$id)
        ->find();
}, 300); // 缓存 5 分钟

// 典型应用场景：用户详情接口
Route::get('/users/{id}', function($id) {
    $user = Cache::remember("user:{$id}", function() use ($id) {
        return PandaDB::table('users')
            ->where('id', (int)$id)
            ->find();
    }, 300);

    if (!$user) {
        return Response::error('用户不存在', 404);
    }

    return Response::success($user);
});''', '缓存回源 remember'))

story.append(p('4.4 原子操作 increment/decrement', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Cache\\Cache;

// increment - 原子递增
Cache::set('page:views', 0);
$views = Cache::increment('page:views');  // 1
$views = Cache::increment('page:views');  // 2
$views = Cache::increment('page:views', 5); // 7（步长为5）

// decrement - 原子递减
$views = Cache::decrement('page:views');  // 6
$views = Cache::decrement('page:views', 3); // 3（步长为3）

// 应用场景：文章浏览量计数
Route::get('/posts/{id}', function($id) {
    $cacheKey = "post:views:{$id}";
    $views = Cache::increment($cacheKey);

    // 定期同步到数据库（或使用队列异步同步）
    if ($views % 100 === 0) {
        PandaDB::table('posts')
            ->where('id', (int)$id)
            ->update(['views' => $views]);
    }

    return Response::success(['views' => $views]);
});''', '原子操作 increment/decrement'))

story.append(p('4.5 批量操作', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Cache\\Cache;

// setMultiple - 批量设置
Cache::setMultiple([
    'config:site_name'    => '熊猫API',
    'config:site_desc'    => '轻量级PHP框架',
    'config:site_version' => '1.0.0',
], 3600);

// getMultiple - 批量获取
$values = Cache::getMultiple([
    'config:site_name',
    'config:site_desc',
    'config:site_version',
]);
// 返回: ['config:site_name' => '熊猫API', ...]

// deleteMultiple - 批量删除
Cache::deleteMultiple([
    'config:site_name',
    'config:site_desc',
]);''', '批量操作'))

story.append(p('4.6 标签缓存', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Cache\\Cache;

// 标签缓存 - 按标签分组管理缓存
// 设置带标签的缓存
Cache::set('user:1', $user1, 3600)->tag('users');
Cache::set('user:2', $user2, 3600)->tag('users');
Cache::set('user:3', $user3, 3600)->tag('users');

// 也可以设置多个标签
Cache::set('post:1', $post1, 3600)->tag('posts', 'user:1_posts');

// 按标签清除缓存
Cache::flushTag('users');     // 清除所有 users 标签的缓存
Cache::flushTag('posts');     // 清除所有 posts 标签的缓存
Cache::flushTag('user:1_posts'); // 清除指定用户的文章缓存

// 应用场景：用户信息更新时批量清除缓存
Route::put('/users/{id}', function($id) {
    // ... 更新用户数据 ...

    // 清除该用户相关的所有缓存
    Cache::forget("user:{$id}");
    Cache::flushTag("user:{$id}_related");

    return Response::success(['message' => '更新成功']);
});''', '标签缓存'))

story.append(PageBreak())

# ==================== 第5章 路由系统 ====================
story.append(p('第5章  路由系统', 'h1'))
story.append(sp(8))

story.append(p('5.1 路由定义', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Route\\Route;

// GET 请求
Route::get('/api/v1/users', function() {
    return Response::success(PandaDB::table('users')->select());
});

// POST 请求
Route::post('/api/v1/users', function() {
    $data = (new Request())->all();
    $id = PandaDB::table('users')->insert($data);
    return Response::success(['id' => $id], 201);
});

// PUT 请求
Route::put('/api/v1/users/{id}', function($id) {
    $data = (new Request())->all();
    PandaDB::table('users')->where('id', $id)->update($data);
    return Response::success(['message' => '更新成功']);
});

// DELETE 请求
Route::delete('/api/v1/users/{id}', function($id) {
    PandaDB::table('users')->where('id', $id)->delete();
    return Response::success(['message' => '删除成功']);
});

// PATCH 请求
Route::patch('/api/v1/users/{id}/status', function($id) {
    $data = (new Request())->all();
    PandaDB::table('users')->where('id', $id)->update($data);
    return Response::success(['message' => '状态更新成功']);
});

// match - 匹配多种请求方法
Route::match(['GET', 'POST'], '/api/v1/search', function() {
    // 处理搜索逻辑
});

// any - 匹配所有请求方法
Route::any('/api/v1/test', function() {
    return Response::success(['message' => '测试']);
});''', '路由定义'))

story.append(p('5.2 路由组', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Route\\Route;

// 路由组 - 统一前缀和中间件
Route::group([
    'prefix'     => '/api/v1',
    'middleware' => ['Cors'],
], function() {

    // 公开接口
    Route::get('/health', [HealthController::class, 'index']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    // 需要认证的接口
    Route::group(['middleware' => ['Auth']], function() {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        Route::get('/posts', [PostController::class, 'index']);
        Route::post('/posts', [PostController::class, 'store']);
    });

    // 需要限流的接口
    Route::group(['middleware' => ['Throttle:60,1']], function() {
        Route::post('/sms/send', [SmsController::class, 'send']);
        Route::post('/email/send', [EmailController::class, 'send']);
    });
});''', '路由组'))

story.append(p('5.3 路由参数约束', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Route\\Route;

// whereNumber - 数字约束
Route::get('/users/{id}', function($id) {
    // $id 只能是数字
})->whereNumber('id');

// whereAlpha - 字母约束
Route::get('/categories/{slug}', function($slug) {
    // $slug 只能是字母
})->whereAlpha('slug');

// where - 正则约束
Route::get('/posts/{year}/{month}', function($year, $month) {
    // year: 4位数字, month: 2位数字
})->where('year', '[0-9]{4}')->where('month', '[0-9]{2}');

// 组合约束
Route::get('/files/{id}/{name}', function($id, $name) {
    //
})->whereNumber('id')->where('name', '[a-zA-Z0-9_\\-]+');''', '路由参数约束'))

story.append(p('5.4 完整路由表', 'h2'))
story.append(sp(4))
story.append(make_table(
    ['方法', '路径', '说明'],
    [
        ['GET', '/api/v1/health', '健康检查'],
        ['POST', '/api/v1/login', '用户登录'],
        ['POST', '/api/v1/register', '用户注册'],
        ['POST', '/api/v1/logout', '用户登出'],
        ['GET', '/api/v1/users', '用户列表（分页）'],
        ['GET', '/api/v1/users/{id}', '用户详情'],
        ['POST', '/api/v1/users', '创建用户'],
        ['PUT', '/api/v1/users/{id}', '更新用户'],
        ['DELETE', '/api/v1/users/{id}', '删除用户'],
        ['GET', '/api/v1/posts', '文章列表（分页）'],
        ['GET', '/api/v1/posts/{id}', '文章详情'],
        ['POST', '/api/v1/posts', '创建文章'],
        ['PUT', '/api/v1/posts/{id}', '更新文章'],
        ['DELETE', '/api/v1/posts/{id}', '删除文章'],
        ['GET', '/api/v1/posts/{id}/comments', '文章评论列表'],
        ['POST', '/api/v1/posts/{id}/comments', '添加评论'],
        ['GET', '/api/v1/stats', '统计信息'],
        ['GET', '/api/v1/categories', '分类列表'],
        ['POST', '/api/v1/categories', '创建分类'],
        ['PUT', '/api/v1/categories/{id}', '更新分类'],
        ['DELETE', '/api/v1/categories/{id}', '删除分类'],
        ['GET', '/api/v1/tags', '标签列表'],
        ['POST', '/api/v1/tags', '创建标签'],
        ['GET', '/api/v1/search', '全局搜索'],
        ['GET', '/api/v1/upload/image', '上传图片页面'],
        ['POST', '/api/v1/upload/image', '上传图片'],
        ['POST', '/api/v1/upload/file', '上传文件'],
        ['GET', '/api/v1/cache/test', '缓存测试'],
        ['GET', '/api/v1/sql', '原生SQL查询'],
        ['POST', '/api/v1/transaction', '事务测试'],
        ['GET', '/api/v1/pandadb-demo', 'PandaDB双风格演示'],
        ['GET', '/api/v1/config', '获取配置信息'],
        ['POST', '/api/v1/email/send', '发送邮件'],
        ['POST', '/api/v1/sms/send', '发送短信'],
        ['GET', '/api/v1/logs', '操作日志'],
        ['GET', '/api/v1/profile', '当前用户信息'],
        ['PUT', '/api/v1/profile', '更新个人信息'],
        ['PUT', '/api/v1/password', '修改密码'],
        ['POST', '/api/v1/forgot-password', '忘记密码'],
    ],
    [50, 140, CW - 190]
))

story.append(PageBreak())

# ==================== 第6章 验证器 ====================
story.append(p('第6章  验证器', 'h1'))
story.append(sp(8))

story.append(p('6.1 验证规则', 'h2'))
story.append(sp(4))
story.append(make_table(
    ['规则', '说明', '示例'],
    [
        ['required', '字段必填', "'name' => 'required'"],
        ['string', '必须是字符串', "'name' => 'string'"],
        ['integer', '必须是整数', "'age' => 'integer'"],
        ['numeric', '必须是数字', "'price' => 'numeric'"],
        ['email', '必须是邮箱格式', "'email' => 'email'"],
        ['url', '必须是URL格式', "'website' => 'url'"],
        ['min:n', '最小长度/值', "'name' => 'min:2'"],
        ['max:n', '最大长度/值', "'name' => 'max:50'"],
        ['between:n,m', '介于n和m之间', "'age' => 'between:18,60'"],
        ['in:a,b,c', '必须在列表中', "'status' => 'in:0,1,2'"],
        ['not_in:a,b', '不能在列表中', "'role' => 'not_in:admin'"],
        ['regex:pattern', '正则匹配', "'phone' => 'regex:/^1[3-9]\\d{9}$/'"],
        ['confirmed', '需要确认字段', "'password' => 'confirmed'"],
        ['date', '必须是日期格式', "'birthday' => 'date'"],
        ['array', '必须是数组', "'ids' => 'array'"],
        ['alpha', '只能是字母', "'name' => 'alpha'"],
        ['alpha_num', '字母和数字', "'username' => 'alpha_num'"],
    ],
    [70, 100, CW - 170]
))

story.append(sp(8))
story.append(p('6.2 使用示例', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Validation\\Validator;
use PandaAPI\\Core\\Request;

// 基本验证
$request = new Request();
$data = $request->all();

$validator = new Validator($data, [
    'name'     => 'required|string|min:2|max:50',
    'email'    => 'required|email',
    'password' => 'required|string|min:6|confirmed',
    'age'      => 'integer|between:18,120',
    'status'   => 'in:0,1,2',
]);

// 检查验证是否失败
if ($validator->fails()) {
    return Response::json([
        'error'    => '验证失败',
        'messages' => $validator->errors(),
    ], 422);
}

// 获取验证后的安全数据
$safeData = $validator->validated();

// 自定义错误消息
$validator = new Validator($data, [
    'name'  => 'required|string|min:2',
    'email' => 'required|email',
], [
    'name.required'  => '用户名不能为空',
    'name.min'       => '用户名至少2个字符',
    'email.required' => '邮箱不能为空',
    'email.email'    => '请输入正确的邮箱格式',
]);

// 验证用户注册
$validator = new Validator($data, [
    'name'                  => 'required|string|min:2|max:50',
    'email'                 => 'required|email',
    'password'              => 'required|string|min:6',
    'password_confirmation' => 'required|string|min:6',
    'phone'                 => 'regex:/^1[3-9]\\d{9}$/',
]);

if ($validator->fails()) {
    return Response::json([
        'code'    => 422,
        'message' => '参数验证失败',
        'errors'  => $validator->errors(),
    ], 422);
}''', '验证器使用示例'))

story.append(PageBreak())

# ==================== 第7章 中间件 ====================
story.append(p('第7章  中间件', 'h1'))
story.append(sp(8))

story.append(p('7.1 内置中间件', 'h2'))
story.append(sp(4))
story.append(make_table(
    ['中间件', '说明', '配置参数'],
    [
        ['Cors', '跨域资源共享', 'origin, methods, headers'],
        ['Auth', 'Token 认证', 'token 请求头或参数'],
        ['Throttle', '接口限流', 'max_attempts, decay_minutes'],
        ['Log', '请求日志记录', '日志文件路径'],
        ['JsonBody', 'JSON 请求体解析', '自动解析 JSON 请求'],
    ],
    [70, 120, CW - 190]
))

story.append(sp(8))
story.append(p('7.2 使用示例', 'h2'))
story.extend(code('''<?php
use PandaAPI\\Route\\Route;

// 全局中间件 - 在 App 配置中设置
$app->config([
    'middleware' => ['Cors', 'Log', 'JsonBody'],
]);

// 路由组中间件
Route::group(['middleware' => ['Cors']], function() {
    Route::get('/api/public', function() {
        return Response::success(['message' => '公开接口']);
    });
});

// 单个路由中间件
Route::get('/api/profile', function() {
    return Response::success(['user' => '当前用户信息']);
})->middleware(['Auth']);

// 限流中间件（60次/分钟）
Route::post('/api/sms/send', function() {
    return Response::success(['message' => '短信已发送']);
})->middleware(['Throttle:60,1']);

// 组合中间件
Route::post('/api/admin/settings', function() {
    return Response::success(['message' => '设置已更新']);
})->middleware(['Auth', 'Throttle:30,1']);

// 自定义中间件示例
class CheckAdmin
{
    public function handle($request, Closure $next)
    {
        $user = $request->getAttribute('user');
        if ($user['role'] !== 'admin') {
            return Response::error('无权限访问', 403);
        }
        return $next($request);
    }
}

// 注册自定义中间件
Route::get('/api/admin/dashboard', function() {
    return Response::success(['stats' => '...']);
})->middleware(['Auth', 'CheckAdmin']);''', '中间件使用示例'))

story.append(PageBreak())

# ==================== 第8章 公共函数 ====================
story.append(p('第8章  公共函数', 'h1'))
story.append(sp(8))

story.append(p('8.1 函数列表', 'h2'))
story.append(sp(4))
story.append(make_table(
    ['函数名', '说明', '参数'],
    [
        ['json_success', '返回成功JSON响应', 'data, message, code'],
        ['json_error', '返回错误JSON响应', 'message, code, data'],
        ['get_input', '获取输入参数（支持默认值）', 'key, default'],
        ['generate_token', '生成随机Token', 'length'],
        ['password_encrypt', '密码加密', 'password'],
        ['password_verify', '密码验证', 'password, hash'],
        ['get_user_id', '获取当前登录用户ID', 'request'],
        ['get_client_ip', '获取客户端IP', 'request'],
        ['format_date', '格式化日期', 'timestamp, format'],
        ['pagination', '生成分页数据', 'total, page, per_page'],
        ['array_to_tree', '数组转树形结构', 'data, pid, id'],
        ['curl_get', 'CURL GET请求', 'url, headers, timeout'],
        ['curl_post', 'CURL POST请求', 'url, data, headers'],
        ['is_mobile', '判断是否手机访问', 'user_agent'],
        ['generate_order_no', '生成订单号', 'prefix'],
        ['upload_file', '文件上传处理', 'file, path'],
        ['send_sms', '发送短信', 'phone, content'],
        ['send_email', '发送邮件', 'to, subject, body'],
        ['write_log', '写入日志', 'message, level, file'],
        ['config', '获取配置项', 'key, default'],
        ['env', '获取环境变量', 'key, default'],
        ['redirect', '重定向', 'url'],
        ['abort', '中止请求返回错误', 'code, message'],
    ],
    [90, 130, CW - 220]
))

story.append(sp(8))
story.append(p('8.2 使用示例', 'h2'))
story.extend(code('''<?php
// json_success - 成功响应
return json_success(['id' => 1, 'name' => '张三']);
// 输出: {"code": 200, "message": "success", "data": {"id": 1, "name": "张三"}}

// json_error - 错误响应
return json_error('用户不存在', 404);
// 输出: {"code": 404, "message": "用户不存在", "data": null}

// get_input - 获取输入参数
$name  = get_input('name', '');        // POST/GET 参数
$email = get_input('email', 'default'); // 带默认值

// generate_token - 生成Token
$token = generate_token(32);
// 输出: "a1b2c3d4e5f6..."

// password_encrypt / password_verify
$hash = password_encrypt('123456');
$isValid = password_verify('123456', $hash); // true

// get_client_ip - 获取客户端IP
$ip = get_client_ip($request);
// 输出: "192.168.1.100"

// curl_get - GET请求
$data = curl_get('https://api.example.com/users', [
    'Authorization: Bearer ' . $token,
]);

// curl_post - POST请求
$result = curl_post('https://api.example.com/users', [
    'name'  => '张三',
    'email' => 'zhangsan@example.com',
], ['Content-Type: application/json']);

// write_log - 写日志
write_log('用户登录成功: ' . $email, 'info');
write_log('数据库错误: ' . $e->getMessage(), 'error');

// pagination - 分页数据
$pageData = pagination(100, 1, 15);
// 返回: ['total' => 100, 'current_page' => 1, 'per_page' => 15, 'last_page' => 7]''', '公共函数使用示例'))

story.append(PageBreak())

# ==================== 第9章 MVC 应用开发 ====================
story.append(p('第9章  MVC 应用开发', 'h1'))
story.append(sp(8))

story.append(p('9.1 控制器 Controller', 'h2'))
story.extend(code('''<?php
namespace App\\Controllers;

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

        $result = $builder->order('created_at', 'desc')
            ->paginate($perPage, $page);

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
        $user = Cache::remember("user:{$id}", function() use ($id) {
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

        $data['password']   = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['status']     = 1;
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = PandaDB::table('users')->insert($data);
        $user = PandaDB::table('users')->where('id', $id)->find();
        unset($user['password']);

        return Response::json($user, 201);
    }
}''', 'UserController 控制器'))

story.append(p('9.2 模型 Model', 'h2'))
story.extend(code('''<?php
namespace App\\Models;

use PandaAPI\\Database\\PandaDB;

class UserModel
{
    // 表名（不含前缀）
    protected $table = 'users';

    // 获取所有用户
    public function getAll($status = null)
    {
        $builder = PandaDB::table($this->table);
        if ($status !== null) {
            $builder->where('status', (int)$status);
        }
        return $builder->order('created_at', 'desc')->select();
    }

    // 根据ID查找用户
    public function findById($id)
    {
        return PandaDB::table($this->table)
            ->where('id', (int)$id)
            ->find();
    }

    // 根据邮箱查找
    public function findByEmail($email)
    {
        return PandaDB::table($this->table)
            ->where('email', $email)
            ->find();
    }

    // 创建用户
    public function create(array $data)
    {
        $data['status']     = 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        return PandaDB::table($this->table)->insert($data);
    }

    // 更新用户
    public function update($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return PandaDB::table($this->table)
            ->where('id', (int)$id)
            ->update($data);
    }

    // 删除用户
    public function delete($id)
    {
        return PandaDB::table($this->table)
            ->where('id', (int)$id)
            ->delete();
    }

    // 分页查询
    public function paginate($perPage = 15, $page = 1)
    {
        return PandaDB::table($this->table)
            ->order('created_at', 'desc')
            ->paginate($perPage, $page);
    }
}''', 'UserModel 模型'))

story.append(p('9.3 公共函数 common', 'h2'))
story.extend(code('''<?php
// app/common.php

/**
 * 返回成功JSON响应
 */
function json_success($data = null, $message = 'success', $code = 200)
{
    return json_encode([
        'code'    => $code,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 返回错误JSON响应
 */
function json_error($message = 'error', $code = 400, $data = null)
{
    return json_encode([
        'code'    => $code,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取输入参数
 */
function get_input($key = null, $default = null)
{
    $input = array_merge($_GET, $_POST);
    $jsonInput = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonInput)) {
        $input = array_merge($input, $jsonInput);
    }

    if ($key === null) {
        return $input;
    }
    return $input[$key] ?? $default;
}

/**
 * 生成随机Token
 */
function generate_token($length = 32)
{
    return bin2hex(random_bytes($length));
}

/**
 * 密码加密
 */
function password_encrypt($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 获取客户端IP
 */
function get_client_ip()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}''', '公共函数 common.php'))

story.append(p('9.4 路由配置 router', 'h2'))
story.extend(code('''<?php
// routes/api.php

use PandaAPI\\Route\\Route;
use App\\Controllers\\UserController;
use App\\Controllers\\PostController;
use App\\Controllers\\AuthController;

// 健康检查
Route::get('/api/v1/health', function() {
    return Response::success([
        'framework' => 'PandaAPI',
        'status'    => 'healthy',
        'time'      => date('Y-m-d H:i:s'),
    ]);
});

// 认证路由（公开）
Route::post('/api/v1/login', [AuthController::class, 'login']);
Route::post('/api/v1/register', [AuthController::class, 'register']);

// API 路由组
Route::group([
    'prefix'     => '/api/v1',
    'middleware' => ['Cors', 'Auth'],
], function() {

    // 用户资源路由
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // 文章资源路由
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);

    // 统计接口
    Route::get('/stats', function() {
        return Response::success([
            'users' => PandaDB::table('users')->count(),
            'posts' => PandaDB::table('posts')->count(),
        ]);
    });
});''', '路由配置 routes/api.php'))

story.append(PageBreak())

# ==================== 第10章 API 接口测试 ====================
story.append(p('第10章  API 接口测试', 'h1'))
story.append(sp(8))

story.append(p('10.1 curl 测试命令', 'h2'))
story.extend(code('''# ============================================
# 熊猫API框架 - 完整接口测试命令
# 基础地址: http://localhost:8080
# ============================================

# 1. 健康检查
curl -X GET http://localhost:8080/api/v1/health

# 2. 用户注册
curl -X POST http://localhost:8080/api/v1/register \\
  -H "Content-Type: application/json" \\
  -d '{"name":"张三","email":"zhangsan@test.com","password":"123456"}'

# 3. 用户登录
curl -X POST http://localhost:8080/api/v1/login \\
  -H "Content-Type: application/json" \\
  -d '{"email":"zhangsan@test.com","password":"123456"}'

# 4. 获取用户列表（分页）
curl -X GET "http://localhost:8080/api/v1/users?page=1&per_page=15"

# 5. 获取用户详情
curl -X GET http://localhost:8080/api/v1/users/1

# 6. 创建用户
curl -X POST http://localhost:8080/api/v1/users \\
  -H "Content-Type: application/json" \\
  -H "Authorization: Bearer YOUR_TOKEN" \\
  -d '{"name":"李四","email":"lisi@test.com","password":"123456"}'

# 7. 更新用户
curl -X PUT http://localhost:8080/api/v1/users/1 \\
  -H "Content-Type: application/json" \\
  -H "Authorization: Bearer YOUR_TOKEN" \\
  -d '{"name":"张三丰"}'

# 8. 删除用户
curl -X DELETE http://localhost:8080/api/v1/users/1 \\
  -H "Authorization: Bearer YOUR_TOKEN"

# 9. 获取文章列表
curl -X GET "http://localhost:8080/api/v1/posts?page=1&per_page=10"

# 10. 创建文章
curl -X POST http://localhost:8080/api/v1/posts \\
  -H "Content-Type: application/json" \\
  -H "Authorization: Bearer YOUR_TOKEN" \\
  -d '{"title":"测试文章","content":"这是文章内容"}'

# 11. 获取统计信息
curl -X GET http://localhost:8080/api/v1/stats

# 12. 缓存测试
curl -X GET http://localhost:8080/api/v1/cache/test

# 13. 原生SQL查询
curl -X GET http://localhost:8080/api/v1/sql

# 14. 事务测试
curl -X POST http://localhost:8080/api/v1/transaction \\
  -H "Authorization: Bearer YOUR_TOKEN"

# 15. PandaDB 双风格演示
curl -X GET http://localhost:8080/api/v1/pandadb-demo

# 16. 全局搜索
curl -X GET "http://localhost:8080/api/v1/search?keyword=PHP"

# 17. 获取分类列表
curl -X GET http://localhost:8080/api/v1/categories

# 18. 获取当前用户信息
curl -X GET http://localhost:8080/api/v1/profile \\
  -H "Authorization: Bearer YOUR_TOKEN"

# 19. 修改密码
curl -X PUT http://localhost:8080/api/v1/password \\
  -H "Content-Type: application/json" \\
  -H "Authorization: Bearer YOUR_TOKEN" \\
  -d '{"old_password":"123456","new_password":"654321"}'

# 20. 上传图片
curl -X POST http://localhost:8080/api/v1/upload/image \\
  -H "Authorization: Bearer YOUR_TOKEN" \\
  -F "image=@/path/to/image.jpg"''', 'curl 接口测试命令'))

# ==================== 结尾 ====================
story.append(sp(40))
story.append(p('— 文档结束 —', 'caption'))
story.append(p('熊猫API框架 PandaAPI Framework v1.0', 'caption'))

# ============================================================
# 生成 PDF
# ============================================================
doc.build(story)
print(f'OK: {OUTPUT}')
