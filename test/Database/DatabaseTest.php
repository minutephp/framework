<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/14/2016
 * Time: 2:43 AM
 */

namespace Test\Database {

    use Auryn\Injector;
    use Minute\Database\Database;
    use Minute\Fs\Fs;

    class DatabaseTest extends \PHPUnit_Framework_TestCase {
        public function testGetDsn() {
            /** @var Database $database */
            $database = (new Injector())->make('Minute\Database\Database');

            $this->assertEquals($database->getDsn(), ['username' => 'user', 'password' => 'pass', 'host' => 'localhost', 'port' => 3306, 'database' => 'none'], 'Unable to set custom DSN');
        }

        public function testConnect() {
            /** @var Database $database */
            $database = (new Injector())->make('Minute\Database\Database');

            $connection = $database->connect();
            $this->assertInstanceOf('\Illuminate\Database\Connection', $connection, 'Connection not created');

            $this->expectException(\PDOException::class);
            $database->getPdo();
        }

        protected function setUp() {
            parent::setUp();

            putenv('PHP_CUSTOM_DSN=mysql://user:pass@localhost/none');
            #define('MODEL_DIR', 'Test\Model');
        }

        protected function tearDown() {
            putenv('PHP_CUSTOM_DSN=');

            parent::tearDown();
        }
    }
}