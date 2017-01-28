<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/17/2016
 * Time: 6:03 PM
 */
namespace Minute\Resolver {

    use App\Config\BootLoader;
    use Composer\Autoload\ClassLoader;
    use Illuminate\Support\Str;
    use Minute\Model\ModelEx;
    use Minute\Utils\PathUtils;

    class Resolver {
        /**
         * @var ClassLoader
         */
        protected $loader;
        /**
         * @var PathUtils
         */
        private $utils;

        /**
         * Resolver constructor.
         *
         */
        public function __construct() {
            $this->loader = @include((new BootLoader())->getBaseDir() . '/vendor/autoload.php');
            $this->utils  = new PathUtils();
        }

        /**
         * @param $name
         * @param bool $usePrefix
         *
         * @return bool|ModelEx
         */
        public function getModel($name, bool $usePrefix = false) {
            try {
                $prefix = $usePrefix ? (defined('MODEL_PREFIX') ? MODEL_PREFIX : 'M') : '';
                $class  = $this->normalize($prefix . ucfirst(Str::camel(Str::singular("$name"))), defined('MODEL_DIR') ? MODEL_DIR : 'App\Model');

                return class_exists($class) ? $class : false;
            } catch (\Throwable $e) {
            }

            return false;
        }

        public function getController($path) {
            if (is_string($path)) {
                $class = $this->normalize($path, defined('CONTROLLER_DIR') ? CONTROLLER_DIR : 'App\Controller');

                return @class_exists($class) ? $class : false;
            } else {
                return $path;
            }
        }

        public function getView($path) {
            $class = $this->loader->findFile($this->normalize($path, defined('VIEW_DIR') ? VIEW_DIR : 'App\View'));

            return file_exists($class) ? realpath($class) : false;
        }

        public function getListeners() {
            $glob      = $this->utils->unixPath(sprintf('%s/listeners.php', defined('LISTENER_DIR') ? ROUTE_DIR : 'App\Listener'));
            $listeners = $this->find($glob);

            return $listeners ?? [];
        }

        public function getRoutes() {
            $glob   = $this->utils->unixPath(sprintf('%s/routes.php', defined('ROUTE_DIR') ? ROUTE_DIR : 'App\Route'));
            $routes = $this->find($glob);

            return $routes;
        }

        /**
         * Returns all matching files by namespace
         *
         * Example: find all route.php files in Routes folders,
         * $loader->find('App\Route\routes.php')
         *
         * Example: find all Cron directories
         * $loader->find('App\Controller\Cron')
         *
         * @return array
         */
        public function find($glob): array {
            @list($prefix, $match) = explode('/', $this->utils->unixPath(trim($glob, '\\/')), 2);

            foreach ($folders = $this->loader->getPrefixesPsr4()["$prefix\\"] ?? [] as $folder) {
                if (is_readable($path = sprintf('%s/%s', $folder, $match))) {
                    $matches[] = realpath($path);
                }
            }

            return $matches ?? [];
        }

        protected function normalize(string $path, string $suffix = '') {
            $info  = $this->utils->pathinfo($path);
            $class = $this->utils->dosPath(sprintf('%s%s%s', $suffix ? trim($suffix, '\\') . '\\' : '', $info['dirname'] !== '.' ? trim($info['dirname'], '\\') . '\\' : '', $info['filename']));

            return ltrim($class, "\\");
        }
    }
}