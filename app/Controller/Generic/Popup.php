<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 7/6/2016
 * Time: 2:16 PM
 */
namespace App\Controller\Generic {

    use Illuminate\Support\Str;
    use Minute\Routing\RouteEx;
    use Minute\View\View;

    class Popup {
        public function index(RouteEx $_route) {
            $viewPath = Str::camel(str_replace(' ', '', ucwords(preg_replace('/(\W+)/', '\\1 ', $_route->getPath()))));

            return new View($viewPath, [], false);
        }
    }
}