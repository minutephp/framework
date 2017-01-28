<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/14/2016
 * Time: 2:27 AM
 */
namespace Minute\Event {

    use Minute\Cache\QCache;
    use Minute\Database\Database;
    use Minute\Log\LoggerEx;
    use Minute\Resolver\Resolver;
    use Minute\Model\ModelEx;

    class Binding {
        protected $listeners;
        /**
         * @var Dispatcher
         */
        private $dispatcher;
        /**
         * @var Resolver
         */
        private $resolver;
        /**
         * @var Database
         */
        private $database;
        /**
         * @var QCache
         */
        private $cache;
        /**
         * @var LoggerEx
         */
        private $logger;

        /**
         * Bindings constructor.
         *
         * @param Dispatcher $dispatcher
         * @param Resolver $resolver
         * @param Database $database
         * @param QCache $cache
         * @param LoggerEx $logger
         */
        public function __construct(Dispatcher $dispatcher, Resolver $resolver, Database $database, QCache $cache, LoggerEx $logger) {
            $this->dispatcher = $dispatcher;
            $this->resolver   = $resolver;
            $this->database   = $database;
            $this->cache      = $cache;
            $this->logger     = $logger;
        }

        public function addMultiple(array $listeners) {
            array_map([$this, 'add'], $listeners);

            return $this;
        }

        public function add(array $listener) {
            $this->listeners[] = $listener;

            return $this;
        }

        /**
         * @return mixed
         */
        public function getListeners() {
            return $this->listeners;
        }

        /**
         * @param mixed $listeners
         *
         * @return Binding
         */
        public function setListeners($listeners) {
            $this->listeners = $listeners;

            return $this;
        }

        /**
         * Returns the listeners for $eventName
         *
         * @param string $eventName
         *
         * @return array
         */
        public function getBindings($eventName) {
            return $this->dispatcher->getListeners($eventName);
        }

        /**
         * Bind listeners defined in plugins, app and Database
         */
        public function bind() {
            $listeners = $this->cache->get('app-listeners', function () {
                foreach ($this->resolver->getListeners() as $file) {
                    try {
                        $binding = $this;
                        require_once($file);
                    } catch (\Throwable $e) {
                        $this->logger->warn("Unable to include $file: " . $e->getMessage());
                    }
                }

                $listeners = $this->getListeners();

                if ($this->database->isConnected()) {
                    /** @var ModelEx $eventModel */
                    if ($eventModel = $this->resolver->getModel('Event', true)) {
                        try {
                            foreach ($eventModel::all() as $item) {
                                $attrs = $item->attributesToArray();
                                list($class, $func) = @explode('@', $attrs['handler']);
                                $event = array_merge($attrs, ['event' => $attrs['name'], 'handler' => [sprintf('\\%s', ltrim($class, '\\')), $func ?? 'index']]);

                                $listeners[] = $event;
                            }
                        } catch (\Exception $e) {
                        }
                    }
                }

                return $listeners;
            }, 300);

            foreach ($listeners as $listener) {
                $this->dispatcher->listen($listener['event'], $listener['handler'], $listener['priority'] ?? 99, $listener['data'] ?? '');
            }
        }
    }
}