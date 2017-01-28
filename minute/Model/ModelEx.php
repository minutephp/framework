<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/14/2016
 * Time: 2:36 AM
 */
namespace Minute\Model {

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\Relation;

    class ModelEx extends Model {
        static $aliases = [];

        /** @var array */
        protected $notNullable = [];
        public    $timestamps  = false;
        public    $metadata    = null;

        /**
         * Dynamically create a relationship on this model.
         *
         * @param string $relation        - The relationship type like hasMany, hasOne, etc
         * @param string $alias           - Alias by which you want to access this relationship, e.g. $user->groups
         * @param string $childModelClass - The Model class for this relation like App\Model\UserGroup
         * @param string $foreignKey      - The key in child table which is the primary key for $this, e.g. user_id in UserGroup table when $this = Users
         * @param string $localKey        - The primary key for the child table
         * @param array $props            - Defaults like offset, limit, pk, etc
         *
         * @return mixed
         */
        public function addRelation(string $relation, string $alias, $childModelClass, $foreignKey = null, $localKey = null, $props = null, $node = null) {
            if (!empty($relation) && !empty($alias) && !empty($childModelClass)) {
                $args = ['relation' => $relation, 'alias' => $alias, 'childModelClass' => $childModelClass, 'foreignKey' => $foreignKey, 'localKey' => $localKey, 'props' => $props, 'node' => $node];

                return self::$aliases[get_called_class()][$alias] = $args;
            }

            return false;
        }

        /**
         * Get a specified relationship.
         *
         * @param  string $relation
         *
         * @return mixed
         */
        public function getRelation($relation) {
            return $this->dynamicRelation($relation) ?: parent::getRelation($relation);
        }

        /**
         * Return all dynamically created relations
         *
         * @return mixed
         */
        public function getDynamicRelations() {
            return self::$aliases[get_called_class()];
        }

        /**
         * Special function to access $model->relation, like $user->groups, etc
         *
         * @param string $key
         *
         * @return mixed
         */
        public function getRelationValue($key) {
            if ($results = $this->relations[$key] ?? null) {
                return $results;
            } elseif ($relation = $this->dynamicRelation($key)) {
                return $this->relations[$key] = $relation->getResults();
            } else {
                return parent::getRelationValue($key);
            }
        }

        /**
         * Special handler to handler relations when called as functions, e.g. $user->groups()
         *
         * @param string $method
         * @param array $parameters
         *
         * @return null
         */
        public function __call($method, $parameters) {
            if ($relation = $this->dynamicRelation($method)) {
                return $relation;
            } else {
                return call_user_func_array(['parent', '__call'], [$method, $parameters]);
            }
        }

        /**
         * @param string $column
         *
         * @return bool
         */
        public function isNullable($column) {
            return !in_array($column, $this->notNullable);
        }

        public function toArray() {
            $result = parent::toArray();

            if(!empty($this->metadata)){
                $result['metadata'] = $this->metadata;
            }

            return $result;
        }

        /**
         * Return the relation if it was added dynamically
         *
         * @param $name
         *
         * @return Relation
         */
        protected function dynamicRelation($name) {
            if ($relationAndProps = self::$aliases[get_called_class()][$name] ?? null) {
                return $this->createRelationWithProps($relationAndProps);
            }

            return null;
        }

        protected function createRelationWithProps($relation) {
            $class   = $relation['childModelClass'];
            $builder = call_user_func_array(['parent', $relation['relation'] ?: 'hasMany'], [$class, $relation['foreignKey'], $relation['localKey']]);

            if ($props = $relation['props'] ?? null) {
                /** @var ModelEx $childModel */
                $childModel = new $class;
                $builder    = (new ModelExtender())->extend($builder, array_merge($props, ['pri' => $childModel->getKeyName()]), $relation['node']);
            }

            return $builder;
        }
    }
}