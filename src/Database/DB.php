<?php
declare(strict_types=1);

namespace PandaAPI\Database;

/**
 * DB - 数据库入口类（兼容层）
 * 
 * 此类作为 PandaDB 的别名/兼容层，方便从旧版本迁移
 * 所有方法都委托给 PandaDB 处理
 * 
 * @package PandaAPI\Database
 * @deprecated 请直接使用 PandaDB
 */
class DB extends PandaDB
{
    // 继承 PandaDB 的所有功能
    // 此类仅作为向后兼容的别名存在
}
