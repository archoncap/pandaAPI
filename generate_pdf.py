#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
QuickAPI Framework Documentation PDF Generator
"""

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.lib.colors import HexColor
from reportlab.lib.styles import ParagraphStyle
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.enums import TA_LEFT, TA_CENTER
import os

# Register CJK font
CJK_FONT = "Helvetica"
font_registered = False

font_paths = [
    "/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc",
    "/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc", 
    "/usr/share/fonts/truetype/noto/NotoSansCJK-Regular.ttc",
    "/usr/share/fonts/opentype/noto/NotoSansSC-Regular.otf",
    "/usr/share/fonts/truetype/droid/DroidSansFallbackFull.ttf",
    "/usr/share/fonts/noto-cjk/NotoSansCJK-Regular.ttc",
]

for font_path in font_paths:
    if os.path.exists(font_path):
        try:
            if font_path.endswith('.ttc'):
                pdfmetrics.registerFont(TTFont("CJKFont", font_path, subfontIndex=0))
            else:
                pdfmetrics.registerFont(TTFont("CJKFont", font_path))
            CJK_FONT = "CJKFont"
            font_registered = True
            print(f"Registered CJK font: {font_path}")
            break
        except Exception as e:
            print(f"Failed to register {font_path}: {e}")

# Colors
PRIMARY = HexColor('#1a365d')
ACCENT = HexColor('#2b6cb0')
LIGHT_BG = HexColor('#f7fafc')
GRAY = HexColor('#718096')
DARK = HexColor('#2d3748')
WHITE = HexColor('#ffffff')

# Page setup
PAGE_WIDTH, PAGE_HEIGHT = A4
LEFT_MARGIN = RIGHT_MARGIN = 20 * mm
TOP_MARGIN = BOTTOM_MARGIN = 20 * mm
CONTENT_WIDTH = PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN

# Styles
styles = {
    'title': ParagraphStyle('Title', fontName=CJK_FONT, fontSize=28, leading=36,
                           textColor=PRIMARY, alignment=TA_CENTER, spaceAfter=30),
    'subtitle': ParagraphStyle('Subtitle', fontName=CJK_FONT, fontSize=14, leading=20,
                              textColor=GRAY, alignment=TA_CENTER, spaceAfter=50),
    'h1': ParagraphStyle('H1', fontName=CJK_FONT, fontSize=18, leading=26,
                        textColor=PRIMARY, spaceBefore=24, spaceAfter=12),
    'h2': ParagraphStyle('H2', fontName=CJK_FONT, fontSize=14, leading=20,
                        textColor=ACCENT, spaceBefore=18, spaceAfter=8),
    'h3': ParagraphStyle('H3', fontName=CJK_FONT, fontSize=12, leading=16,
                        textColor=DARK, spaceBefore=12, spaceAfter=6),
    'body': ParagraphStyle('Body', fontName=CJK_FONT, fontSize=10, leading=16,
                          textColor=DARK, spaceAfter=8),
    'code': ParagraphStyle('Code', fontName='Courier', fontSize=8, leading=11,
                         textColor=DARK, spaceBefore=6, spaceAfter=6),
    'caption': ParagraphStyle('Caption', fontName=CJK_FONT, fontSize=9, leading=12,
                              textColor=GRAY, alignment=TA_CENTER, spaceBefore=4, spaceAfter=12),
}

def create_table_style():
    return TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), ACCENT),
        ('TEXTCOLOR', (0, 0), (-1, 0), WHITE),
        ('FONTNAME', (0, 0), (-1, 0), CJK_FONT),
        ('FONTNAME', (0, 1), (-1, -1), CJK_FONT),
        ('FONTSIZE', (0, 0), (-1, 0), 10),
        ('FONTSIZE', (0, 1), (-1, -1), 9),
        ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 6),
        ('TOPPADDING', (0, 0), (-1, -1), 6),
        ('LEFTPADDING', (0, 0), (-1, -1), 8),
        ('BACKGROUND', (0, 1), (-1, -1), WHITE),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [WHITE, LIGHT_BG]),
        ('GRID', (0, 0), (-1, -1), 0.5, HexColor('#e2e8f0')),
    ])

def p(text, style='body'):
    return Paragraph(text, styles[style])

def spacer(h=12):
    return Spacer(1, h)

def code(text):
    # Convert to simple text without pre tags, escape special chars
    return Paragraph(text.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;'), styles['code'])

# Build document
doc = SimpleDocTemplate(
    "/workspace/quickapi/QuickAPI_Manual.pdf",
    pagesize=A4,
    leftMargin=LEFT_MARGIN,
    rightMargin=RIGHT_MARGIN,
    topMargin=TOP_MARGIN,
    bottomMargin=BOTTOM_MARGIN
)

story = []

# ===== Cover Page =====
story.append(spacer(80))
story.append(p("<b>QuickAPI</b>", 'title'))
story.append(p("High-Performance API Framework", 'subtitle'))
story.append(spacer(30))
story.append(p("PHP 7.4+ | Medoo ORM | inhere/sroute", 'subtitle'))
story.append(spacer(40))
story.append(p("User Manual", 'subtitle'))
story.append(PageBreak())

# ===== Table of Contents =====
story.append(p("Contents", 'h1'))
story.append(spacer(10))
toc_items = [
    "1. Introduction",
    "2. Core Features",
    "3. Quick Start",
    "4. Database Operations",
    "5. Cache System",
    "6. Route System",
    "7. Request & Response",
    "8. Middleware",
    "9. Validator",
    "10. Performance Optimization",
    "11. Configuration Reference",
]
for item in toc_items:
    story.append(p(item, 'body'))
story.append(PageBreak())

# ===== 1. Introduction =====
story.append(p("1. Introduction", 'h1'))
story.append(spacer(10))
story.append(p("QuickAPI is a high-performance API framework based on PHP 7.4+, combining the powerful features of Medoo ORM with ThinkPHP5-like chain call syntax, and integrating inhere/sroute for lightning-fast route matching.", 'body'))
story.append(spacer(10))

story.append(p("1.1 Key Features", 'h2'))
story.append(p("* Simple database operations - ThinkPHP5 chain call style", 'body'))
story.append(p("* Connection pool management - Reduce DB connection overhead", 'body'))
story.append(p("* Multiple cache backends - File/Memcache/Redis/OpenResty", 'body'))
story.append(p("* Lightning-fast routing - O(1) time complexity", 'body'))
story.append(p("* Stateless design - Disable PHP Session for maximum performance", 'body'))
story.append(p("* PDO prepared statements - Prevent SQL injection", 'body'))
story.append(PageBreak())

# ===== 2. Core Features =====
story.append(p("2. Core Features", 'h1'))
story.append(spacer(10))

story.append(p("2.1 Chain Database Operations", 'h2'))
story.append(p("Framework provides ThinkPHP5-like chain call syntax:", 'body'))
story.append(code("DB::table('user')->where('status', 1)->select();\nDB::table('user')->where('id', 1)->find();\nDB::table('user')->insert(['name' => 'test']);"))
story.append(spacer(10))

story.append(p("2.2 Performance Optimization", 'h2'))
features_table = [
    ['Feature', 'Description'],
    ['Connection Pool', 'Max 10, Min 2 connections, auto management'],
    ['Query Cache', 'Multiple cache backends support'],
    ['Prepared Statements', 'PDO prepared, prevent SQL injection'],
    ['Query Builder', 'Chain calls, lazy execution'],
    ['Performance Stats', 'Query count, execution time, slow query log'],
]
story.append(Table(features_table, colWidths=[100, CONTENT_WIDTH-100], style=create_table_style()))
story.append(spacer(15))
story.append(PageBreak())

# ===== 3. Quick Start =====
story.append(p("3. Quick Start", 'h1'))
story.append(spacer(10))

story.append(p("3.1 Installation", 'h2'))
story.append(code("composer require quickapi/framework"))
story.append(spacer(10))

story.append(p("3.2 Configure Database", 'h2'))
story.append(code("use QuickAPI\\Database\\DB;\n\nDB::config([\n    'driver' => 'mysql',\n    'host' => '127.0.0.1',\n    'database' => 'quickapi',\n    'username' => 'root',\n    'password' => '',\n]);"))
story.append(spacer(10))

story.append(p("3.3 Define Routes", 'h2'))
story.append(code("use QuickAPI\\Route\\Route;\nuse QuickAPI\\Core\\Response;\n\nRoute::get('/users', function() {\n    return Response::json(DB::table('user')->select());\n});\n\nRoute::get('/user/{id}', function($id) {\n    return Response::json(DB::table('user')->where('id', $id)->find());\n});"))
story.append(spacer(10))

story.append(p("3.4 Run Application", 'h2'))
story.append(code("use QuickAPI\\Core\\App;\n\n$app = new App();\n$app->run();"))
story.append(PageBreak())

# ===== 4. Database =====
story.append(p("4. Database Operations", 'h1'))
story.append(spacer(10))

story.append(p("4.1 Basic Query Methods", 'h2'))
db_queries = [
    ['Method', 'Description'],
    ['select()', 'Query all records'],
    ['find()', 'Query single record'],
    ['count()', 'Count records'],
    ['insert($data)', 'Insert data, return ID'],
    ['update($data)', 'Update data, return affected rows'],
    ['delete()', 'Delete data, return affected rows'],
]
story.append(Table(db_queries, colWidths=[120, CONTENT_WIDTH-120], style=create_table_style()))
story.append(spacer(12))

story.append(p("4.2 Chain Methods", 'h2'))
chain_methods = [
    ['Method', 'Description'],
    ['table($table)', 'Set table name'],
    ['field($fields)', 'Specify query fields'],
    ['where($field, $value)', 'WHERE condition'],
    ['whereOr($field, $value)', 'OR WHERE condition'],
    ['whereIn($field, $values)', 'IN condition'],
    ['join($table, $on, $type)', 'JOIN query'],
    ['order($field, $dir)', 'ORDER BY'],
    ['limit($limit, $offset)', 'LIMIT clause'],
    ['page($page, $perPage)', 'Pagination'],
]
story.append(Table(chain_methods, colWidths=[160, CONTENT_WIDTH-160], style=create_table_style()))
story.append(spacer(12))

story.append(p("4.3 Example Code", 'h2'))
story.append(code("# Query all\nDB::table('users')->select();\n\n# Condition query\nDB::table('users')->where('status', 1)->select();\n\n# Chain calls\nDB::table('users')\n    ->field('id, name, email')\n    ->where('status', 1)\n    ->order('created_at', 'desc')\n    ->limit(10)\n    ->select();\n\n# Pagination\n$result = DB::table('users')->paginate(15, $page);"))
story.append(spacer(10))

story.append(p("4.4 Aggregation", 'h2'))
story.append(code("DB::table('users')->count();       // Count\nDB::table('orders')->sum('amount');   // Sum\nDB::table('users')->avg('score');      // Average\nDB::table('products')->max('price');    // Max\nDB::table('products')->min('price');     // Min"))
story.append(PageBreak())

# ===== 5. Cache =====
story.append(p("5. Cache System", 'h1'))
story.append(spacer(10))

story.append(p("5.1 Supported Cache Drivers", 'h2'))
cache_drivers = [
    ['Driver', 'Config'],
    ['file', 'path'],
    ['redis', 'host, port, password, database'],
    ['memcache', 'host, port'],
    ['openresty', 'host, port'],
]
story.append(Table(cache_drivers, colWidths=[80, CONTENT_WIDTH-80], style=create_table_style()))
story.append(spacer(12))

story.append(p("5.2 Configuration Example", 'h2'))
story.append(code("Cache::config([\n    'driver' => 'redis',\n    'redis' => [\n        'host' => '127.0.0.1',\n        'port' => 6379,\n    ],\n]);"))
story.append(spacer(10))

story.append(p("5.3 Basic Operations", 'h2'))
story.append(code("# Basic operations\nCache::set('key', 'value', 300);\nCache::get('key');\nCache::has('key');\nCache::delete('key');\n\n# Advanced\nCache::remember('key', function() {\n    return DB::table('users')->select();\n}, 300);\n\nCache::increment('counter');\nCache::decrement('counter');\n\n# Tags\nCache::tag('users')->set('user_1', $data);\nCache::tag('users')->flush();\n\n# Lock\nCache::lock('resource', 10);\nCache::unlock('resource');"))
story.append(PageBreak())

# ===== 6. Route =====
story.append(p("6. Route System", 'h1'))
story.append(spacer(10))

story.append(p("6.1 HTTP Method Routes", 'h2'))
story.append(code("Route::get($path, $handler);      // GET\nRoute::post($path, $handler);     // POST\nRoute::put($path, $handler);       // PUT\nRoute::delete($path, $handler);    // DELETE\nRoute::any($path, $handler);       // Any method\nRoute::match(['GET', 'POST'], $path, $handler);"))
story.append(spacer(10))

story.append(p("6.2 Route Groups", 'h2'))
story.append(code("Route::group(['prefix' => '/api/v1'], function() {\n    Route::get('/users', 'UserController@index');\n    Route::get('/users/{id}', 'UserController@show');\n});"))
story.append(spacer(10))

story.append(p("6.3 Middleware", 'h2'))
story.append(code("Route::group(['middleware' => ['Auth', 'Cors']], function() {\n    Route::get('/profile', 'ProfileController@show');\n});"))
story.append(spacer(10))

story.append(p("6.4 Parameter Constraints", 'h2'))
story.append(code("Route::get('/user/{id}', 'Controller@show')\n    ->whereNumber('id');\n\nRoute::get('/post/{slug}', 'Controller@show')\n    ->whereAlpha('slug');"))
story.append(spacer(10))

story.append(p("6.5 Resource Routes", 'h2'))
story.append(code("Route::resource('users', 'UserController');\n\n# Generates:\n# GET    /users          index\n# GET    /users/{id}     show\n# POST   /users          store\n# PUT    /users/{id}     update\n# DELETE /users/{id}     destroy"))
story.append(PageBreak())

# ===== 7. Request & Response =====
story.append(p("7. Request & Response", 'h1'))
story.append(spacer(10))

story.append(p("7.1 Request Methods", 'h2'))
request_methods = [
    ['Method', 'Description'],
    ['method()', 'Get HTTP method'],
    ['uri()', 'Get request path'],
    ['all()', 'Get all parameters'],
    ['query($key)', 'Get GET parameter'],
    ['post($key)', 'Get POST parameter'],
    ['header($key)', 'Get request header'],
    ['json()', 'Get JSON data'],
    ['file($key)', 'Get uploaded file'],
    ['ip()', 'Get user IP'],
]
story.append(Table(request_methods, colWidths=[120, CONTENT_WIDTH-120], style=create_table_style()))
story.append(spacer(12))

story.append(p("7.2 Response Methods", 'h2'))
story.append(code("# JSON response\nResponse::json(['data' => $value]);\n\n# Success response\nResponse::success(['user' => $user]);\n\n# Error response\nResponse::error('Error message', 500);\n\n# Pagination\nResponse::paginate($data, $total, $page, $perPage);\n\n# Other responses\nResponse::text('Hello');\nResponse::html('<h1>Hello</h1>');\nResponse::redirect('/new-url');\nResponse::noContent();"))
story.append(PageBreak())

# ===== 8. Middleware =====
story.append(p("8. Middleware", 'h1'))
story.append(spacer(10))

story.append(p("8.1 Built-in Middleware", 'h2'))
middleware_list = [
    ['Middleware', 'Description'],
    ['CorsMiddleware', 'Cross-Origin Resource Sharing'],
    ['AuthMiddleware', 'User Authentication'],
    ['ThrottleMiddleware', 'Rate Limiting'],
    ['LogMiddleware', 'Request Logging'],
    ['ValidateMiddleware', 'Data Validation'],
]
story.append(Table(middleware_list, colWidths=[150, CONTENT_WIDTH-150], style=create_table_style()))
story.append(spacer(12))

story.append(p("8.2 Usage", 'h2'))
story.append(code("# Global middleware\n$app->middleware(['Cors', 'Log']);\n\n# Route middleware\nRoute::get('/profile', 'ProfileController@show')\n    ->middleware(['Auth']);"))
story.append(PageBreak())

# ===== 9. Validator =====
story.append(p("9. Validator", 'h1'))
story.append(spacer(10))

story.append(p("9.1 Validation Rules", 'h2'))
validator_rules = [
    ['Rule', 'Description'],
    ['required', 'Required field'],
    ['email', 'Email format'],
    ['min:n', 'Minimum length/value'],
    ['max:n', 'Maximum length/value'],
    ['numeric', 'Numeric value'],
    ['integer', 'Integer value'],
    ['string', 'String value'],
    ['array', 'Array value'],
    ['in:val1,val2', 'In list'],
    ['between:min,max', 'Between range'],
    ['url', 'URL format'],
    ['ip', 'IP address'],
    ['confirmed', 'Confirmation match'],
]
story.append(Table(validator_rules, colWidths=[120, CONTENT_WIDTH-120], style=create_table_style()))
story.append(spacer(12))

story.append(p("9.2 Usage Example", 'h2'))
story.append(code("$validator = new Validator($data, [\n    'name' => 'required|string|min:2|max:50',\n    'email' => 'required|email',\n    'password' => 'required|string|min:6',\n    'age' => 'numeric|between:18,100',\n]);\n\nif ($validator->fails()) {\n    return Response::json([\n        'errors' => $validator->errors()\n    ], 422);\n}\n\n$validated = $validator->validated();"))
story.append(PageBreak())

# ===== 10. Performance =====
story.append(p("10. Performance Optimization", 'h1'))
story.append(spacer(10))

story.append(p("10.1 Connection Pool Configuration", 'h2'))
story.append(code("'database' => [\n    'pool' => [\n        'max' => 10,           // Max connections\n        'min' => 2,            // Min connections\n        'idle_timeout' => 60,   // Idle timeout (sec)\n        'connect_timeout' => 5, // Connect timeout\n    ],\n],"))
story.append(spacer(10))

story.append(p("10.2 Query Cache", 'h2'))
story.append(code("# Auto cache query results\nCache::query('user_list', function() {\n    return DB::table('user')\n        ->where('status', 1)\n        ->select();\n}, 300);"))
story.append(spacer(10))

story.append(p("10.3 Performance Statistics", 'h2'))
story.append(code("# Get query statistics\nDB::getQueryStats();\nDB::getQueryCount();\nDB::getTotalQueryTime();\nDB::getSlowQueries();"))
story.append(PageBreak())

# ===== 11. Configuration =====
story.append(p("11. Configuration Reference", 'h1'))
story.append(spacer(10))

story.append(p("11.1 Complete Configuration", 'h2'))
story.append(code("return [\n    'env' => 'development',\n    'debug' => true,\n    'timezone' => 'Asia/Shanghai',\n    \n    'database' => [\n        'driver' => 'mysql',\n        'host' => '127.0.0.1',\n        'port' => 3306,\n        'database' => 'quickapi',\n        'username' => 'root',\n        'password' => '',\n        'charset' => 'utf8mb4',\n        'prefix' => '',\n        'pool' => ['max' => 10, 'min' => 2],\n    ],\n    \n    'cache' => [\n        'driver' => 'redis',\n        'redis' => [\n            'host' => '127.0.0.1',\n            'port' => 6379,\n        ],\n        'default_ttl' => 3600,\n    ],\n    \n    'middleware' => ['Cors', 'Log'],\n];"))
story.append(PageBreak())

# ===== Appendix =====
story.append(p("Appendix", 'h1'))
story.append(spacer(10))

story.append(p("A. Directory Structure", 'h2'))
story.append(code("quickapi/\n├── src/\n│   ├── Core/           # Core classes\n│   ├── Database/       # Database\n│   ├── Cache/          # Cache\n│   ├── Route/           # Route\n│   ├── Middleware/      # Middleware\n│   └── Exception/       # Exception\n├── app/\n│   └── Controller/       # Controllers\n├── config/             # Configuration\n├── routes/             # Route definitions\n├── tests/              # Tests\n└── public/             # Entry point"))
story.append(spacer(20))

story.append(p("B. License", 'h2'))
story.append(p("MIT License - Free to use, modify and distribute.", 'body'))
story.append(spacer(30))

story.append(p("Copyright 2024 QuickAPI Framework. All rights reserved.", 'caption'))

# Build PDF
doc.build(story)
print("PDF created successfully: /workspace/quickapi/QuickAPI_Manual.pdf")
