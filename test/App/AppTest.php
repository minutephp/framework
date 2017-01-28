<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 5:02 PM
 */

namespace Test\App {

    use Auryn\Injector;
    use Http\HttpRequest;
    use Http\HttpResponse;
    use Minute\App\App;
    use Minute\Event\Dispatcher;
    use Minute\Event\RequestEvent;
    use Minute\Event\ResponseEvent;
    use Minute\Http\HttpRequestEx;
    use Minute\Routing\Router;
    use PHPUnit_Framework_TestCase;

    class AppTest extends PHPUnit_Framework_TestCase {

        public function testAppRunHandlerFiresRequestAndResponseEvents() {
            $request    = $this->getMockBuilder(HttpRequestEx::class)->disableOriginalConstructor()->setMethods(['getMethod', 'getPath'])->getMock();
            $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->setMethods(['fire'])->getMock();

            $dispatcher->expects($this->exactly(2))->method('fire')->withConsecutive(
                [RequestEvent::REQUEST_HANDLE, $this->isInstanceOf(RequestEvent::class)],
                [ResponseEvent::RESPONSE_RENDER, $this->isInstanceOf(ResponseEvent::class)]
            );

            /** @var App $app */
            $app = (new Injector())->make('Minute\App\App', [':request' => $request, ':dispatcher' => $dispatcher]);
            $app->run();
        }
    }
}