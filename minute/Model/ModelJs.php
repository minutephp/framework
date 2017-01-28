<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/21/2016
 * Time: 2:54 PM
 */
namespace Minute\Model {

    use Illuminate\Database\Eloquent\Collection;
    use Illuminate\Support\Str;
    use Minute\Utils\PathUtils;

    class ModelJs {
        /**
         * @var PathUtils
         */
        private $utils;

        /**
         * ModelJs constructor.
         *
         * @param PathUtils $utils
         */
        public function __construct(PathUtils $utils) {
            $this->utils = $utils;
        }

        public function createItem(string $name, array $children = [], array $columns = ['id']) {
            $name        = sprintf('%sItem', $this->fixName($name));
            $columnsTxt  = join(',', array_map('escapeshellarg', $columns));
            $childrenTxt = '';

            foreach ($children as $child) {
                $tabs = str_repeat("\t", 5);
                $self = $child['self'];
                $childrenTxt .= sprintf("this.%s = (new Minute.Models.%sArray(this))%s;\n%s", $self['alias'], $this->fixName($self['alias']), $self['single'] ? '.create()' : '', $tabs);
            }

            $template = <<< EOF

        Minute.Models.$name = (function (_super) {
            __extends($name, _super);
            function $name(parent) {
                _super.call(this, parent, [$columnsTxt]);
                
                $childrenTxt
            }
            return $name;
        }(Minute.Item));

EOF;

            return $template;
        }

        public function createItemArray(string $name, string $modelClass, string $localKey, $foreignKey = null) {
            $alias    = $name;
            $single   = sprintf('%sItem', $this->fixName($name));
            $multiple = sprintf('%sArray', $this->fixName($name));
            $joinKey  = !empty($foreignKey) ? sprintf("'%s'", $foreignKey) : 'null';
            $theClass = $this->utils->filename($modelClass);

            $template = <<< EOF
            
        Minute.Models.$multiple = (function (_super) {
            __extends($multiple, _super);
            function $multiple(parent) {
                _super.call(this, Minute.Models.$single, parent, '$alias', '$theClass', '$localKey', $joinKey);
            }
            return $multiple;
        }(Minute.Items));

EOF;

            return $template;
        }

        /**
         * @param Collection $model
         * @param string $single
         *
         * @return string
         */
        public function createData($model, string $single) {
            $str = sprintf("\n\t\t\$scope.%s = new Minute.Models.%sArray(null);\n", $single, $this->fixName($single));
            $str .= sprintf("\t\t\$scope.%s.load(%s);\n", $single, json_encode($model->toArray(), JSON_PRETTY_PRINT));

            //{metadata: {offset: 0, limit: 2, total: %d}, items: %s}

            return $str;
        }

        protected function fixName($name) {
            return ucfirst(Str::camel(Str::singular($name)));
        }
    }
}