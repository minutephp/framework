<?php

namespace Test\Cache {

    use Auryn\Injector;
    use Minute\Cache\QCache;
    use Nette\Caching\Storages\MemcachedStorage;

    class QCacheTest extends \PHPUnit_Framework_TestCase {

        public function testMemoryCacheTest() {
            $key   = 'mykey';
            $value = ['a' => 'b'];

            foreach (['memory', 'file'] as $type) {
                if (MemcachedStorage::isAvailable()) {
                    putenv('PHP_MEMCACHE_SERVER=localhost');
                }

                /** @var QCache $qCache */
                $injector = new Injector();
                $qCache   = $injector->make('Minute\Cache\QCache', [$type]);

                $qCache->flush();

                $none = $qCache->get($key);
                $this->assertNull($none, 'Cache is empty');

                $result = $qCache->set($key, $value);
                $this->assertEquals($result, $value, 'Cache is set');

                $result = $qCache->get($key);
                $this->assertEquals($result, $value, 'Cache is retrieved');

                $qCache->remove($key);
                $none = $qCache->get($key);
                $this->assertNull($none, 'Cache key is deleted');
            }
        }
    }
}