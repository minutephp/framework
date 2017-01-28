<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/17/2016
 * Time: 1:08 PM
 */
namespace Minute\Cache {

    use App\Config\BootLoader;
    use Closure;
    use Nette\Caching\Cache;
    use Nette\Caching\Storages\FileStorage;
    use Nette\Caching\Storages\MemcachedStorage;
    use Nette\Caching\Storages\MemoryStorage;

    class QCache extends Cache {
        /**
         * @var BootLoader
         */
        private $bootLoader;

        /**
         * QCache constructor.
         *
         * @param string $type
         * @param string $namespace
         * @param BootLoader $bootLoader
         */
        public function __construct(string $type = 'memory', string $namespace = '', BootLoader $bootLoader) {
            $this->bootLoader = $bootLoader;

            if ($type == 'file') {
                $storage = new FileStorage($this->getCacheDir());
            } elseif ($type == 'ram') {
                $storage = new MemoryStorage();
            } else {
                $storage = (($server = $this->getMemCachedServer()) ? new MemcachedStorage($server['host'], $server['port'], $server['prefix']) : new MemoryStorage());
            }

            parent::__construct($storage, $namespace);
        }

        public function getType(): string {
            $storage = $this->getStorage();

            return $storage instanceof FileStorage ? 'file' : ($storage instanceof MemcachedStorage ? 'memcached' : 'ram');
        }

        /**
         * Gets a value from cache
         *
         * @param string $key
         * @param Closure|mixed $default
         * @param int $ttl
         *
         * @return mixed
         */
        public function get(string $key, $default = false, $ttl = null) {
            $value = $this->load($key);

            if (($value === NULL) && !(empty($default))) {
                return $this->set($key, $default, $ttl);
            }

            return $value;
        }

        /**
         * Sets a value in to cache
         *
         * @param string $key
         * @@param Closure|mixed $value
         * @param string|int $ttl
         *
         * @return mixed
         */
        public function set(string $key, $value, $ttl = null) {
            return $this->save($key, $value instanceof Closure ? $value() : $value, [Cache::EXPIRE => $ttl ?? 86400]);
        }

        /**
         * Clears the cache completely
         *
         * @return QCache
         */
        public function flush() {
            $this->clean([Cache::ALL => TRUE]);

            return $this;
        }

        protected function getCacheDir() {
            $cacheDir = sprintf('%s/%s', $this->bootLoader->getBaseDir(), 'tmp/cache');

            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }

            return realpath($cacheDir);
        }

        protected function getMemCachedServer() {
            if ($var = getenv('PHP_MEMCACHE_SERVER')) {
                if ($parse = parse_url($var)) {
                    return ['host' => $parse['host'] ?? 'localhost', 'port' => $parse['port'] ?? 11211, 'prefix' => ltrim($parse['path'] ?? 'minute', '/')];
                }
            }

            return false;
        }
    }
}