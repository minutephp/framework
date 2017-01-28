<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/21/2016
 * Time: 7:52 AM
 */
namespace Minute\Crypto {

    use Firebase\JWT\JWT;
    use Minute\Config\Config;
    use stdClass;

    class JwtEx {
        const JWT_KEY = 'private/site/jwt_key';
        /**
         * @var JWT
         */
        private $jwt;
        /**
         * @var string
         */
        private $key;
        /**
         * @var Config
         */
        private $config;

        /**
         * JwtEx constructor.
         *
         * @param JWT $jwt
         * @param Config $config
         *
         * @internal param string $key
         */
        public function __construct(JWT $jwt, Config $config) {
            $this->config = $config;
            $this->jwt    = $jwt;
            $this->key    = $this->config->get(self::JWT_KEY, 'sanchit123456');
        }

        public function decode($token) {
            try {
                $result = JWT::decode($token, $this->key, array('HS256'));
            } catch (\Throwable $e) {
            }

            return $result ?? null;
        }

        /**
         * @param stdClass $payload
         * @param null $expires - can be +1 day, or +1 week, etc
         *
         * @return String
         */
        public function encode(stdClass $payload, $expires = null): String {
            if (!empty($expires)) {
                $payload->exp = is_numeric($expires) ? time() + $expires : strtotime($expires);
            }

            return JWT::encode($payload, $this->key, 'HS256');
        }
    }
}