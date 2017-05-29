<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 10:42 PM
 */
namespace Minute\Routing {

    use Auryn\Injector;
    use Closure;
    use Minute\Model\ModelParserGet;
    use Minute\Model\ModelParserPost;
    use Minute\Model\Permission;
    use Symfony\Component\Routing\Route;

    class RouteEx extends Route {
        /**
         * @var array
         */
        private $permissions = [];
        /**
         * @var array
         */
        private $constraints = [];
        /**
         * @var array
         */
        private $deleteCascades = [];
        /**
         * @var int
         */
        private $cached = 0;

        /**
         * @return int
         */
        public function getCached(): int {
            return $this->cached;
        }

        /**
         * @param int $cached
         *
         * @return RouteEx
         */
        public function setCached(int $cached): RouteEx {
            $this->cached = $cached;

            return $this;
        }

        /**
         * @return mixed
         */
        public function getDeleteCascades() {
            return $this->deleteCascades;
        }

        /**
         * Automatically cascades deletes in child table when parent record is removed
         * (useful when foreign keys are not supported)
         *
         * @param string $parentModelAlias
         * @param array $childModelName
         *
         * @return RouteEx
         */
        public function setDeleteCascade(string $parentModelAlias, $childModelName) {
            $this->deleteCascades[$parentModelAlias] = array_merge($this->deleteCascades[$parentModelAlias] ?? [], (array) $childModelName);

            return $this;
        }

        /**
         * @param string $modelAlias
         * @param string $permission
         *
         * @return RouteEx
         */
        public function setReadPermission(string $modelAlias, string $permission) {
            return $this->setPermission('read', $modelAlias, $permission);
        }

        /**
         * @param string $modelAlias
         *
         * @return string
         */
        public function getReadPermission(string $modelAlias) {
            return $this->getPermission('read', $modelAlias);
        }

        /**
         * @param string $modelAlias
         * @param string $permission
         *
         * @return RouteEx
         */
        public function setJoinPermission(string $modelAlias, string $permission) {
            return $this->setPermission('join', $modelAlias, $permission);
        }

        /**
         * @param string $modelAlias
         *
         * @return string
         */
        public function getJoinPermission(string $modelAlias) {
            return $this->getPermission('join', $modelAlias);
        }

        /**
         * @param string $modelAlias
         * @param string $permission
         *
         * @return RouteEx
         */
        public function setCreatePermission(string $modelAlias, string $permission) {
            return $this->setPermission('create', $modelAlias, $permission);
        }

        /**
         * @param string $modelAlias
         *
         * @return string
         */
        public function getCreatePermission(string $modelAlias) {
            return $this->getPermission('create', $modelAlias);
        }

        /**
         * @param string $modelAlias
         * @param string $permission
         *
         * @return RouteEx
         */
        public function setUpdatePermission(string $modelAlias, string $permission) {
            return $this->setPermission('update', $modelAlias, $permission);
        }

        /**
         * @param string $modelAlias
         *
         * @return string
         */
        public function getUpdatePermission(string $modelAlias) {
            return $this->getPermission('update', $modelAlias);
        }

        /**
         * @param string $modelAlias
         * @param string $permission
         *
         * @return RouteEx
         */
        public function setDeletePermission(string $modelAlias, string $permission) {
            return $this->setPermission('delete', $modelAlias, $permission);
        }

        /**
         * @param string $modelAlias
         *
         * @return string
         */
        public function getDeletePermission(string $modelAlias) {
            return $this->getPermission('delete', $modelAlias);
        }

        /**
         * @param string $modelAlias
         * @param string $permission
         *
         * @return RouteEx
         */
        public function setAllPermissions(string $modelAlias, string $permission) {
            foreach (['create', 'read', 'update', 'delete'] as $type) {
                $this->setPermission($type, $modelAlias, $permission);
            }

            return $this;
        }

        /**
         * @param string $modelAlias
         *
         * @return string
         */
        public function getAllPermissions(string $modelAlias) {
            return $this->permissions ?? [];
        }

        /**
         * @return mixed
         * @throws \Auryn\InjectionException
         */
        public function parseGetModels() {
            /** @var ModelParserGet $modelParser */
            $modelParser = (new Injector())->make('Minute\Model\ModelParserGet', [$this->getDefault('models') ?? []]);

            return $modelParser->getParentsWithChildren();
        }

        /**
         * @return mixed
         * @throws \Auryn\InjectionException
         */
        public function parsePostModels() {
            /** @var ModelParserPost $modelParser */
            $modelParser = new ModelParserPost($this->getDefault('models') ?? []);

            return $modelParser->getModels();
        }

        /**
         * @param string $alias
         * @param array|Closure $constraint
         *
         * @return RouteEx
         */
        public function addConstraint(string $alias, $constraint) {
            $this->constraints[$alias]   = $this->constraints[$alias] ?? [];
            $this->constraints[$alias][] = $constraint;

            return $this;
        }

        /**
         * @param string $alias
         *
         * @return array
         */
        public function getConstraintByAlias(string $alias) {
            return $this->constraints[$alias] ?? [];
        }

        /**
         * @return array
         */
        public function getAllConstraints() {
            return $this->constraints;
        }

        /**
         * Sets a default value.
         * @return RouteEx
         */
        public function setDefault($name, $default) {
            parent::setDefault($name, $default);

            return $this;
        }

        /**
         * @param string $type
         * @param string $modelAlias
         * @param string $permission
         *
         * @return RouteEx
         */
        protected function setPermission(string $type, string $modelAlias, string $permission) {
            $this->permissions[$type] = $this->permissions[$type] ?? [];;
            $this->permissions[$type][$modelAlias] = $permission;

            return $this;
        }

        private function getPermission(string $type, string $modelAlias) {
            return $this->permissions[$type][$modelAlias] ?? (preg_match('/^(read|update)/', $type) ? Permission::SAME_USER : ($type === 'join' ? Permission::EVERYONE : Permission::NOBODY));
        }
    }
}