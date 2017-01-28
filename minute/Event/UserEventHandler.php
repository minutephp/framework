<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 9/5/2016
 * Time: 10:06 AM
 */
namespace Minute\Event {

    class UserEventHandler extends UserEvent {
        /**
         * @var
         */
        protected $error;
        /**
         * @var bool
         */
        protected $handled = false;

        /**
         * @return mixed
         */
        public function getError() {
            return $this->error;
        }

        /**
         * @param mixed $error
         *
         * @return UserEventHandler
         */
        public function setError($error) {
            $this->error = $error;

            return $this;
        }

        /**
         * @return boolean
         */
        public function isHandled(): bool {
            return $this->handled;
        }

        /**
         * @param boolean $handled
         *
         * @return UserEventHandler
         */
        public function setHandled(bool $handled): UserEventHandler {
            $this->handled = $handled;

            return $this;
        }

    }
}