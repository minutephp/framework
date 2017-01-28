<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/15/2016
 * Time: 1:29 AM
 */
namespace Minute\Model {

    use Illuminate\Database\Eloquent\Collection;
    use Minute\Error\ModelError;
    use Minute\Resolver\Resolver;

    class ModelLoader {
        /**
         * @var ModelExtender
         */
        private $modelExtender;
        /**
         * @var Resolver
         */
        private $resolver;

        /**
         * ModelLoader constructor.
         *
         * @param ModelExtender $modelExtender
         * @param Resolver $resolver
         */
        public function __construct(ModelExtender $modelExtender, Resolver $resolver) {
            $this->modelExtender = $modelExtender;
            $this->resolver      = $resolver;
        }

        public function loadModels($nodes, $alias = null) {
            if (!empty($alias)) {
                if ($topLevelParent = $this->findParentOfNode($nodes, $alias)) { //parent node which contains alias (of child) - for when we only want to load a particular node branch
                    $nodes = [$topLevelParent => $nodes[$topLevelParent]];
                } else {
                    throw new ModelError("No parent contains alias: $alias");
                }
            }

            foreach ((array) $nodes as $node) {
                $model    = $this->createRelations($node);
                $results  = $model->get();
                $self     = $node['self'];
                $metadata = array_merge($this->getDefaults($node['self']), ['total' => ($self['single'] || !empty($self['pk'])) ? 1 : $model->limit(1)->offset(0)->count()]);
                $rows     = new CollectionEx($results, $metadata);

                $models[$node['self']['alias']] = $rows;
                $this->loadData($node, $rows);
            }

            return $models ?? [];
        }

        /**
         * @param array $node
         * @param ModelEx $parent
         *
         * @return mixed
         * @throws ModelError
         */
        public function createRelations($node, $parent = null) {
            $self     = $node['self'];
            $class    = $this->resolver->getModel($self['name']);
            $defaults = $this->getDefaults($self);

            try {
                $model = new $class();
            } catch (\Throwable $e) {
                throw new ModelError("{$self['name']} table or model class not found.");
            }

            if ($parent) {
                $parent::addRelation($self['single'] ? 'hasOne' : 'hasMany', $self['alias'], get_class($model), $self['matchInfo']['column'] ?? $self['matchInfo']['key'],
                    $self['matchInfo']['key'], $defaults, $node);
            } else {
                $model = $this->modelExtender->extend($model, $defaults, $node);
            }

            foreach ($node['children'] ?? [] as $child) {
                $this->createRelations($child, $class);
            }

            return $model;
        }

        /**
         * @param $node
         * @param $rows
         */
        public function loadData($node, $rows) {
            /** @var ModelEx $row */
            foreach ($rows as $row) {
                foreach ($node['children'] ?? [] as $child) {
                    $self  = $child['self'];
                    $alias = $self['alias'];
                    $more  = $row->getRelationValue($alias);

                    if (!$self['single'] && empty($self['pk'])) {
                        $row->setRelation($alias, new CollectionEx($more->all(), array_merge($this->getDefaults($self), ['total' => $row->$alias()->limit(1)->offset(0)->count()])));
                    }

                    if (!empty($child['children']) && $more) {
                        $this->loadData($child, $more instanceof Collection ? $more : [$more]);
                    }
                }
            }
        }

        /**
         * Utility function to find the parent node of a child
         * in a tree
         *
         * @param $parents
         * @param $childAlias
         *
         * @return bool|int|string
         */
        protected function findParentOfNode($parents, $childAlias) {
            foreach ($parents as $me => $children) {
                if ($me === $childAlias) {
                    return $me;
                } elseif (!empty($children['children']) && ($found = $this->findParentOfNode($children['children'], $childAlias))) {
                    return $me;
                }
            }

            return false;
        }

        private function getDefaults($metadata) {
            $defaults = [];

            foreach (ModelExtender::metadataKeys as $key) {
                if (isset($metadata[$key])) {
                    $defaults[$key] = $metadata[$key];
                }
            }

            return $defaults;
        }
    }
}