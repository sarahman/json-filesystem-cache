<?php

namespace Tests;

use Sarahman\SimpleCache\JSONFileSystemCache;

class JsonFilesystemCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    public function it_checks_data_existence_in_cache()
    {
        $cache = new JSONFileSystemCache();
        $cache->clear();

        $this->assertFalse($cache->has('your_custom_key'));

        $cache->set('your_custom_key', 'sample data');
        $this->assertTrue($cache->has('your_custom_key'));

        // Set Cache key.
        $cache->delete('your_custom_key');
        $this->assertFalse($cache->has('your_custom_key'));
    }

    /**
     * @test
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    public function it_checks_data_stored_in_temporary_files_directory()
    {
        $cache = new JSONFileSystemCache();

        $this->assertInstanceOf('Sarahman\SimpleCache\JSONFileSystemCache', $cache);

        $cache->clear();

        $this->assertFalse($cache->has('your_custom_key'));

        // Set Cache key.
        $cache->set('your_custom_key', [
            'sample' => 'data',
            'another' => 'data'
        ]);

        $this->assertTrue($cache->has('your_custom_key'));

        // Get Cached key data.
        $this->assertArrayHasKey('sample', $cache->get('your_custom_key'));
        $this->assertArrayHasKey('another', $cache->get('your_custom_key'));
    }

    /**
     * @test
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    public function it_checks_data_stored_in_the_given_directory()
    {
        $cache = new JSONFileSystemCache('cache.json', 'tmp');

        $this->assertInstanceOf('Sarahman\SimpleCache\JSONFileSystemCache', $cache);

        $cache->clear();

        $this->assertFalse($cache->has('your_custom_key'));

        // Set Cache key.
        $cache->set('your_custom_key', [
            'sample' => 'data',
            'another' => 'data'
        ]);

        $this->assertTrue($cache->has('your_custom_key'));

        // Get Cached key data.
        $this->assertArrayHasKey('sample', $cache->get('your_custom_key'));
        $this->assertArrayHasKey('another', $cache->get('your_custom_key'));
    }

    /**
     * @test
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    public function it_checks_data_update_in_cache()
    {
        $cache = new JSONFileSystemCache();

        // Set Cache key.
        $cache->set('your_custom_key', [
            'sample' => 'data',
            'another' => 'data'
        ]);

        $this->assertTrue($cache->has('your_custom_key'));

        // Get Cached key data.
        $this->assertArrayHasKey('sample', $cache->get('your_custom_key'));
        $this->assertArrayHasKey('another', $cache->get('your_custom_key'));

        // Update Cache key data.
        $cache->set('your_custom_key', 'new data');
        $this->assertTrue($cache->has('your_custom_key'));
        $this->assertEquals('new data', $cache->get('your_custom_key'));
    }
}
