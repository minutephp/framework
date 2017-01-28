<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 8/23/2016
 * Time: 2:24 AM
 */
namespace Minute\Event {

    use Minute\Routing\RouteEx;

    class RouterEvent extends Event {
        const ROUTER_GET_FALLBACK_RESOURCE = "router.get.fallback.resource";
        /**
         * @var string
         */
        private $method;
        /**
         * @var string
         */
        private $path;
        /**
         * @var RouteEx
         */
        private $route;

        /**
         * RouterEvent constructor.
         *
         * @param string $method
         * @param string $path
         */
        public function __construct(string $method, string $path) {
            $this->method = $method;
            $this->path   = $path;
        }

        /**
         * @return RouteEx
         */
        public function getRoute() {
            return $this->route;
        }

        /**
         * @param RouteEx $route
         *
         * @return RouterEvent
         */
        public function setRoute(RouteEx $route) {
            $this->route = $route;

            return $this;
        }

        /**
         * @return string
         */
        public function getMethod(): string {
            return $this->method;
        }

        /**
         * @return string
         */
        public function getPath(): string {
            return $this->path;
        }
    }
}