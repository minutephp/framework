<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/14/2016
 * Time: 2:24 PM
 */
namespace Minute\Model {

    use StrScan\StringScanner;

    class ModelParserPost {
        /**
         * @var array
         */
        private $models;
        /**
         * @var array
         */
        private $modelStrs;

        /**
         * Parse constructor.
         *
         * @param array $modelStrs
         */
        public function __construct(array $modelStrs) {
            $this->modelStrs = $modelStrs;

            $this->parse();
        }

        /**
         * @return array
         */
        public function getModels() {
            return $this->models;
        }

        protected function parse() {
            $this->models = [];

            foreach ($this->modelStrs as $str) {
                $scanner = new StringScanner($str);
                $model   = [];

                $model['name']   = $scanner->scan('/\w+/');
                $model['fields'] = $scanner->scan('/\[([^\]]+)\]/') ? preg_split('/\s*,\s*/', $scanner->getCapture(0)) : null;
                $model['alias']  = $scanner->scanUntil('/\s*as (\w+)/i') ? $scanner->getCapture(0) : $model['name'];

                $this->models[] = $model;
            }
        }
    }
}