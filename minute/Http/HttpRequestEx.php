<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/18/2016
 * Time: 12:57 PM
 */
namespace Minute\Http {

    use Http\HttpRequest;

    class HttpRequestEx extends HttpRequest {
        /**
         * @var string
         */
        static $rawInput;
        /**
         * @var bool
         */
        protected $ajax;

        public function __construct(array $get = null, array $post = null, array $cookies = null, array $files = null, array $server = null) {
            $get     = $get ?? $_GET;
            $post    = $post ?? $_POST;
            $cookies = $cookies ?? $_COOKIE;
            $files   = $files ?? $_FILES;
            $server  = $server ?? $_SERVER;

            if (preg_match('~^application/json~i', $server['HTTP_CONTENT_TYPE'] ?? $server['CONTENT_TYPE'] ?? null)) { //for AngularJS handling
                $post = array_merge($post ?? [], (array) json_decode(trim($this->getRawInput()), true));;
                $this->ajax = true;
            }

            parent::__construct($get, $post, $cookies, $files, $server);
        }

        /**
         * @return array
         */
        public function getGetParameters(): array {
            return $this->getParameters;
        }

        /**
         * @return array
         */
        public function getPostParameters(): array {
            return $this->postParameters;
        }

        /**
         * @return array
         */
        public function getServer(): array {
            return $this->server;
        }

        /**
         * @return array
         */
        public function getFiles(): array {
            return $this->files;
        }

        /**
         * @return array
         */
        public function getCookies(): array {
            return $this->cookies;
        }

        /**
         * @param array $getParameters
         */
        public function setGetParameters(array $getParameters) {
            $this->getParameters = $getParameters;
        }

        /**
         * @param array $postParameters
         */
        public function setPostParameters(array $postParameters) {
            $this->postParameters = $postParameters;
        }

        /**
         * @param array $server
         */
        public function setServer(array $server) {
            $this->server = $server;
        }

        /**
         * @param array $files
         */
        public function setFiles(array $files) {
            $this->files = $files;
        }

        /**
         * @param array $cookies
         */
        public function setCookies(array $cookies) {
            $this->cookies = $cookies;
        }

        public function isAjaxRequest() {
            return $this->ajax || (preg_match('~^application/json~i', $this->server["HTTP_ACCEPT"] ?? '') && true);
        }

        public function getRawInput() {
            return static::$rawInput = static::$rawInput ?? (file_get_contents('php://input') ?? '');
        }
    }
}