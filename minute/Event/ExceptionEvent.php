<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 10/4/2016
 * Time: 5:23 AM
 */
namespace Minute\Event {

    use Throwable;

    class ExceptionEvent extends Event {
        const EXCEPTION_UNHANDLED = "exception.unhandled";
        /**
         * @var Throwable
         */
        private $error;

        /**
         * ExceptionEvent constructor.
         *
         * @param Throwable $error
         */
        public function __construct(Throwable $error) {
            $this->error = $error;
        }

        /**
         * @return Throwable
         */
        public function getError(): Throwable {
            return $this->error;
        }

    }
}