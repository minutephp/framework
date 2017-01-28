<?php
/**
 * User: Sanchit <dev@svift.io>
 * Date: 4/8/2016
 * Time: 11:12 AM
 */
namespace Test\Unit {

    use Auryn\Injector;
    use Error;
    use Exception;
    use Minute\Database\Database;
    use PHPUnit_Extensions_Database_DataSet_IDataSet;
    use PHPUnit_Extensions_Database_DB_IDatabaseConnection;
    use PHPUnit_Extensions_Database_TestCase;

    /**
     * Class PHPUnit_Db_Test
     *
     * Create xml dumps with command:
     *    - mysqldump --xml -t -u root %*
     *
     * @package Tests\Db
     */
    class PHPUnit_Db_Test_Base extends PHPUnit_Extensions_Database_TestCase {
        /**
         * @var \PDO
         */
        protected $pdo;
        /**
         * @var Database
         */
        protected $database;
        /**
         * @var Injector
         */
        protected $injector;

        public function testConnection() {
            $this->assertNotNull($this->pdo, 'PDO connection not found');
        }

        protected function setUp() {
            define('MODEL_DIR', '\Test\Model');
            //define('DEBUG_MODE', true);

            $this->injector = new Injector();
            $this->injector->share(Database::class);

            $this->database = $this->injector->make('Minute\Database\Database', [true]);

            try {
                $this->pdo = $this->database->getPdo();
            } catch (\PDOException $e) {
                $this->markTestSkipped('Database connection error: ' . $e->getMessage());
            }

            parent::setUp();
        }

        /**
         * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
         */
        protected function getConnection() {
            return $this->createDefaultDBConnection($this->pdo);
        }

        /**
         * Load test data from xml file
         *
         * @param string $file
         *
         * @return \PHPUnit_Extensions_Database_DataSet_MysqlXmlDataSet
         * @throws Exception
         */
        protected function loadData(string $file) {
            $fn = sprintf("%s/%s.sql", dirname($file), pathinfo($file, PATHINFO_FILENAME));

            if ($file = realpath($fn)) {
                return $this->importMysqlDump(file_get_contents($fn));
            }

            throw new Exception("File not found: $fn");
        }

        /**
         * Returns the test dataset.
         * @return PHPUnit_Extensions_Database_DataSet_IDataSet
         * @throws Error
         */
        protected function getDataSet() {
            return $this->createArrayDataSet([]);
        }

        protected function importMysqlDump($dump) {
            $results  = $this->pdo->query('select database()')->fetchAll();
            $database = $results[0][0];

            if (preg_match('/\_test$/', $database)) {
                $this->pdo->exec($dump);
            } else {
                throw new \Exception("Test database must end with \\_test: $database");
            }

            return $this->createArrayDataSet([]);
        }

    }
}