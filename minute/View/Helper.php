<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/20/2016
 * Time: 7:10 AM
 */
namespace Minute\View {

    use Minute\Error\ViewError;
    use Minute\Resolver\Resolver;

    class Helper {
        const POSITION_HEAD = "position.head";
        const POSITION_BODY = "position.body";
        /**
         * @var string
         */
        private $templateUrl;
        /**
         * @var string
         */
        private $position;

        /**
         * Helper constructor.
         *
         * @param string $templateUrl
         * @param string $position
         */
        public function __construct(string $templateUrl, string $position = self::POSITION_HEAD) {
            $this->templateUrl = $templateUrl;
            $this->position = $position;
        }

        /**
         * @return string
         */
        public function getTemplateUrl(): string {
            return $this->templateUrl;
        }

        /**
         * @param string $templateUrl
         *
         * @return Helper
         */
        public function setTemplateUrl(string $templateUrl): Helper {
            $this->templateUrl = $templateUrl;

            return $this;
        }

        /**
         * @return string
         */
        public function getPosition(): string {
            return $this->position;
        }

        /**
         * @param string $position
         *
         * @return Helper
         */
        public function setPosition(string $position): Helper {
            $this->position = $position;

            return $this;
        }
    }
}