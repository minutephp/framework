<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 11/30/2016
 * Time: 8:09 AM
 */
namespace Minute\Utils {

    use Minute\Config\Config;

    class HttpUtils {
        /**
         * @var Config
         */
        private $config;

        /**
         * HttpUtils constructor.
         *
         * @param Config $config
         */
        public function __construct(Config $config) {
            $this->config = $config;
        }

        public function prefixHostName(string $path) {
            return preg_match('/^http/i', $path) ? $path : sprintf("%s/%s", $this->config->getPublicVars('host'), ltrim($path, '/'));
        }
    }
}