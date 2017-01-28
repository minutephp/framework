<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 7/6/2016
 * Time: 2:16 PM
 */
namespace App\Controller\Generic {

    use Illuminate\Support\Str;
    use Minute\Routing\RouteEx;
    use Minute\Utils\PathUtils;
    use Minute\View\View;

    class Page {
        const MAX_DEPTH = 2;
        /**
         * @var PathUtils
         */
        private $utils;

        /**
         * Page constructor.
         *
         * @param PathUtils $utils
         */
        public function __construct(PathUtils $utils) {
            $this->utils = $utils;
        }

        public function index(RouteEx $_route) {
            $viewPath = $this->getViewPath($_route->getPath());

            return new View($viewPath);
        }

        public function getViewPath($path) {
            $path = $this->utils->unixPath($path);
            $path = preg_replace('/\{.*?\}/', '', $path);
            $path = trim($path, '/');

            $parts      = explode('/', $path);
            $depth      = min(count($parts) - 1, self::MAX_DEPTH);
            $capitalize = function ($f) { return ucwords(Str::camel($f)); };
            $dirname    = $depth > 0 ? join('/', array_map($capitalize, array_slice($parts, 0, $depth))) : null;
            $filename   = join('', array_map($capitalize, array_slice($parts, $depth)));

            //throw new AwsError("hohohoo");

            return !empty($dirname) ? sprintf('%s/%s', $dirname, $filename) : $filename;
        }
    }
}