<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 6:42 PM
 */

namespace Test\Routing {

    use Auryn\Injector;
    use Http\HttpResponse;
    use Illuminate\Database\Eloquent\Collection;
    use Minute\Database\Database;
    use Minute\Error\ModelError;
    use Minute\Error\RouterError;
    use Minute\Error\ValidationError;
    use Minute\Event\AuthEvent;
    use Minute\Event\ControllerEvent;
    use Minute\Event\Dispatcher;
    use Minute\Event\RedirectEvent;
    use Minute\Event\RequestEvent;
    use Minute\Http\HttpRequestEx;
    use Minute\Http\HttpResponseEx;
    use Minute\Model\ModelEx;
    use Minute\Model\Permission;
    use Minute\Routing\RouteEx;
    use Minute\Routing\Router;
    use Minute\View\Redirection;
    use stdClass;

    class RouterTest extends \PHPUnit_Framework_TestCase {

        protected function setUp() {
            global $mocks;
            parent::setUp();

            define('MODEL_DIR', '\Test\Model');

            if (!class_exists("\\Test\\Model\\ModelMock")) {
                $mock = new class extends ModelEx {
                    protected $guarded = [];

                    public function __construct($data = []) {
                        global $mocks;

                        parent::__construct($data);

                        if (!empty($data) && !empty($mocks['create'])) {
                            call_user_func($mocks['create'], $data);
                        }
                    }

                    public static function where() {
                        global $mocks;

                        if (!empty($mocks['where'])) {
                            return call_user_func_array($mocks['where'], func_get_args());
                        } else {
                            return call_user_func_array(['parent', 'where'], func_get_args());
                        }
                    }
                };

                class_alias(get_class($mock), "\\Test\\Model\\ModelMock");
            }

            $mocks = [];
        }

        public function testMatchingRouteIsReturned() {
            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router', []);
            $route  = $router->get('/foo/{id}');
            $route->addDefaults(['bar' => 'baz']);
            $route->addDefaults(['id' => null]);

            $this->assertInstanceOf(RouteEx::class, $route, 'Return value must be Minute\Routing\RouteEx');

            $route = $router->match('GET', '/foo/bar');
            $match = $route->getDefaults();
            $this->assertEquals(['controller' => null, 'auth' => false, 'models' => [], 'bar' => 'baz', 'id' => 'bar', '_route' => '/foo/{id}',], $match, 'Found matching route');

            $route = $router->match('GET', '/foo');
            $match = $route->getDefaults();
            $this->assertEquals(['controller' => null, 'auth' => false, 'models' => [], 'bar' => 'baz', 'id' => null, '_route' => '/foo/{id}',], $match, 'Id should be null');
        }

