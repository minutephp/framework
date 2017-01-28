<?php

namespace Test\Event {

    use Auryn\Injector;
    use Minute\Event\Binding;
    use Minute\Event\Event;

    class BindingTest extends \PHPUnit_Framework_TestCase {
        protected function setUp() {
            parent::setUp();

            define('MODEL_DIR', 'Test\Model');
        }

        public function testDefaultListeners() {
            $name = 'some.event.name';
            $data = 'some.data';

            $mock = $this->getMockBuilder('SomeClass')->setMethods(['method'])->getMock();
            $mock->expects($this->once())->method('method')->with($this->callback(function (Event $payload) use ($name, $data) {
                return $payload->getName() === $name && $payload->getData() === $data;
            }));

            $listeners = [['event' => $name, 'handler' => [$mock, 'method'], 'priority' => -99, 'data' => $data]];

            /** @var Binding $binding */
            $binding   = (new Injector())->make('Minute\Event\Binding', [':defaultListeners' => $listeners]);
            $listeners = $binding->getBindings($name);

            foreach ($listeners as $listener) {
                $listener(new Event());
            }
        }

        public function testAddListeners() {
            $mock = new class {
                public static function all() {
                    $data = new class {
                        public function attributesToArray() {
                            return ['event' => 'a', 'handler' => 1];
                        }
                    };

                    return [$data, $data];
                }
            };

            class_alias(get_class($mock), 'Test\Model\MEvent');

            /** @var Binding $binding */
            $binding   = (new Injector())->make('Minute\Event\Binding', [':defaultListeners' => []]);
            $listeners = $binding->getBindings('a');

            $this->assertCount(2, $listeners, 'Listeners are added from Events model');
        }
    }
}