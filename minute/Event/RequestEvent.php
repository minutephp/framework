<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 6:18 PM
 */
namespace Minute\Event {

    use Minute\Http\HttpRequestEx;
    use Minute\Http\HttpResponseEx;

    class RequestEvent extends Event {
        const REQUEST_HANDLE = "request.handle";

        /**
         * @var HttpResponseEx
         */
        protected $response;
        /**
         * @var HttpRequestEx
         */
        private $request;

        /**
         * RequestEvent constructor.
         *
         * @param HttpRequestEx $request
         */
        public function __construct(HttpRequestEx $request) {
            $this->request = $request;
        }

        /**
         * @return HttpResponseEx
         */
        public function getResponse() {
            return $this->response;
        }

        /**
         * @param HttpResponseEx $response
         *
         * @return RequestEvent
         */
        public function setResponse(HttpResponseEx $response) {
            $this->response = $response;

            return $this;
        }

        /**
         * @return HttpRequestEx
         */
        public function getRequest() {
            return $this->request;
        }

        /**
         * @param HttpRequestEx $request
         *
         * @return RequestEvent
         */
        public function setRequest($request) {
            $this->request = $request;

            return $this;
        }
    }
}