        public function testRouterPageNotFound() {
            $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->setMethods(['fire'])->getMock();

            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router', [':dispatcher' => $dispatcher]);
            $router->get('/foo/{id}');
            $router->get('/protected', null, true);

            /** @var HttpResponse $response */
            $event = new RequestEvent(new HttpRequestEx($_GET, $_POST, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/foo/bar']));
            $router->handle($event);
            $this->assertInstanceOf(HttpResponseEx::class, $event->getResponse(), 'Router handler must return \Minute\Http\HttpResponse');
            $this->assertEquals(200, $event->getResponse()->getStatusCode(), 'Router must return status code 200');

            $event = new RequestEvent(new HttpRequestEx($_GET, $_POST, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/protected']));
            $router->handle($event);
            $this->assertEquals(403, $event->getResponse()->getStatusCode(), 'Router must return Forbidden (403)');

            $event = new RequestEvent(new HttpRequestEx($_GET, $_POST, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']));
            $router->handle($event);
            $this->assertEquals(404, $event->getResponse()->getStatusCode(), 'Router must return Page not found (404)');
        }

        public function testRouterCanHandleRedirects() {
            /** @var Redirection $redirectTo */
            $redirectTo = null;

            $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->setMethods(['fire'])->getMock();
            $dispatcher->expects($this->any())->method('fire')->willReturnCallback(function (string $name, $event) use (&$redirectTo) {
                if (($name === ControllerEvent::CONTROLLER_EXECUTE) && ($event instanceof ControllerEvent)) {
                    $event->setOutput(new Redirection('/home', [], false));
                } elseif (($name === RedirectEvent::REDIRECT) && ($event instanceof RedirectEvent)) {
                    $redirectTo = $event->getRedirection();
                }

                return null;
            });

            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router', [':dispatcher' => $dispatcher]);
            $router->get('/redirect');

            /** @var HttpResponse $response */
            $event = new RequestEvent(new HttpRequestEx($_GET, $_POST, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/redirect']));
            $router->handle($event);

            $this->assertEquals('/home', $redirectTo->getRedirectUrl(), 'Redirection is handled properly');
        }

        public function testControllerIsReturned() {
            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router', []);
            $router->get('/foo/{id}', 'SomeController', true, 'modelOne', 'modelTwo');

            $route = $router->match('GET', '/foo/bar');
            $match = $route->getDefaults();
            $this->assertEquals(['controller' => 'SomeController', 'auth' => true, 'models' => ['modelOne', 'modelTwo'], 'id' => 'bar', '_route' => '/foo/{id}'], $match, 'Found matching route');
        }

        public function testCanAccessModels() {
            $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->setMethods(['fire'])->getMock();
            $dispatcher->expects($this->any())->method('fire')->willReturnCallback(function (string $name, $event) use (&$redirectTo) {
                if ($name === AuthEvent::AUTH_CHECK_ACCESS) {
                    /** @var AuthEvent $event */
                    if ($event->getLevel() !== 'admin') {
                        $event->setActiveUserId(1);
                        $event->setAuthorized(true);
                    }
                }

                return null;
            });

            $this->mockModel('ModelOne');

            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router', [':dispatcher' => $dispatcher]);
            $router->get('/everyone', 'SomeController', false, 'modelOne')->setReadPermission('modelOne', Permission::EVERYONE);
            $router->get('/same-user', 'SomeController', true, 'modelOne');
            $router->get('/members', 'SomeController', true, 'modelOne')->setReadPermission('modelOne', 'member');
            $router->get('/guest', 'SomeController', false, 'modelOne')->setReadPermission('modelOne', 'admin');

            $event = new RequestEvent(new HttpRequestEx($_GET, $_POST, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/everyone']));
            $router->handle($event);

            $event = new RequestEvent(new HttpRequestEx($_GET, $_POST, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/same-user']));
            $router->handle($event);

            $event = new RequestEvent(new HttpRequestEx($_GET, $_POST, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/members']));
            $router->handle($event);

            $event = new RequestEvent(new HttpRequestEx($_GET, $_POST, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/guest']));
            $this->expectException(ModelError::class);
            $router->handle($event);
        }

        public function testCannotAccessModel() {
            $this->mockModel('ModelTwo');

            $router = (new Injector())->make('Minute\Routing\Router', []);
            $router->get('/fail/{id}', 'SomeController', false, 'modelTwo');

            $event = new RequestEvent(new HttpRequestEx($_GET, $_POST, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/fail/bar']));
            $this->expectException(ModelError::class);
            $router->handle($event);
        }

        public function testGetCanHandleMetadata() {
            $this->mockModel('ModelOne', 'ModelTwo');
            $search = ['columns' => 'test', 'operator' => '=', 'value' => '1'];

            $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->setMethods(['fire'])->getMock();
            $dispatcher->expects($this->any())->method('fire')->willReturnCallback(function ($name, $event) use ($search) {
                /** @var ControllerEvent $event */
                if ($name == ControllerEvent::CONTROLLER_EXECUTE) {
                    $parents = $event->getParams(true)['_parents'];
                    $parent  = $parents['one']['self'];
                    $this->assertEquals(3, $parent['pk'], 'Pk set successfully');

                    $child = $parents['one']['children']['modelTwo']['self'];
                    $this->assertEquals(10, $child['limit'], 'Child limit is set');
                    $this->assertEquals(3, $child['offset'], 'Child limit is set');
                    $this->assertEquals($search, $child['search'], 'Search criteria is set');
                }
            });

            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router', [':dispatcher' => $dispatcher]);
            $router->get('/test/{mock_id}', null, false, 'modelOne[mock_id] as one', 'modelTwo[one.model_id]')->setReadPermission('one', Permission::EVERYONE);

            $get   = ['metadata' => json_encode(['one' => ['pk' => 3], 'modelTwo' => ['offset' => 3, 'limit' => 10, 'search' => $search]])];
            $event = new RequestEvent(new HttpRequestEx($get, $_POST, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test/1', 'HTTP_ACCEPT' => 'application/json']));
            $router->handle($event);
        }

        public function testPost() {
            /** @var Router $router */
            $router  = (new Injector())->make('Minute\Routing\Router');
            $request = new HttpRequestEx($_GET, $_POST, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']);

            $this->expectException(RouterError::class);

            $router->post('/test', null, false);
            $router->handle(new RequestEvent($request));
        }

        public function testRouterAssignsDefaultPostHandlerWhenControllerIsNull() {
            $this->mockModel('ModelOne', 'ModelTwo');
            $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->setMethods(['fire'])->getMock();
            $dispatcher->expects($this->any())->method('fire')->willReturnCallback(function ($name, $event) {
                if ($name == ControllerEvent::CONTROLLER_EXECUTE) {
                    /** @var ControllerEvent $event */
                    $this->assertEquals(['Generic/DefaultPostHandler.php', 'index'], $event->getAction(), 'Controller is default post handler');
                    $this->assertCount(2, $event->getParams(true)['_parents'], 'Controller has correct models');
                }
            });

            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router', [':dispatcher' => $dispatcher]);
            $post   = ['alias' => 'two'];
            $event  = new RequestEvent(new HttpRequestEx($_GET, $post, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']));

            $router->post('/test', null, false, 'ModelTwo', 'ModelOne[first_name, last_name,email] as two');
            $router->handle($event);
        }

        public function testPostHandlerCreatePermissions() {
            global $mocks;
            $mocks['create'] = function () { };
            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router');
            $router->post('/test', null, false, 'ModelMock[ignore] as two')->setCreatePermission('two', Permission::EVERYONE);

            $post  = ['alias' => 'two', 'items' => json_encode([['ignore' => 1]])];
            $event = new RequestEvent(new HttpRequestEx($_GET, $post, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']));
            $router->handle($event);

            $post  = ['alias' => 'two', 'items' => json_encode([['ignore' => 1], ['id' => 1]])];
            $event = new RequestEvent(new HttpRequestEx($_GET, $post, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']));
            $this->expectException(ModelError::class);
            $router->handle($event);
        }

        public function testPostFieldRestrictions() {
            global $mocks;
            $mocks['create'] = function () { };

            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router');
            $router->post('/test', null, false, 'ModelMock[first_name, last_name,email] as two')->setCreatePermission('two', Permission::EVERYONE);

            $post  = ['alias' => 'two', 'items' => json_encode([['first_name' => 1, 'email' => 1]])];
            $event = new RequestEvent(new HttpRequestEx($_GET, $post, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']));
            $router->handle($event);

            $post  = ['alias' => 'two', 'items' => json_encode([['password' => 1, 'first_name' => 1, 'last_name' => 1, 'email' => 1]])];
            $event = new RequestEvent(new HttpRequestEx($_GET, $post, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']));
            $this->expectException(ValidationError::class);
            $router->handle($event);
        }

        public function testPostModelCreateFromRequest() {
            global $mocks;
            $mocks['create'] = function ($data) {
                $this->assertEquals($data, ['first_name' => 'san', 'email' => 'san@man']);
            };

            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router');
            $router->post('/test', null, false, 'ModelMock[first_name, last_name,email] as two')->setCreatePermission('two', Permission::EVERYONE);

            $post  = ['alias' => 'two', 'items' => json_encode([['first_name' => 'san', 'email' => 'san@man']])];
            $event = new RequestEvent(new HttpRequestEx($_GET, $post, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']));
            $router->handle($event);
        }

        public function testPostModelUpdateFailWhenUserIdOfRecordIsDifferentFromLoggedInUserId() {
            global $mocks;

            $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->setMethods(['fire'])->getMock();
            $dispatcher->expects($this->any())->method('fire')->willReturnCallback(function (string $name, $event) use (&$redirectTo) {
                if (($name === AuthEvent::AUTH_CHECK_ACCESS) && ($event instanceof AuthEvent)) {
                    $event->setAuthorized(true);
                    $event->setActiveUserId(5);
                }
            });

            $mockRecord          = new stdClass();
            $mockRecord->user_id = 3;

            $mocks['where'] = function ($col, $op, $val) use ($mockRecord) {
                $this->assertEquals(['id', '=', '3'], [$col, $op, $val], 'Correct where called');

                return new Collection([$mockRecord]);
            };

            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router', [':dispatcher' => $dispatcher]);
            $router->post('/test', null, false, 'ModelMock[first_name, last_name,email] as two')->setUpdatePermission('two', Permission::SAME_USER);

            $post  = ['alias' => 'two', 'items' => json_encode([['first_name' => 'san', 'email' => 'san@man', 'id' => 3]])];
            $event = new RequestEvent(new HttpRequestEx($_GET, $post, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']));
            $this->expectException(ModelError::class);
            $router->handle($event);
        }

        public function testModelUpdatePassWhenUserIdMatches() {
            global $mocks;

            $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->setMethods(['fire'])->getMock();
            $dispatcher->expects($this->any())->method('fire')->willReturnCallback(function (string $name, $event) use (&$redirectTo) {
                if (($name === AuthEvent::AUTH_CHECK_ACCESS) && ($event instanceof AuthEvent)) {
                    $event->setAuthorized(true);
                    $event->setActiveUserId(5);
                }
            });

            $mockRecord          = new stdClass();
            $mockRecord->user_id = 5;

            $mocks['where'] = function ($col, $op, $val) use ($mockRecord) {
                $this->assertEquals(['id', '=', '3'], [$col, $op, $val], 'Correct where called');

                return new Collection([$mockRecord]);
            };

            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router', [':dispatcher' => $dispatcher]);
            $router->post('/test', null, false, 'ModelMock[first_name, last_name,email] as two')->setUpdatePermission('two', Permission::SAME_USER);

            $post  = ['alias' => 'two', 'items' => json_encode([['first_name' => 'san', 'email' => 'san@man', 'id' => 3]])];
            $event = new RequestEvent(new HttpRequestEx($_GET, $post, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']));
            $router->handle($event);

            $this->assertEquals($mockRecord->first_name, 'san');
            $this->assertEquals($mockRecord->email, 'san@man');
            $this->assertEquals($mockRecord->user_id, 5);
        }

        public function testModelCreateAndUpdateInSameRequest() {
            global $mocks;

            $database = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->setMethods(['getColumns'])->getMock();
            $database->expects($this->any())->method('getColumns')->will($this->returnValue(['first_name', 'email', 'user_id']));

            $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->setMethods(['fire'])->getMock();
            $dispatcher->expects($this->any())->method('fire')->willReturnCallback(function (string $name, $event) use (&$redirectTo) {
                if (($name === AuthEvent::AUTH_CHECK_ACCESS) && ($event instanceof AuthEvent)) {
                    $event->setAuthorized(true);
                    $event->setActiveUserId(5);
                } elseif ($name == ControllerEvent::CONTROLLER_EXECUTE) {
                    /** @var ControllerEvent $event */
                    $models    = $event->getParams(true)['_models'];
                    $mockClass = '\Test\Model\ModelMock';

                    $this->assertEquals(['Generic/DefaultPostHandler.php', 'index'], $event->getAction(), 'Controller is default post handler');
                    $this->assertCount(1, $event->getParams(true)['_parents'], 'Controller has correct number of parents');
                    $this->assertCount(2, $models, 'Controller has correct number of models');
                    $this->assertTrue($models[0] instanceof $mockClass, 'Model #1 is instance of ModelMock');
                    $this->assertEquals('san', $models[1]->first_name, 'Model #2 has right attributes');
                }
            });

            $mockRecord = new stdClass();

            $mockRecord->user_id = 5;

            $mocks['create'] = function ($data) {
                $this->assertEquals(['first_name' => 'very new', 'email' => 'new@user', 'user_id' => 5], $data, 'Creation data is correct');
            };

            $mocks['where'] = function ($col, $op, $val) use ($mockRecord) {
                $this->assertEquals(['id', '=', '3'], [$col, $op, $val], 'Correct where called');

                return new Collection([$mockRecord]);
            };

            /** @var Router $router */
            $router = (new Injector())->make('Minute\Routing\Router', [':dispatcher' => $dispatcher, ':database' => $database]);
            $router->post('/test', null, false, 'ModelMock[first_name, last_name,email] as two')
                   ->setCreatePermission('two', Permission::SAME_USER)->setUpdatePermission('two', Permission::SAME_USER);

            $post  = ['alias' => 'two', 'items' => json_encode([['first_name' => 'very new', 'email' => 'new@user'], ['first_name' => 'san', 'email' => 'san@man', 'id' => 3]])];
            $event = new RequestEvent(new HttpRequestEx($_GET, $post, $_COOKIE, $_FILES, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']));
            $router->handle($event);

            $this->assertEquals($mockRecord->first_name, 'san');
            $this->assertEquals($mockRecord->email, 'san@man');
            $this->assertEquals($mockRecord->user_id, 5);
        }

        private function mockModel() {
            foreach (func_get_args() as $name) {
                $class = "\\Test\\Model\\$name";
                if (!class_exists($class)) {
                    $mock = new class extends ModelEx {
                    };
                    class_alias(get_class($mock), $class);
                }
            }
        }
    }
}