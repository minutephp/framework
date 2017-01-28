<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/21/2016
 * Time: 1:29 PM
 */
namespace Minute\Model {

    use Minute\Database\Database;
    use Minute\Resolver\Resolver;

    class ModelBridge {
        /**
         * @var ModelJs
         */
        private $modelJs;
        /**
         * @var Database
         */
        private $database;
        /**
         * @var Resolver
         */
        private $resolver;

        /**
         * ModelBridge constructor.
         *
         * @param ModelJs $modelJs
         * @param Database $database
         * @param Resolver $resolver
         */
        public function __construct(ModelJs $modelJs, Database $database, Resolver $resolver) {
            $this->modelJs  = $modelJs;
            $this->database = $database;
            $this->resolver = $resolver;
        }

        public function modelToJsClasses($parent, string $foreignKey = '') {
            /** @var ModelEx $model */
            $self     = $parent['self'];
            $template = '';

            if ($class = $this->resolver->getModel($self['name'])) {
                $model    = new $class;
                $localKey = $model->getKeyName();
                $template .= $this->modelJs->createItem($self['alias'], $parent['children'], $this->database->getColumns($model->getTable()));
                $template .= $this->modelJs->createItemArray($self['alias'], $class, $localKey, $foreignKey);

                foreach ($parent['children'] ?? [] as $child) {
                    $template .= $this->modelToJsClasses($child, $localKey);
                }
            }

            return $template;
        }

        public function modelToJsData($model, string $name) {
            return $this->modelJs->createData($model, $name);
        }
    }
}