<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/20/2016
 * Time: 11:02 AM
 */
namespace Minute\View {

    class Redirection {
        /**
         * @var string
         */
        private $url;
        /**
         * @var array
         */
        private $params;
        /**
         * @var bool
         */
        private $exit;

        /**
         * Redirection constructor.
         *
         * @param string $url
         * @param array $params
         * @param bool $exit
         */
        public function __construct(string $url, array $params = [], $exit = true) {
            $this->url    = $url;
            $this->params = $params;
            $this->exit   = $exit;
        }

        /**
         * @return boolean
         * @codeCoverageIgnore
         */
        public function isExit() {
            return $this->exit;
        }

        /**
         * @param boolean $exit
         *
         * @return Redirection
         * @codeCoverageIgnore
         */
        public function setExit($exit) {
            $this->exit = $exit;

            return $this;
        }

        /**
         * @return string
         * @codeCoverageIgnore
         */
        public function getUrl() {
            return $this->url;
        }

        /**
         * @param string $url
         *
         * @return Redirection
         * @codeCoverageIgnore
         */
        public function setUrl($url) {
            $this->url = $url;

            return $this;
        }

        /**
         * @return array
         * @codeCoverageIgnore
         */
        public function getParams() {
            return $this->params;
        }

        /**
         * @param array $params
         *
         * @return Redirection
         * @codeCoverageIgnore
         */
        public function setParams($params) {
            $this->params = $params;

            return $this;
        }

        /**
         * Create the redirect URL with params
         *
         * @return string
         */
        public function getRedirectUrl() {
            $url = $this->url;

            return !empty ($this->params) ? sprintf("%s%s%s", $url, strpos($url, '?') ? '&' : '?', http_build_query($this->params)) : $url;
        }

        /**
         * Redirect to some url
         *
         * @param bool $permanent
         */
        public function redirect(bool $permanent = false) {
            $url = $this->getRedirectUrl();

            if (!headers_sent()) {
                header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
                header("Cache-Control: no-cache, no-store, must-revalidate");
                header("Pragma: no-cache");

                header(sprintf("Location: %s", $url), true, $permanent ? 301 : 302);
            }

            printf('<script>location = "%s";</script>', $url);

            if (!empty($this->exit)) {
                exit;
            }
        }
    }
}