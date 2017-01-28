<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 6:21 PM
 */
namespace Minute\Event {

    use Auryn\Injector;
    use Illuminate\Contracts\Container\Container as ContainerContract;
    use Illuminate\Events\Dispatcher as LaravelDispatcher;

    class Dispatcher extends LaravelDispatcher {
        /**
         * @var Injector
         */
        private $injector;

        /**
         * Dispatcher constructor.
         *
         * @param ContainerContract|null $container
         * @param Injector $injector
         */
        public function __construct(ContainerContract $container = null, Injector $injector) {
            parent::__construct($container);
            $this->injector = $injector;
        }

        public function listen($events, $listener, $priority = 0, $data = null) {
            $wrapper = function ($payload) use ($events, $listener, $data) {
                if (!empty($data) && ($payload instanceof Event)) {
                    $payload->setData($data);
                }

                if (is_array($listener) && count($listener) === 2 && is_string($listener[0])) {
                    $object   = $this->injector->make($listener[0]);
                    $listener = [$object, $listener[1]];
                }

                if (is_callable($listener)) {
                    return call_user_func($listener, $payload);
                } else {
                    trigger_error("Listener is not callable: " . $listener);

                    return false;
                }
            };

            parent::listen($events, $wrapper, $priority);
        }

        public function fire($event, $payload = [], $halt = false) {
            if ($payload instanceof Event) {
                $payload->setName($event);
            }

            return parent::fire($event, $payload, $halt);
        }
    }
}