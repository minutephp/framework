<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 7/5/2016
 * Time: 11:34 AM
 */
namespace Minute\Model {

    use Carbon\Carbon;
    use Cocur\Slugify\Slugify;
    use Htmlawed;
    use Minute\Database\Database;

    class ModelAutoFill {
        /**
         * @var Database
         */
        private $database;

        /**
         * ModelAutoFill constructor.
         *
         * @param Database $database
         */
        public function __construct(Database $database) {
            $this->database = $database;
        }

        /**
         * Automatically fills fields like created_at, updated_at, *_slug, *_safe
         * and sets unset fields to null
         *
         * @param ModelEx $model
         *
         * @return ModelEx
         */
        public function fillMissing($model) {
            if ($columns = $this->database->getColumns($model->getTable())) {
                foreach ($columns as $column) {
                    if (!$model->$column && ($model->$column !== 0)) {
                        if (preg_match('/^(created_at|updated_at)$/', $column)) {
                            $model->$column = Carbon::now();
                        } elseif (preg_match('/(\w+)\_slug$/', $column, $matches)) {
                            if ($root = $model->{$matches[1]} ?? null) {
                                $pk    = $model->getKeyName();
                                $slug  = $copy = (new Slugify())->slugify($root);
                                $count = 1;

                                while ($record = $model::where($column, '=', $slug)->first()) {
                                    if ($record->$pk == $model->$pk) {
                                        $slug = false;
                                        break;
                                    }

                                    $slug = sprintf('%s-%d', $copy, $count++);
                                }

                                if (!empty($slug)) {
                                    $model->$column = $slug;
                                }
                            }
                        } elseif ($model->isNullable($column)) {
                            $model->$column = null;
                        }
                    }

                    if (preg_match('/\_safe$/', $column)) {
                        $model->$column = Htmlawed::filter($model->$column);
                    } elseif (preg_match('/\_json$/', $column) && is_array($model->$column)) {
                        $model->$column = json_encode($model->$column);
                    }
                }
            }

            return $model;
        }
    }
}