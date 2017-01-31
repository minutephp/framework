<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/21/2016
 * Time: 10:33 AM
 */
namespace Minute\Debug {

    use Minute\Database\Database;

    class Debugger {
        /**
         * @var Database
         */
        private $database;

        /**
         * Debugger constructor.
         *
         * @param Database $database
         */
        public function __construct(Database $database) {
            $this->database = $database;
        }

        /**
         * @return bool
         */
        public function enabled() {
            if (defined('DEBUG_MODE') && (DEBUG_MODE === true)) { //define in public\index.php
                return true;
            }

            if (@getenv('PHP_DEBUG_MODE') == "true") { //using SetEnv in Apache's httpd.conf
                return true;
            }

            return !$this->database->hasRdsAccess();
        }
    }
}