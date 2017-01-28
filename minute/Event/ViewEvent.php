<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 6:18 PM
 */
namespace Minute\Event {

    use Minute\View\ViewParser;

    class ViewEvent extends TagEvent {
        /**
         * @var string
         */
        private $content;
        /**
         * @var ViewParser
         */
        private $view;

        /**
         * ViewEvent constructor.
         *
         * @param ViewParser $view
         * @param string $tag
         * @param array $attrs
         */
        public function __construct(ViewParser $view, string $tag, array $attrs = []) {
            parent::__construct($tag, $attrs);

            $this->view    = $view;
            $this->content = '';
        }

        /**
         * @return ViewParser
         */
        public function getView() {
            return $this->view;
        }

        /**
         * @return string
         */
        public function getContent() {
            return $this->content;
        }

        /**
         * @param string $content
         *
         * @return ViewEvent
         */
        public function setContent($content) {
            $this->content = $content;

            return $this;
        }
    }
}