<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/14/2016
 * Time: 2:24 PM
 */
namespace Minute\Model {

    use Minute\Log\LoggerEx;
    use StrScan\StringScanner;

    class ModelParserGet {
        /**
         * @var array
         */
        private $models;
        /**
         * @var array
         */
        private $modelStrs;
        /**
         * @var LoggerEx
         */
        private $logger;

        /**
         * Parse constructor.
         *
         * @param array $modelStrs
         * @param LoggerEx $logger
         */
        public function __construct(array $modelStrs, LoggerEx $logger) {
            $this->modelStrs = $modelStrs;
            $this->logger    = $logger;

            $this->parse();
        }

        public function getParentsWithChildren() {
            $top = ['children' => []];

            $insertIn = function (&$root, $parentAlias, $alias, $value) use (&$insertIn) {
                foreach ($root['children'] as $i => $child) {
                    if ($i == $parentAlias) {
                        $root['children'][$i]['children'][$alias] = $value;
                    } else {
                        if ($result = $insertIn($root['children'][$i], $parentAlias, $alias, $value)) {
                            return $result;
                        }
                    }
                }

                return false;
            };

            foreach ($this->models as $index => $model) {
                $alias = $model['alias'];
                $value = ['children' => [], 'self' => $this->getModelByAlias($alias)];

                if ($parentAlias = $model['matchInfo']['parent'] ?? null) {
                    if ($insertIn($top, $parentAlias, $alias, $value)) {
                        $this->logger->warning("Unable to find parent ($parentAlias) for model: $alias");
                    }
                } else {
                    $top['children'][$alias] = $value;
                }
            }

            return $top['children'];
        }

        /**
         * @return array
         */
        public function getModels() {
            return $this->models;
        }

        public function getModelByAlias($alias) {
            foreach ($this->models as $model) {
                if ($model['alias'] == $alias) {
                    return $model;
                }
            }

            return null;
        }

        protected function parse() {
            $this->models = [];

            foreach ($this->modelStrs as $str) {
                $scanner = new StringScanner($str);
                $model   = [];

                $model['name']  = $scanner->scan('/\w+/');
                $getFirstMatch  = $scanner->scan('/\[([^\]]+)\]/') ? $scanner->getCapture(0) : null;
                $getSecondMatch = $scanner->scan('/\[([\d]*)\]/') ? $scanner->getCapture(0) : null;

                if ($getSecondMatch === null && (is_numeric($getFirstMatch) || empty($getFirstMatch))) {
                    list($getSecondMatch, $getFirstMatch) = array($getFirstMatch, null);
                }

                $model['match']  = $getFirstMatch;
                $model['limit']  = (int) ($getSecondMatch ?: ($getSecondMatch === null ? 1 : 20));
                $model['alias']  = $scanner->scanUntil('/\s*as (\w+)/i') ? $scanner->getCapture(0) : $model['name'];
                $model['order']  = $scanner->scanUntil('/\s*order by (.+)$/i') ? $scanner->getCapture(0) : '';
                $model['single'] = $getSecondMatch === null;

                if ($match = $model['match']) {
                    $scanner = new StringScanner($match);
                    $col     = $scanner->scanUntil('/["\']?(.+?)["\']?\s*\=\s*/') ? $scanner->getCapture(0) : null;
                    $value   = $scanner->scanUntil('/\|\|\s*/') ?: $scanner->scanUntil('/(.*)$/');

                    if (preg_match('/(\w+)\.(\w+)/', $value, $cols)) {
                        $model['matchInfo'] = ['type' => 'relational', 'column' => $col ?: $cols[2], 'parent' => $cols[1], 'key' => $cols[2]];
                    } elseif (preg_match('/\$(\w+)/', $value, $glob)) {
                        $model['matchInfo'] = ['type' => 'var', 'col' => $col ?: $glob[1], 'name' => $glob[1]];
                    } elseif (preg_match('/^(?:[\"\'])(\\w+)(?:[\"\'])$/', $value, $placeholder)) {
                        $model['matchInfo'] = ['type' => 'string', 'col' => $col ?: $placeholder[1], 'value' => $placeholder[1]];
                    } elseif (preg_match('/(\w+)/', $value, $placeholder)) {
                        $model['matchInfo'] = ['type' => 'url_param', 'col' => $col ?: $placeholder[1], 'name' => $placeholder[1]];
                    }

                    if (!$scanner->hasTerminated()) {
                        $model['matchInfo']['default'] = $scanner->scan('/([\"\'])(.*?)\\1$/') ? $scanner->getCapture(1) : $scanner->getRemainder();
                    }
                }

                $this->models[] = $model;
            }
        }
    }
}