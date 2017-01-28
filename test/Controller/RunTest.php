<?php

namespace Test\Controller {

    use Auryn\Injector;
    use Minute\Controller\Runnable;
    use Minute\Event\ControllerEvent;
    use Minute\Http\HttpRequestEx;
    use Minute\View\View;

    class RunTest extends \PHPUnit_Framework_TestCase {
        protected function setUp() {
            parent::setUp();

            define('VIEW_DIR', '\Test\View\Sample');
            define('CONTROLLER_DIR', '\Test\Controller');
        }

        public function testControllerExecution() {
            $mock = new class {
                public function index() {
                    return 42;
                }
            };

            class_alias(get_class($mock), '\Test\Controller\TestController');

            $event = new ControllerEvent('TestController@index', []);
            (new Injector())->make('Minute\Controller\Runnable')->execute($event);

            $this->assertEquals(42, $event->getOutput(), 'Controller knows answer to life, the universe, and everything.');
        }

        public function testControllerWithClosureFunctions() {
            $event = new ControllerEvent(function () { return 'hello'; }, []);
            (new Injector())->make('Minute\Controller\Runnable')->execute($event);

            $this->assertEquals('hello', $event->getOutput(), 'Closure returned correct value');
        }

        public function testControllerWithParams() {
            $event = new ControllerEvent(function ($a) { return $a; }, ['a' => 'test']);
            (new Injector())->make('Minute\Controller\Runnable')->execute($event);

            $this->assertEquals('test', $event->getOutput(), 'Closure returned correct value');
        }

        public function testControllerWithDI() {
            $_GET['foo'] = 'bar-baz';

            $event = new ControllerEvent(function (HttpRequestEx $request) { return $request->getParameter('foo'); }, ['a' => 'test']);
            (new Injector())->make('Minute\Controller\Runnable')->execute($event);

            $this->assertEquals($_GET['foo'], $event->getOutput(), 'Closure returned correct value');
        }

        public function testExecuteWithView() {
            $mock = new class {
                public function index() {
                    return new View();
                }
            };

            $class = '\Test\Controller\A\B\c';
            class_alias(get_class($mock), $class);

            $event = new ControllerEvent('A\B\c', ['foo' => 'bar', 'models' => 'test']);
            /** @var Runnable $controller */
            $controller = (new Injector())->make('Minute\Controller\Runnable');
            $controller->execute($event);

            $this->assertEquals('<html><body><inner>Content</inner></body></html>', $event->getOutput(), 'Controller is able to render View()');
        }
    }
}