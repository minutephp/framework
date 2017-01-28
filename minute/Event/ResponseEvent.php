<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 6:18 PM
 */
namespace Minute\Event {

    use Minute\Http\HttpResponseEx;
    use Minute\View\Redirection;

    class ResponseEvent extends Event {
        const RESPONSE_RENDER = "response.render";
        const RESPONSE_ERROR  = "response.error";
        /**
         * @var string|Redirection
         */
        private $response;
        /**
         * @var bool
         */
        private $ajaxRequest;

        /**
         * ResponseEvent constructor.
         *
         * @param string|Redirection $response
         */
        public function __construct($response) {
            $this->response    = $response;
            $this->ajaxRequest = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null === 'XMLHttpRequest';
        }

        /**
         * @return boolean
         */
        public function isAjaxRequest(): bool {
            return $this->ajaxRequest;
        }

        /**
         * @param boolean $ajaxRequest
         *
         * @return ResponseEvent
         */
        public function setAjaxRequest(bool $ajaxRequest): ResponseEvent {
            $this->ajaxRequest = $ajaxRequest;

            return $this;
        }

        /**
         * @return HttpResponseEx|Redirection
         */
        public function getResponse() {
            return $this->response;
        }

        /**
         * @param HttpResponseEx|Redirection $response
         *
         * @return ResponseEvent
         */
        public function setResponse($response) {
            $this->response = $response;

            return $this;
        }

        public function isSimpleHtmlResponse() {
            if ($this->response instanceof HttpResponseEx) {
                return ((@$_SERVER['REQUEST_METHOD'] === 'GET') && !$this->response->isFinal() && !$this->isAjaxRequest());
            }

            return false;
        }
    }
}