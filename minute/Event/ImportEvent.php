<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 8/1/2016
 * Time: 3:03 AM
 */
namespace Minute\Event {

    class ImportEvent extends TagEvent {
        /**
         * @var array
         */
        private $content = [];
        /**
         * @var ViewEvent
         */
        private $viewEvent;
        /**
         * @var array
         */
        private $params;

        /**
         * ImportEvent constructor.
         *
         * @param ViewEvent $viewEvent
         * @param string $tag
         * @param array $attrs
         * @param array $params
         */
        public function __construct(ViewEvent $viewEvent, string $tag = '', array $attrs = [], array $params = []) {
            parent::__construct($tag, $attrs);

            $this->viewEvent = $viewEvent;
            $this->params    = $params;
        }

        /**
         * @return array
         */
        public function getParams(): array {
            return $this->params ?? [];
        }

        /**
         * @param array $params
         *
         * @return ImportEvent
         */
        public function setParams(array $params): ImportEvent {
            $this->params = $params;

            return $this;
        }

        /**
         * @return ViewEvent
         */
        public function getViewEvent(): ViewEvent {
            return $this->viewEvent;
        }

        /**
         * @return array
         */
        public function getContent(): array {
            return $this->content;
        }

        /**
         * @param array $content
         *
         * @return ImportEvent
         */
        public function setContent(array $content): ImportEvent {
            $this->content = $content;

            return $this;
        }

        /**
         * @param $content
         *
         * @return ImportEvent
         */
        public function addContent(array $content) {
            $this->content = array_merge(is_array($this->content) ? $this->content : [], $content);

            return $this;
        }
    }
}