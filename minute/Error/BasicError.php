<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 9/4/2016
 * Time: 7:17 AM
 */
namespace Minute\Error {

    use Exception;

    class BasicError extends \Error {
        const WARNING   = 'warning';
        const ERROR     = 'error';
        const CRITICAL  = 'critical';
        const EMERGENCY = 'emergency';

        /**
         * @var string
         */
        private $severity;

        /**
         * BasicError constructor.
         *
         * @param string $message
         * @param string $severity
         * @param int $code
         * @param Exception $previous
         */
        public function __construct(string $message, string $severity = self::ERROR, int $code = 0, $previous = null) {
            parent::__construct($message, $code, $previous);

            $this->severity = $severity;
        }

        /**
         * @return string
         */
        public function getSeverity(): string {
            return $this->severity;
        }
    }
}