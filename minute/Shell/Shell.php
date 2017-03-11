<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 8/1/2016
 * Time: 8:34 PM
 */
namespace Minute\Shell {

    use Minute\Debug\Debugger;
    use Minute\Log\LoggerEx;

    class Shell {
        /**
         * @var LoggerEx
         */
        private $loggerEx;
        /**
         * @var Debugger
         */
        private $debugger;

        /**
         * Shell constructor.
         *
         * @param LoggerEx $loggerEx
         * @param Debugger $debugger
         */
        public function __construct(LoggerEx $loggerEx, Debugger $debugger) {
            $this->loggerEx = $loggerEx;
            $this->debugger = $debugger;

            set_time_limit(0);
        }

        public function run(...$args) {
            $cmd    = $this->compile($args);
            $output = system("$cmd 2>&1", $code);

            return ['code' => $code, 'output' => $output];
        }

        public function capture(...$args) {
            $cmd    = $this->compile($args);
            $output = `$cmd 2>&1`;

            return $output;
        }

        public function raw(...$args) {
            $cmd    = $this->compile($args);
            $output = system($cmd, $code);

            return ['code' => $code, 'output' => $output];
        }

        public function exec(...$args) {
            $cmd = $this->compile($args);

            exec("$cmd 2>&1", $output, $code);

            return ['code' => $code, 'output' => $output];
        }

        public function background(...$args) {
            $cmd = $this->compile($args);
            pclose(popen($cmd, 'r'));

            return true;
        }

        protected function compile($args) {
            $cmd = call_user_func_array('sprintf', $args);

            if ($this->debugger->enabled()) {
                $this->loggerEx->debug($cmd);
            }

            return $cmd;
        }
    }
}