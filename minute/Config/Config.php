<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/21/2016
 * Time: 8:08 AM
 */
namespace Minute\Config {

    use Minute\Cache\QCache;
    use Minute\Database\Database;
    use Minute\Debug\Debugger;
    use Minute\Resolver\Resolver;

    class Config {
        /**
         * @var Resolver
         */
        private $resolver;
        /**
         * @var QCache
         */
        private $cache;
        /**
         * @var Database
         */
        private $database;
        /**
         * @var Debugger
         */
        private $debugger;

        /**
         * Config constructor.
         *
         * @param Resolver $resolver
         * @param QCache $cache
         * @param Database $database
         * @param Debugger $debugger
         */
        public function __construct(Resolver $resolver, QCache $cache, Database $database, Debugger $debugger) {
            $this->resolver = $resolver;
            $this->cache    = $cache;
            $this->database = $database;
            $this->debugger = $debugger;
        }

        /**
         * Get site configuration using keys like /public/host, /public/domain,
         * /private/api_keys/google/token, etc
         *
         * @param string $key
         * @param mixed $default
         * @param bool $cached
         *
         * @return bool|mixed|null
         */
        public function get(string $key, $default = false, $cached = true) {
            @list($type, $path) = explode('/', ltrim($key, '/'), 2);

            $get = function () use ($type, $default) {
                if ($model = $this->getConfigModel()) {
                    if ($data = $model::where('type', $type)->first()) {
                        return json_decode($data->data_json, true) ?: false;
                    }
                }

                return false;
            };

            $data = !$cached ? $get() : $this->cache->get("config-$type", $get, 300);

            return (empty($path) ? $data : $this->find($data, $path)) ?: $default;
        }

        /**
         * Save site configuration using keys like /public/host, /private/api_keys/google/token, etc
         *
         * @param string $key
         * @param $value
         * @param bool $create - create this key if it does not exists when true, fails if false and key isn't already present
         *
         * @return bool
         */
        public function set(string $key, $value, bool $create = false) {
            list($type, $path) = explode('/', ltrim($key, '/'), 2);

            if ($create || ($this->get($key) !== false)) {
                $data = $this->get($type, []);

                $this->update($data, $path, $value);

                if ($model = $this->getConfigModel()) {
                    $model::unguard();

                    if ($model::updateOrCreate(['type' => $type], ['data_json' => json_encode($data)])) {
                        $this->cache->remove("config-$type");

                        return true;
                    }
                }
            }

            return false;
        }

        /**
         * Shortcut method to retrieve "/public" settings (all data or by $name)
         *
         * @param null $name    - return /public/$name only (returns the whole array otherwise)
         * @param null $default - default value to return if none is found
         *
         * @return string
         */
        public function getPublicVars($name = null, $default = null) {
            $public = array_merge(['domain' => 'localhost', 'site_name' => 'MinutePHP'], $this->get('public', []));
            $data   = array_merge(['host' => sprintf('http%s://www.%s', $public['https_only'] ?? false === true ? 's' : '', $public['domain'])], $public);

            if ($this->debugger->enabled()) {
                $data['host'] = sprintf("%s://%s", $_SERVER['REQUEST_SCHEME'] ?? 'http', $_SERVER['HTTP_HOST']);
            }

            return !empty($name) ? $data[$name] ?? $default : $data;
        }

        /**
         * Look for value in json_data using path
         *
         * @param $data
         * @param $path
         *
         * @return mixed|bool
         */
        protected function find($data, $path) {
            if (is_array($data)) {
                $parts = explode('/', $path);

                foreach ($parts as $part) {
                    if (isset($data[$part])) {
                        $data = $data[$part];
                    } else {
                        return false;
                    }
                }

                return $data;
            }

            return false;
        }

        /**
         * Update Json data using path as key
         *
         * @param $data
         * @param $path
         * @param $value
         */
        protected function update(&$data, $path, $value) {
            $parts   = explode('/', $path);
            $current = &$data;

            foreach ($parts as $part) {
                $current = &$current[$part];
            }

            $current = $value;
        }

        /**
         * @return \Minute\Model\ModelEx
         */
        protected function getConfigModel() {
            return $this->database->isConnected() ? $this->resolver->getModel('Config', true) : null;
        }
    }
}