<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 10/12/2016
 * Time: 1:58 AM
 */
namespace Minute\Crypto {

    use Minute\Config\Config;

    //IMPORTANT: This library does not output cryptographically secure values and may not be used for cryptographic purposes (please use JWT for that).
    class Blowfish {
        /**
         * @var Config
         */
        private $config;

        /**
         * Blowfish constructor.
         *
         * @param Config $config
         */
        public function __construct(Config $config) {
            $this->config = $config;
        }

        public function encrypt(string $string) {
            return $this->encrypt_decrypt('encrypt', $string);
        }

        public function decrypt(string $string) {
            return $this->encrypt_decrypt('decrypt', $string);
        }

        protected function encrypt_decrypt($action, $string) {
            $output = false;

            $encrypt_method = "BF";
            $secret_key     = $this->config->get(JwtEx::JWT_KEY, 'sanchit123456');

            $key = hash('sha256', $secret_key);
            $iv  = substr(sha1($secret_key), 0, 8);

            if ($action == 'encrypt') {
                $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
                $output = base64_encode($output);
            } else if ($action == 'decrypt') {
                $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
            }

            return $output;
        }
    }
}