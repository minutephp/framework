<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/20/2016
 * Time: 11:11 AM
 */
namespace Minute\Render {

    use Minute\Config\Config;
    use Minute\Event\ResponseEvent;
    use Minute\Http\HttpRequestEx;
    use Minute\Http\HttpResponseEx;
    use Minute\Lang\Lang;
    use Minute\Session\Session;

    class Problem {
        /**
         * @var HttpRequestEx
         */
        private $request;
        /**
         * @var Config
         */
        private $config;
        /**
         * @var Lang
         */
        private $lang;
        /**
         * @var Session
         */
        private $session;
        /**
         * @var HttpResponseEx
         */
        private $response;

        /**
         * Problem constructor.
         *
         * @param HttpRequestEx $request
         * @param HttpResponseEx $response
         * @param Config $config
         * @param Lang $lang
         * @param Session $session
         */
        public function __construct(HttpRequestEx $request, HttpResponseEx $response, Config $config, Lang $lang, Session $session) {
            $this->request  = $request;
            $this->response = $response;
            $this->config   = $config;
            $this->lang     = $lang;
            $this->session  = $session;
        }

        public function send(ResponseEvent $event) {
            /** @var HttpResponseEx $response */
            $response = $event->getResponse();

            if ($response->getStatusCode() === 401) {
                $user_id = $this->session->getLoggedInUserId();
                $reason  = $user_id > 0 ? $this->lang->getText('Your account does not have the required authorization to view this page') :
                    $this->lang->getText('You must be logged in to view this page');

                if ($event->isAjaxRequest()) {
                    $response->setContent($reason);
                } else {
                    $url = $this->response->getLoginRedirect($reason, true);
                    $response->redirect($url, 302);
                }
            }

            if (!headers_sent()) {
                foreach ($response->getHeaders() as $header) {
                    header($header, false);
                }
            }

            echo $response->getContent() ?? sprintf("Error code: %d", $response->getStatusCode());
        }
    }
}