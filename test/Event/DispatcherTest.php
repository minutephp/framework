<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/14/2016
 * Time: 1:47 AM
 */

namespace Test\Event {

    use Auryn\Injector;
    use Minute\Event\Dispatcher;
    use Minute\Event\Event;

    class DispatcherTest extends \PHPUnit_Framework_TestCase {

        public function testDispatcherCallsListenersWithData() {
            $name  = 'some.event.name';
            $data1 = 'additional.data.1';
            $data2 = 'additional.data.2';

            $class   = $this->getMockBuilder('SomeClass')->setMethods(['trigger1', 'trigger2'])->getMock();
            $payload = new Event();

            $class->expects($this->once())->method('trigger1')->with($payload);
            $class->expects($this->once())->method('trigger2')->with($this->callback(function (Event $payload) use ($name, $data2) {
                return $payload->getName() === $name && $payload->getData() === $data2;
            }));

            /** @var Dispatcher $dispatcher */
            $dispatcher = (new Injector())->make('Minute\Event\Dispatcher');
            $dispatcher->listen($name, [$class, 'trigger1'], 0, $data1);
            $dispatcher->listen($name, [$class, 'trigger2'], 0, $data2);
            $dispatcher->fire($name, $payload);
        }
    }
}