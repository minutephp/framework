<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 6:18 PM
 */
namespace Minute\Event {

    use Minute\Http\HttpResponseEx;

    class ControllerEvent extends Event {
        const CONTROLLER_EXECUTE = "controller.execute";
        /**
         * @var
         */
        private $controller;
        /**
         * @var array
         */
        private $params;
        /**
         * @var HttpResponseEx
         */
        private $response;

        /**
         * ControllerEvent constructor.
         *
         * @param $controller
         * @param array $params
         */
        public function __construct($controller, array $params = []) {
            $this->controller = $controller;
            $this->params     = $params;
        }

        /**
         * @return HttpResponseEx
         */
        public function getResponse(): HttpResponseEx {
            return $this->response;
        }

        /**
         * @param HttpResponseEx $response
         *
         * @return ControllerEvent
         */
        public function setResponse(HttpResponseEx $response): ControllerEvent {
            $this->response = $response;

            return $this;
        }

        /**
         * @return mixed
         */
        public function getController() {
            return $this->controller;
        }

        /**
         * @param bool $raw - Auryn needs keys to prepended with a colon (treats values as Class otherwise)
         *
         * @return array
         */
        public function getParams(bool $raw = false) {
            if ($raw) {
                return $this->params;
            } else {
                foreach ($this->params as $key => $value) {
                    $results[strpos($key, ':') !== 0 ? ":$key" : $key] = $value;
                }

                return $results ?? [];
            }
        }

        public function setParam($name, $value) {
            $this->params[$name] = $value;
        }
    }
}