<?php

namespace Test\Resolver {

    use Minute\Model\ModelEx;
    use Minute\Resolver\Resolver;

    class ResolverTest extends \PHPUnit_Framework_TestCase {
        /**
         * Sets up the fixture, for example, open a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp() {
            parent::setUp();

            define('MODEL_DIR', 'Test\Model');
            define('CONTROLLER_DIR', 'Test\Controller');
            define('VIEW_DIR', 'Test\View\Sample');
            define('ROUTE_DIR', 'Test\Route');
        }

        public function testGetModel() {
            $class = (new Resolver())->getModel('DoesNotExists');
            $this->assertFalse($class, 'Class must not exist');

            $mock = new class extends ModelEx {
            };

            class_alias(get_class($mock), '\Test\Model\ModelThatExist');

            $class = (new Resolver())->getModel('ModelThatExists'); //plural!
            $this->assertEquals('\Test\Model\ModelThatExist', $class, 'ModelThatExist must exist');
        }

        public function testGetController() {
            $class = (new Resolver())->getController('DoesNotExists');
            $this->assertFalse($class, 'Controller class must not exist');

            $mock = new class {
            };

            class_alias(get_class($mock), '\Test\Controller\ControllerThatExists');

            $class = (new Resolver())->getController('ControllerThatExists');
            $this->assertEquals('\Test\Controller\ControllerThatExists', $class, '\Test\Controller\ControllerThatExists was found');

            class_alias(get_class($mock), '\Test\Controller\Admin\Stocks\AnotherControllerThatExists');

            $class = (new Resolver())->getController('\Admin\Stocks\AnotherControllerThatExists');
            $this->assertEquals('\Test\Controller\Admin\Stocks\AnotherControllerThatExists', $class, '\Test\Controller\Admin\Stocks\AnotherControllerThatExists was found');

            $class = (new Resolver())->getController('Admin\Stocks\AnotherControllerThatExists');
            $this->assertEquals('\Test\Controller\Admin\Stocks\AnotherControllerThatExists', $class, '\Test\Controller\Admin\Stocks\AnotherControllerThatExists was found (without starting slash)');
        }

        public function testGetView() {
            $class = (new Resolver())->getView('\Test\View\DoesNotExists');
            $this->assertFalse($class, 'View must not exist');

            $class = (new Resolver())->getView('A\B\C');
            $this->assertFileExists($class, 'View "c" must exist');
        }

        public function testGetRoutes() {
            $routes = (new Resolver())->getRoutes();
            $route  = realpath(__DIR__ . '/../route/routes.php');
            $this->assertContains($route, $routes, 'Route file was found');
        }

        public function testFind() {
            $folders = (new Resolver())->find('\Test\Controller');
            $this->assertCount(1, $folders, 'Found the controller folders');

            $folders = (new Resolver())->find('\Test\Resolver\ResolverTest.php');
            $this->assertCount(1, $folders, 'Found the file in folders');

            $folders = (new Resolver())->find('\Test\DoesNotExists');
            $this->assertCount(0, $folders, 'Nothing should be found');
        }
    }
}