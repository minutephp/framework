<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/28/2016
 * Time: 6:36 PM
 */
namespace App\Controller\Generic {

    use Minute\Error\ModelError;
    use Minute\Http\HttpRequestEx;
    use Minute\Model\ModelAutoFill;
    use Minute\Model\ModelEx;
    use Minute\Resolver\Resolver;
    use Minute\Routing\RouteEx;

    class DefaultPostHandler {
        /**
         * @var Resolver
         */
        private $resolver;
        /**
         * @var ModelAutoFill
         */
        private $modelAutoFill;

        /**
         * DefaultPostHandler constructor.
         *
         * @param Resolver $resolver
         * @param ModelAutoFill $modelAutoFill
         */
        public function __construct(Resolver $resolver, ModelAutoFill $modelAutoFill) {
            $this->resolver      = $resolver;
            $this->modelAutoFill = $modelAutoFill;
        }

        public function index(string $_mode, $_models, RouteEx $_route, array $_parents, string $alias) {
            if ($_mode === 'delete') {
                if ($parent = $this->findParentByAlias($_parents, $alias)) {
                    $cascade  = $_route->getDeleteCascades();
                    $cascades = $cascade[$parent['alias']];

                    /** @var ModelEx $model */
                    foreach ($_models as $model) {
                        $pk      = $model->getKeyName();
                        $pkValue = $model->$pk;

                        if (!empty($cascades)) {
                            foreach ((array) $cascades as $child) {
                                if ($child = $this->findParentByAlias($_parents, $child)) {
                                    $name = $child['name'];

                                    if ($childModel = $this->resolver->getModel($name)) {
                                        $records = $childModel::where($pk, '=', $pkValue)->get();

                                        if ($recursive = $cascade[$child['alias']] ?? null) {
                                            $this->index($_mode, $records, $_route, $_parents, $child['alias']);
                                        }

                                        /** @var ModelEx $record */
                                        foreach ($records as $record) {
                                            $record->delete();
                                        }
                                    } else {
                                        throw new ModelError("Cannot find child model '$name' for delete cascade");
                                    }
                                } else {
                                    throw new ModelError("Child model '$child' not defined");
                                }
                            }
                        }

                        if ($model->delete()) {
                            $items[] = ['pk' => $pkValue];
                        }
                    }
                }
            } else {
                /** @var ModelEx $model */
                foreach ($_models as $model) {
                    $this->modelAutoFill->fillMissing($model);
                    $model->save();
                    $items[] = $model->toArray();
                }
            }

            return json_encode(['items' => $items ?? []]);
        }

        private function findParentByAlias($_parents, $child) {
            foreach ($_parents as $parent) {
                if ($parent['alias'] === $child) {
                    return $parent;
                }
            }

            return $this->resolver->getModel($child) ? ['name' => $child, 'alias' => $child] : false;
        }
    }
}