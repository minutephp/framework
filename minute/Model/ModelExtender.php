<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/16/2016
 * Time: 8:37 PM
 */
namespace Minute\Model {

    use Illuminate\Database\Eloquent\Relations\Relation;
    use Minute\Model\ModelEx;
    use Minute\Resolver\Resolver;

    class ModelExtender {
        /**
         * @param ModelEx|Relation $model
         * @param array $metadata
         *
         * @return mixed
         */

        const metadataKeys = ['pk', 'offset', 'limit', 'order', 'search', 'conditions'];

        // Note: We can't add a constructor with DI here because `ModelEx` creates an instance of this class. But
        // ModelEx extends Eloquent's Model class which unfortunately can't support DI (being a singleton)

        public function extend($model, $metadata = [], $relations = []) {
            /** @var ModelEx $model */
            $oModel = $model instanceof ModelEx ? $model : ($model instanceof Relation ? $model->getRelated() : null);

            if (!empty($metadata['pk'])) {//if pk is set we don't have to limit, order or offset!
                $keyName                  = $oModel->getKeyName();
                $metadata['conditions'][] = [$metadata['pri'] ?? $keyName, '=', $metadata['pk']];
                $model                    = $model->offset(0)->limit(1);
            } else {
                $model = $model->offset(@$metadata['offset'] ?: 0)->limit($metadata['limit'] ?: 20);

                if ($order = $metadata['order'] ?? null) {
                    if (preg_match('/\(.*\)/', $metadata['order'])) {
                        $model = $model->orderByRaw($metadata['order']);
                    } else {
                        $orderBy = preg_match('/(.*?)(ASC|DESC)?$/i', $order, $matches) ? [trim($matches[1]), $matches[2] ?? 'asc'] : [$order, 'asc'];
                        $model   = $model->orderBy($orderBy[0], $orderBy[1] ?? 'asc');
                    }
                } elseif ($oModel instanceof ModelEx) {
                    $model = $model->orderBy($oModel->getKeyName());
                }
            }

            foreach ($metadata['conditions'] ?? [] as $condition) {
                $model = is_array($condition) ? call_user_func_array([$model, 'where'], $condition) : $model->where($condition);
            }

            if ($search = $metadata['search'] ?? null) {
                if (!empty($oModel)) {
                    $cols        = preg_split('/\s*,\s*/', $search['columns']);
                    $columns     = [];
                    $parentTable = $oModel->getTable();

                    $searchValues = function ($cols) use ($search) {
                        foreach ($cols as $col) {
                            foreach ((array) $search['value'] as $val) {
                                if (!empty($val)) {
                                    $ors[] = sprintf("%s LIKE %s", $this->quote_name($col), $this->quote($val));
                                }
                            }
                        }

                        return join(' OR ', $ors ?? []);
                    };

                    foreach ($cols as $col) {
                        list($table, $column) = preg_match('/(\w+)\.(\w+)$/', $col, $matches) ? [$matches[1], $matches[2]] : [$parentTable, $col];
                        $tables[$table][] = $column;
                        $columns[$table]  = array_slice(explode('.', $col), 0, -1);
                    }

                    foreach ($tables ?? [] as $table => $cols) {
                        /** @var ModelEx $theModel */
                        $theModel = $oModel;

                        if ($table !== $theModel->getTable()) {
                            $q = [sprintf('(%s in (', $oModel->getKeyName())];

                            if (!empty($relations)) {
                                $childNode = $relations['children'];

                                foreach ($columns[$table] ?? [] as $child) {
                                    if ($childInfo = $childNode[$child]) {
                                        if ($childClass = (new Resolver())->getModel($childInfo['self']['name'])) {
                                            $theModel  = new $childClass;
                                            $parentKey = $childInfo['self']['matchInfo']['key'];
                                            $q[]       = sprintf('SELECT %s FROM %s WHERE %s in (', $parentKey, $theModel->getTable(), $theModel->getKeyName());
                                            $childNode = $childNode[$child]['children'];
                                        }
                                    } else {
                                        break;
                                    }
                                }
                            }

                            $q[] = sprintf('SELECT %s FROM %s WHERE (%s)', $theModel->getKeyName(), $theModel->getTable(), $searchValues($cols));
                            $q[] = str_repeat(')', count($q));

                            $queries[] = implode(' ', $q);
                        } else {
                            $queries[] = sprintf('(%s)', $searchValues($cols));
                        }
                    }

                    if (!empty($queries)) {
                        $query = sprintf('(%s)', join(' OR ', $queries));
                        $model = $model->whereRaw($query);
                    }
                }
            }

            return $model;
        }

        public function quote_name($col) {
            return sprintf('`%s`', preg_replace('/[^A-Za-z0-9_]+/', '', $col));
        }

        protected function quote($val) { //we can't use PDO::quote here since we can't use DI for Database class in constructor (as stated above)
            if (!empty($val) && is_string($val)) {
                return sprintf("'%s'", str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), preg_replace('/[^\x20-\x7E]/', '', $val)));
            }

            return '';
        }
    }
}