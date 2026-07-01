<?php
/**
 * 错误页面模板
 * 
 * @var int $code 错误码
 * @var string $message 错误信息
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>错误 <?php echo $code; ?> - 熊猫API框架</title>
    <style>
        body { font-family: -apple-system, "Microsoft YaHei", sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f7fafc; color: #2d3748; }
        .error-box { text-align: center; padding: 40px; }
        .error-code { font-size: 72px; font-weight: bold; color: #e53e3e; }
        .error-msg { font-size: 18px; color: #718096; margin-top: 10px; }
        .error-footer { margin-top: 20px; font-size: 12px; color: #a0aec0; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-code"><?php echo htmlspecialchars($code); ?></div>
        <div class="error-msg"><?php echo htmlspecialchars($message); ?></div>
        <div class="error-footer">PandaAPI Framework v1.0</div>
    </div>
</body>
</html>
