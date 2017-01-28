<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/20/2016
 * Time: 11:11 AM
 */
namespace Minute\Render {

    use Minute\Event\RedirectEvent;
    use Minute\Event\ResponseEvent;
    use Minute\Http\HttpResponseEx;

    class Output {
        public function send(ResponseEvent $event) {
            /** @var HttpResponseEx $response */
            $response = $event->getResponse();

            if (!headers_sent()) {
                foreach ($response->getHeaders() as $header) {
                    header($header, false);
                }
            } else {
                echo '';
            }

            echo $response->getContent();
        }

        public function redirect(RedirectEvent $event) {
            $event->getRedirection()->redirect();
        }
    }
}