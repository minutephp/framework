<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 11:38 PM
 */
namespace Minute\Event {

    class Event {
        /**
         * @var string
         */
        protected $name;
        /**
         * @var string
         */
        protected $data;

        /**
         * @return string
         */
        public function getName() {
            return $this->name;
        }

        /**
         * @param string $name
         *
         * @return Event
         */
        public function setName($name) {
            $this->name = $name;

            return $this;
        }

        /**
         * @return string
         */
        public function getData() {
            return $this->data;
        }

        /**
         * @param string $data
         *
         * @return Event
         */
        public function setData($data) {
            $this->data = $data;

            return $this;
        }
    }
}