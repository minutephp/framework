<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 10/10/2016
 * Time: 5:13 AM
 */
namespace Minute\Event {

    class SeoEvent extends Event {
        const SEO_GET_TITLE    = 'seo.get.title';
        const IMPORT_PAGE_LIST = 'import.page.list';
        /**
         * @var string
         */
        private $title;
        /**
         * @var array
         */
        private $meta;
        /**
         * @var string
         */
        private $url;

        /**
         * SeoEvent constructor.
         *
         * @param string $url
         * @param string $title
         * @param array $meta
         */
        public function __construct(string $url = '', string $title = '', array $meta = []) {
            $this->url   = $url;
            $this->title = $title;
            $this->meta  = $meta;
        }

        /**
         * @return string
         */
        public function getUrl(): string {
            return $this->url;
        }

        /**
         * @return string
         */
        public function getTitle(): string {
            return $this->title;
        }

        /**
         * @param string $title
         *
         * @return SeoEvent
         */
        public function setTitle(string $title): SeoEvent {
            $this->title = $title;

            return $this;
        }

        /**
         * @return array
         */
        public function getMeta(): array {
            return $this->meta;
        }

        /**
         * @param array $meta
         *
         * @return SeoEvent
         */
        public function setMeta(array $meta): SeoEvent {
            $this->meta = $meta;

            return $this;
        }

    }
}