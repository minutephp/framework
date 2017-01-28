<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 4:27 PM
 */
namespace Minute\App {

    use Http\HttpRequest;
    use League\Flysystem\Exception;
    use Minute\Database\Database;
    use Minute\Debug\Debugger;
    use Minute\Error\AuthError;
    use Minute\Error\BasicError;
    use Minute\Error\PhpError;
    use Minute\Error\PrintableError;
    use Minute\Event\AppEvent;
    use Minute\Event\Binding;
    use Minute\Event\Dispatcher;
    use Minute\Event\ExceptionEvent;
    use Minute\Event\RequestEvent;
    use Minute\Event\ResponseEvent;
    use Minute\Http\HttpRequestEx;
    use Minute\Http\HttpResponseEx;
    use Minute\Log\LoggerEx;
    use Symfony\Component\Routing\Exception\ResourceNotFoundException;

    class App {
        /**
         * @var bool
         */
        protected $init = false;
        /**
         * @var HttpRequest
         */
        private $request;
        /**
         * @var Dispatcher
         */
        private $dispatcher;
        /**
         * @var Binding
         */
        private $binding;
        /**
         * @var Database
         */
        private $database;
        /**
         * @var Debugger
         */
        private $debug;
        /**
         * @var LoggerEx
         */
        private $logger;
        /**
         * @var HttpResponseEx
         */
        private $response;

        /**
         * App constructor.
         *
         * @param HttpRequestEx $request
         * @param HttpResponseEx $response
         * @param Dispatcher $dispatcher
         * @param Binding $binding
         * @param Database $database
         * @param Debugger $debug
         * @param LoggerEx $logger
         */
        public function __construct(HttpRequestEx $request, HttpResponseEx $response, Dispatcher $dispatcher, Binding $binding, Database $database, Debugger $debug,
                                    LoggerEx $logger) {
            $this->request    = $request;
            $this->response   = $response;
            $this->dispatcher = $dispatcher;
            $this->binding    = $binding;
            $this->database   = $database; //for initialize
            $this->debug      = $debug;
            $this->logger     = $logger;
        }

        public function boot() {
            if (!$this->init) {
                define('APP_START_TIME', microtime(true));

                $this->init = true;
                $this->binding->bind();

                $event = new AppEvent($this);
                $this->dispatcher->fire(AppEvent::APP_INIT, $event);
            }
        }

        public function run() {
            try {
                $this->boot();

                $event = new RequestEvent($this->request);
                $this->dispatcher->fire(RequestEvent::REQUEST_HANDLE, $event);
                $this->response = $event->getResponse();
            } catch (AuthError $e) {
                $this->response->setStatusCode(401);
            } catch (ResourceNotFoundException $e) {
                $this->response->setStatusCode(404);
            } catch (\Throwable $e) {
                http_response_code(500);
                $this->response->setStatusCode(500);

                if ($e instanceof PrintableError) {
                    $this->response->setContent($e->getMessage());
                } else {
                    if (preg_match("/Duplicate entry '(.*?)' for key '(.*?)'/", $e->getMessage(), $matches)) {
                        $errorStr = sprintf("DUPLICATE: %s ('%s' is already in use).", ucfirst($matches[2]), $matches[1]);
                    } else {
                        if ($this->debug->enabled()) {
                            throw $e;
                        } else {
                            $event = new ExceptionEvent($e);
                            $this->dispatcher->fire(ExceptionEvent::EXCEPTION_UNHANDLED, $event);
                        }

                        if (preg_match("/Unknown column 'user_id'.*from (.*?) /", $e->getMessage(), $matches)) {
                            $errorStr = sprintf("Permission::SAME_USER requires a `user_id` column (which is missing in table $matches[1])");
                        } else {
                            $errorStr = 'Internal server error';
                        }
                    }

                    $this->response->setContent($errorStr);
                }
            }

            if ($this->response->getStatusCode() === 200) {
                $this->dispatcher->fire(ResponseEvent::RESPONSE_RENDER, new ResponseEvent($this->response));
            } else {
                $this->dispatcher->fire(ResponseEvent::RESPONSE_ERROR, new ResponseEvent($this->response));
            }
        }
    }
}