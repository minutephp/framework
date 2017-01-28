<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 10/13/2016
 * Time: 4:56 AM
 */
namespace Minute\Utils {

    class Sniffer {
        public function getUserIP() {
            if (getenv('HTTP_CLIENT_IP')) {
                $IP = getenv('HTTP_CLIENT_IP');
            } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
                $IP = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_X_FORWARDED')) {
                $IP = getenv('HTTP_X_FORWARDED');
            } elseif (getenv('HTTP_FORWARDED_FOR')) {
                $IP = getenv('HTTP_FORWARDED_FOR');
            } elseif (getenv('HTTP_FORWARDED')) {
                $IP = getenv('HTTP_FORWARDED');
            } else {
                $IP = @$_SERVER['REMOTE_ADDR'];
            }

            return $IP ?: '127.0.0.1';
        }
    }
}
