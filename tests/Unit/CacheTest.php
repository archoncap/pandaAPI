<?php
/**
 * 缓存测试
 */

namespace QuickAPI\Test;

use PHPUnit\Framework\TestCase;
use QuickAPI\Cache\Cache;
use QuickAPI\Cache\Drivers\FileCache;

class CacheTest extends TestCase
{
    protected FileCache $cache;

    protected function setUp(): void
    {
        $this->cache = new FileCache(['path' => sys_get_temp_dir() . '/quickapi_test_cache']);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
    }

    public function testSetAndGet(): void
    {
        $this->cache->set('key1', 'value1');
        $value = $this->cache->get('key1');
        
        $this->assertEquals('value1', $value);
    }

    public function testGetDefaultValue(): void
    {
        $value = $this->cache->get('nonexistent', 'default');
        
        $this->assertEquals('default', $value);
    }

    public function testHas(): void
    {
        $this->cache->set('key1', 'value1');
        
        $this->assertTrue($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('nonexistent'));
    }

    public function testDelete(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->delete('key1');
        
        $this->assertFalse($this->cache->has('key1'));
    }

    public function testClear(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->clear();
        
        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
    }

    public function testExpire(): void
    {
        $this->cache->set('key1', 'value1', 1);
        
        $this->assertTrue($this->cache->has('key1'));
        
        // 等待过期
        sleep(2);
        
        $this->assertFalse($this->cache->has('key1'));
    }

    public function testPull(): void
    {
        $this->cache->set('key1', 'value1');
        
        $value = $this->cache->pull('key1');
        
        $this->assertEquals('value1', $value);
        $this->assertFalse($this->cache->has('key1'));
    }

    public function testIncrement(): void
    {
        $this->cache->set('counter', 10);
        
        $result = $this->cache->increment('counter', 5);
        
        $this->assertEquals(15, $result);
        $this->assertEquals(15, $this->cache->get('counter'));
    }

    public function testDecrement(): void
    {
        $this->cache->set('counter', 10);
        
        $result = $this->cache->decrement('counter', 3);
        
        $this->assertEquals(7, $result);
        $this->assertEquals(7, $this->cache->get('counter'));
    }

    public function testMultipleOperations(): void
    {
        // 批量设置
        $this->cache->setMultiple([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ], 300);
        
        // 批量获取
        $values = $this->cache->getMultiple(['key1', 'key2', 'key3'], 'default');
        
        $this->assertEquals('value1', $values['key1']);
        $this->assertEquals('value2', $values['key2']);
        $this->assertEquals('value3', $values['key3']);
    }

    public function testLock(): void
    {
        $this->assertTrue($this->cache->lock('resource1'));
        $this->assertFalse($this->cache->lock('resource1')); // 已存在锁
        
        $this->cache->unlock('resource1');
        $this->assertTrue($this->cache->lock('resource1')); // 可以再次获取
    }

    public function testArrayValue(): void
    {
        $data = ['name' => 'test', 'value' => 123];
        
        $this->cache->set('array_key', $data);
        $result = $this->cache->get('array_key');
        
        $this->assertEquals($data, $result);
    }

    public function testObjectValue(): void
    {
        $data = (object)['name' => 'test', 'value' => 123];
        
        $this->cache->set('object_key', $data);
        $result = $this->cache->get('object_key');
        
        $this->assertEquals($data, $result);
    }
}
