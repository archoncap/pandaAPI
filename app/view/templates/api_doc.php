<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>熊猫API框架 - API文档</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, "Microsoft YaHei", sans-serif; background: #f5f5f5; color: #333; }
        .header { background: #1a365d; color: #fff; padding: 20px 40px; }
        .header h1 { font-size: 24px; }
        .header p { color: #a0aec0; margin-top: 5px; }
        .container { max-width: 1000px; margin: 20px auto; padding: 0 20px; }
        .section { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .section h2 { font-size: 18px; color: #1a365d; margin-bottom: 12px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; }
        .route { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .route:last-child { border-bottom: none; }
        .method { font-size: 12px; font-weight: bold; padding: 4px 8px; border-radius: 4px; color: #fff; min-width: 60px; text-align: center; }
        .method.get { background: #48bb78; }
        .method.post { background: #4299e1; }
        .method.put { background: #ed8936; }
        .method.delete { background: #fc8181; }
        .path { font-family: monospace; font-size: 14px; color: #2d3748; margin-left: 12px; flex: 1; }
        .action { font-size: 12px; color: #718096; }
        .desc { font-size: 13px; color: #4a5568; margin-top: 2px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🐼 熊猫API框架 (PandaAPI)</h1>
        <p>API 接口文档 v1.0</p>
    </div>
    <div class="container">
        <div class="section">
            <h2>认证接口</h2>
            <div class="route">
                <span class="method post">POST</span>
                <span class="path">/api/v1/auth/login</span>
                <span class="action">AuthController@login</span>
            </div>
            <div class="route">
                <span class="method post">POST</span>
                <span class="path">/api/v1/auth/register</span>
                <span class="action">AuthController@register</span>
            </div>
            <div class="route">
                <span class="method get">GET</span>
                <span class="path">/api/v1/auth/me</span>
                <span class="action">AuthController@me</span>
            </div>
            <div class="route">
                <span class="method post">POST</span>
                <span class="path">/api/v1/auth/logout</span>
                <span class="action">AuthController@logout</span>
            </div>
        </div>
        <div class="section">
            <h2>用户接口</h2>
            <div class="route">
                <span class="method get">GET</span>
                <span class="path">/api/v1/users</span>
                <span class="action">UserController@index</span>
            </div>
            <div class="route">
                <span class="method get">GET</span>
                <span class="path">/api/v1/users/{id}</span>
                <span class="action">UserController@show</span>
            </div>
            <div class="route">
                <span class="method post">POST</span>
                <span class="path">/api/v1/users</span>
                <span class="action">UserController@store</span>
            </div>
            <div class="route">
                <span class="method put">PUT</span>
                <span class="path">/api/v1/users/{id}</span>
                <span class="action">UserController@update</span>
            </div>
            <div class="route">
                <span class="method delete">DELETE</span>
                <span class="path">/api/v1/users/{id}</span>
                <span class="action">UserController@destroy</span>
            </div>
        </div>
        <div class="section">
            <h2>文章接口</h2>
            <div class="route">
                <span class="method get">GET</span>
                <span class="path">/api/v1/posts</span>
                <span class="action">PostController@index</span>
            </div>
            <div class="route">
                <span class="method get">GET</span>
                <span class="path">/api/v1/posts/hot</span>
                <span class="action">PostController@hot</span>
            </div>
            <div class="route">
                <span class="method get">GET</span>
                <span class="path">/api/v1/posts/stats</span>
                <span class="action">PostController@stats</span>
            </div>
            <div class="route">
                <span class="method get">GET</span>
                <span class="path">/api/v1/posts/{id}</span>
                <span class="action">PostController@show</span>
            </div>
            <div class="route">
                <span class="method post">POST</span>
                <span class="path">/api/v1/posts</span>
                <span class="action">PostController@store</span>
            </div>
            <div class="route">
                <span class="method put">PUT</span>
                <span class="path">/api/v1/posts/{id}</span>
                <span class="action">PostController@update</span>
            </div>
            <div class="route">
                <span class="method delete">DELETE</span>
                <span class="path">/api/v1/posts/{id}</span>
                <span class="action">PostController@destroy</span>
            </div>
        </div>
        <div class="section">
            <h2>私有接口（需认证）</h2>
            <div class="route">
                <span class="method get">GET</span>
                <span class="path">/api/v1/private/profile</span>
                <span class="action">ProfileController@show</span>
            </div>
            <div class="route">
                <span class="method put">PUT</span>
                <span class="path">/api/v1/private/profile</span>
                <span class="action">ProfileController@update</span>
            </div>
            <div class="route">
                <span class="method get">GET</span>
                <span class="path">/api/v1/private/debug</span>
                <span class="action">DebugController@info</span>
            </div>
        </div>
    </div>
</body>
</html>
