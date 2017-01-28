<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 10/10/2016
 * Time: 7:17 AM
 */
namespace Minute\Dom {

    use Minute\Log\LoggerEx;

    class TagUtils {
        /**
         * @var LoggerEx
         */
        private $logger;

        /**
         * TagUtils constructor.
         *
         * @param LoggerEx $logger
         */
        public function __construct(LoggerEx $logger) {
            $this->logger = $logger;
        }

        public function insertBeforeTag($tag, $replace, $content) {
            return $this->str_replace_once($tag, $replace . $tag, $content, $pos);
        }

        public function insertAfterTag($tag, $replace, $content) {
            return $this->str_replace_once($tag, $tag . $replace, $content, $pos);
        }

        protected function str_replace_once($needle, $replace, $haystack, &$pos) {
            $pos = stripos($haystack, $needle);

            if ($pos === false) {
                $this->logger->warn("Cannot find tag $needle");
            }

            return ($pos !== false) ? substr_replace($haystack, $replace, $pos, strlen($needle)) : $haystack;
        }
    }
}