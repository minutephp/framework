<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/20/2016
 * Time: 4:54 PM
 */
namespace Minute\Session {

    use Minute\Config\Config;
    use Minute\Crypto\JwtEx;
    use Minute\Event\AuthEvent;
    use Minute\Http\HttpRequestEx;
    use Minute\Http\HttpResponseEx;
    use Minute\User\AccessManager;
    use Minute\User\UserInfo;
    use stdClass;

    class Session {
        /**
         * Name of cookie which stores user data
         */
        const COOKIE_NAME = "MINUTE_JWT";
        /**
         * Name of cookie which stores user data
         */
        const ADMIN_COOKIE_NAME = "MINUTE_JWT_ADMIN";
        /**
         * @var AccessManager
         */
        private $accessManager;
        /**
         * @var HttpRequestEx
         */
        private $request;
        /**
         * @var JwtEx
         */
        private $jwt;
        /**
         * @var Config
         */
        private $config;
        /**
         * @var UserInfo
         */
        private $userInfo;
        /**
         * @var HttpResponseEx
         */
        private $response;

        /**
         * Session constructor.
         *
         * @param JwtEx $jwt
         * @param HttpRequestEx $request
         * @param HttpResponseEx $response
         * @param AccessManager $accessManager
         * @param UserInfo $userInfo
         * @param Config $config
         */
        public function __construct(JwtEx $jwt, HttpRequestEx $request, HttpResponseEx $response, AccessManager $accessManager, UserInfo $userInfo, Config $config) {
            $this->accessManager = $accessManager;
            $this->config        = $config;
            $this->jwt           = $jwt;
            $this->request       = $request;
            $this->response      = $response;
            $this->userInfo      = $userInfo;
            $this->data          = new stdClass();

            if ($cookie = $this->request->getCookie(self::COOKIE_NAME)) {
                if ($decoded = $this->jwt->decode($cookie)) {
                    $this->data = $decoded;
                }
            }
        }

        public function checkAccess(AuthEvent $event) {
            $level = $event->getLevel();

            if ($level === false) {
                $authorized = true;
            } elseif ($level === true) {
                $authorized = $this->getLoggedInUserId() > 0;
            } elseif ($userId = $this->getLoggedInUserId()) {
                $userGroups = $this->userInfo->getUserGroups($userId);
                $authorized = $this->accessManager->belongsToGroup($level, $userGroups);
            }

            $event->setAuthorized($authorized ?? false);
            $event->setActiveUserId(!empty($authorized) ? $this->getLoggedInUserId() : 0);
        }

        public function hasAccess($level) {
            $auth_event = new AuthEvent($level);
            $this->checkAccess($auth_event);

            return $auth_event->isAuthorized();
        }

        public function isTrialAccount(string $min_paid_level = '') {
            return !$this->hasAccess($min_paid_level ?: $this->config->get(AccessManager::GROUP_KEY . '/min_paid_level', 'power'));
        }

        public function startSession(int $userId, $su = false) {
            $expiry   = $this->config->get('private/site/session_length', '+1 day');
            $jwtValue = $this->getSessionCookie($userId, $expiry);
            $this->response->setCookie($su ? self::ADMIN_COOKIE_NAME : self::COOKIE_NAME, $jwtValue, $expiry);
        }

        public function destroySession() {
            $this->userInfo->clearCache($this->getLoggedInUserId());

            $this->data = new stdClass();
            $this->response->setCookie(self::COOKIE_NAME, null);
        }

        public function getLoggedInUserId() {
            return $this->data->user_id ?? 0;
        }

        public function getSessionCookie(int $userId, string $expiry) {
            return $this->jwt->encode((object) ['user_id' => $userId], $expiry);
        }
    }
}