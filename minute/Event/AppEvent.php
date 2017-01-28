<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 8/6/2016
 * Time: 1:48 PM
 */
namespace Minute\Event {

    use Minute\App\App;

    class AppEvent extends Event {
        const APP_INIT = "app.init";
        /**
         * @var App
         */
        private $app;

        /**
         * AppEvent constructor.
         *
         * @param App $app
         */
        public function __construct(App $app) {
            $this->app = $app;
        }

        /**
         * @return App
         */
        public function getApp(): App {
            return $this->app;
        }
    }
}