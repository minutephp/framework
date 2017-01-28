<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 9/8/2016
 * Time: 3:09 AM
 */
namespace Minute\Event {

    use Minute\View\ViewParser;

    class ViewParserEvent extends Event {
        const VIEWPARSER_RENDER    = "viewparser.render";

        /**
         * @var ViewParser
         */
        private $viewParser;
        /**
         * @var string
         */
        private $html;

        /**
         * ViewParserEvent constructor.
         *
         * @param ViewParser $viewParser
         * @param string $html
         */
        public function __construct(ViewParser $viewParser, string $html = '') {
            $this->viewParser = $viewParser;
            $this->html       = $html;
        }

        /**
         * @return string
         */
        public function getHtml(): string {
            return $this->html;
        }

        /**
         * @param string $html
         *
         * @return ViewParserEvent
         */
        public function setHtml(string $html): ViewParserEvent {
            $this->html = $html;

            return $this;
        }

        /**
         * @return ViewParser
         */
        public function getViewParser(): ViewParser {
            return $this->viewParser;
        }
    }
}