<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/16/2016
 * Time: 6:38 PM
 */
namespace Minute\Log {

    use Minute\Error\BasicError;
    use Minute\Event\Dispatcher;
    use Minute\Event\ExceptionEvent;
    use Monolog\Handler\HandlerInterface;
    use Monolog\Logger;

    class LoggerEx extends Logger {
        /**
         * @var Dispatcher
         */
        private $dispatcher;

        /**
         * @param string $name                 The logging channel
         * @param HandlerInterface[] $handlers Optional stack of handlers, the first one in the array is called first, etc.
         * @param callable[] $processors       Optional array of processors
         * @param Dispatcher $dispatcher
         */
        public function __construct(string $name = 'debug', array $handlers = array(), array $processors = array(), Dispatcher $dispatcher) {
            parent::__construct($name, $handlers, $processors);

            $this->dispatcher = $dispatcher;
        }

        public function addRecord($level, $message, array $context = array()) {
            if ($level >= self::ERROR) {
                try { //we call LoggerEx->error or LoggerEx->critical, when we want to raise an error but still want to keep running our code
                    throw new BasicError($message, $level >= self::CRITICAL ? BasicError::CRITICAL : ($level >= self::ERROR ? BasicError::ERROR : BasicError::WARNING));
                } catch (\Throwable $e) {
                    $event = new ExceptionEvent($e);
                    $this->dispatcher->fire(ExceptionEvent::EXCEPTION_UNHANDLED, $event);
                }
            }

            return parent::addRecord($level, $message, $context);
        }
    }
}