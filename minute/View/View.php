<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/21/2016
 * Time: 12:50 PM
 */
namespace Minute\View {

    use Minute\Resolver\Resolver;

    class View {
        /**
         * @var string
         */
        private $layout;
        /**
         * @var array
         */
        private $vars = [];
        /**
         * @var array
         */
        private $helpers;
        /**
         * @var string
         */
        private $viewFile;
        /**
         * @var bool
         */
        private $pathLayouts;
        /**
         * @var string
         */
        private $finalHtml;
        /**
         * @var string
         */
        private $content;
        /**
         * @var array
         */
        private $additionalLayoutFiles;
        /**
         * @var bool
         */
        private $final = false;
        /**
         * @var array
         */
        private $viewData;

        /**
         * View constructor.
         *
         * @param string $viewFile
         * @param array $viewData
         * @param bool $pathLayouts - Search the view file path for layouts (see documentation)
         */
        public function __construct(string $viewFile = '', array $viewData = [], bool $pathLayouts = true) {
            $this->viewFile    = $viewFile;
            $this->viewData    = $viewData;
            $this->pathLayouts = $pathLayouts;
        }

        /**
         * @return array
         */
        public function getViewData(): array {
            return $this->viewData ?? [];
        }

        /**
         * @param array $viewData
         *
         * @return View
         */
        public function setViewData(array $viewData): View {
            $this->viewData = $viewData;

            return $this;
        }

        /**
         * @return boolean
         */
        public function isFinal(): bool {
            return $this->final;
        }

        /**
         * If true, then it tells minifier and other content related plugins
         * to not modify the content.
         *
         * @param boolean $final
         *
         * @return View
         */
        public function setFinal(bool $final): View {
            $this->final = $final;

            return $this;
        }

        /**
         * @return mixed
         */
        public function getAdditionalLayoutFiles() {
            return $this->additionalLayoutFiles;
        }

        /**
         * @param mixed $additionalLayoutFiles
         *
         * @return View
         */
        public function setAdditionalLayoutFiles(array $additionalLayoutFiles) {
            $this->additionalLayoutFiles = $additionalLayoutFiles;

            return $this;
        }

        /**
         * @return mixed
         */
        public function getContent() {
            return $this->content;
        }

        /**
         * @param mixed $content
         *
         * @return View
         */
        public function setContent($content) {
            $this->content = $content;

            return $this;
        }

        /**
         * @return mixed
         */
        public function getFinalHtml() {
            return $this->finalHtml;
        }

        /**
         * @param mixed $finalHtml
         *
         * @return View
         */
        public function setFinalHtml($finalHtml) {
            $this->finalHtml = $finalHtml;

            return $this;
        }

        /**
         * @return string
         */
        public function getLayout() {
            return $this->layout;
        }

        /**
         * @param string $layout
         *
         * @return View
         */
        public function setLayout($layout): View {
            $this->layout = $layout;

            return $this;
        }

        /**
         * @return string
         */
        public function getViewFile() {
            return $this->viewFile;
        }

        /**
         * @param string $viewFile
         *
         * @return View
         */
        public function setViewFile(string $viewFile): View {
            $this->viewFile = $viewFile;

            return $this;
        }

        /**
         * @return boolean
         */
        public function isPathLayouts(): bool {
            return $this->pathLayouts;
        }

        /**
         * @param boolean $pathLayouts
         *
         * @return View
         */
        public function setPathLayouts(bool $pathLayouts): View {
            $this->pathLayouts = $pathLayouts;

            return $this;
        }

        /**
         * @return array
         */
        public function getHelpers() {
            return $this->helpers;
        }

        /**
         * @param array $helpers
         *
         * @return View
         */
        public function setHelpers($helpers) {
            $this->helpers = $helpers;

            return $this;
        }

        /**
         * @param Helper $helper
         *
         * @return View
         */
        public function with(Helper $helper) {
            $this->helpers[] = $helper;

            return $this;
        }

        /**
         * @return array
         */
        public function getVars() {
            return $this->vars;
        }

        /**
         * @param array $vars
         *
         * @return View
         */
        public function setVars($vars) {
            $this->vars = $vars;

            return $this;
        }

        /**
         * @param string $name
         * @param $value
         *
         * @return $this
         */
        public function set(string $name, $value) {
            $this->vars[$name] = $value;

            return $this;
        }
    }
}