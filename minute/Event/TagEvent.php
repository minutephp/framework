<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 8/23/2016
 * Time: 12:17 AM
 */
namespace Minute\Event {

    use SimpleXMLElement;

    class TagEvent extends Event {
        /**
         * @var string
         */
        protected $tag;
        /**
         * @var array
         */
        private $attrs;

        /**
         * TagEvent constructor.
         *
         * @param string $tag
         * @param array $attrs
         */
        public function __construct(string $tag = '', array $attrs = []) {
            $this->tag = $tag;
            $this->attrs = $attrs;
        }

        /**
         * @return array
         */
        public function getAttrs(): array {
            return $this->attrs;
        }

        /**
         * @param array $attrs
         *
         * @return TagEvent
         */
        public function setAttrs(array $attrs): TagEvent {
            $this->attrs = $attrs;

            return $this;
        }

        /**
         * @return string
         */
        public function getTag(): string {
            return $this->tag;
        }

        /**
         * @param string $tag
         *
         * @return TagEvent
         */
        public function setTag(string $tag): TagEvent {
            $this->tag = $tag;

            return $this;
        }
    }
}