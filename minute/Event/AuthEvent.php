<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 6:18 PM
 */
namespace Minute\Event {

    use Http\HttpRequest;
    use Http\HttpResponse;

    class AuthEvent extends Event {
        const AUTH_CHECK_ACCESS        = "auth.check.access"; //for route access
        const AUTH_GET_ALL_USER_GROUPS = "auth.get.all.user.groups"; //for route access

        /**
         * @var bool
         */
        protected $authorized;
        /**
         * @var bool|string
         */
        private $level;
        /**
         * @var int
         */
        private $activeUserId;

        /**
         * AuthEvent constructor.
         *
         * @param bool|string $level
         */
        public function __construct($level) {
            $this->level = $level;
        }

        /**
         * @return mixed
         */
        public function getLevel() {
            return $this->level;
        }

        /**
         * @param mixed $level
         *
         * @return AuthEvent
         */
        public function setLevel($level) {
            $this->level = $level;

            return $this;
        }

        public function isAuthorized() {
            return ($this->authorized || ($this->level === false));
        }

        /**
         * @param boolean $authorized
         *
         * @return AuthEvent
         */
        public function setAuthorized($authorized) {
            $this->authorized = $authorized;

            return $this;
        }

        /**
         * @param int $activeUserId
         *
         * @return AuthEvent
         */
        public function setActiveUserId($activeUserId) {
            $this->activeUserId = $activeUserId;

            return $this;
        }

        /**
         * @return int
         */
        public function getActiveUserId() {
            return $this->activeUserId ?: 0;
        }

        public function isLoggedInUser() {
            return $this->getActiveUserId() > 0;
        }
    }
}