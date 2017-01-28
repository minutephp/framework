<?php
/**
 * Created by: MinutePHP Framework
 */
namespace App\Model {

    use Minute\Model\ModelEx;

    class MPhinxLog extends ModelEx {
        protected $table      = 'm_phinx_logs';
        protected $primaryKey = 'version';
    }
}