<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/18/2016
 * Time: 1:04 PM
 */

namespace Minute\Http {

    use Http\HttpResponse;
    use Minute\Config\Config;
    use Minute\View\Redirection;

    class HttpResponseEx extends HttpResponse {
        /**
         * @var Config
         */
        private $config;
        /*
         * @var book
         */
        private $final = false;
        /**
         * @var HttpRequestEx
         */
        private $request;

        /**
         * HttpResponseEx constructor.
         *
         * @param Config $config
         * @param HttpRequestEx $request
         */
        public function __construct(Config $config, HttpRequestEx $request) {
            $this->config  = $config;
            $this->request = $request;
        }

        /**
         * @return boolean
         */
        public function isFinal(): bool {
            return $this->final;
        }

        /**
         * @param boolean $final
         *
         * @return HttpResponseEx
         */
        public function setFinal(bool $final): HttpResponseEx {
            $this->final = $final;

            return $this;
        }

        public function setCookie(string $name, $value, string $expires = '+1 day', $httpOnly = false) {
            unset($_COOKIE[$name]);

            $domain = $this->config->getPublicVars('domain') ?: 'localhost';
            $expiry = $value === null ? -1 : (is_numeric($expires) ? time() + $expires : strtotime($expires));
            $status = setcookie($name, $value ?? '', $expiry, '/', !empty($domain) ? '.' . $domain : null, null, $httpOnly);

            if ((!empty($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], $domain) === false)) || (preg_match('/localhost/', $domain))) {
                $status = setcookie($name, $value ?? '', $expiry, '/', null, null, $httpOnly);
            }

            if ($status && ($expiry > time())) {
                $_COOKIE[$name] = $value;
            }
        }

        public function redirect($url, int $code = 302) {
            parent::redirect($url);
            $this->setStatusCode($code);
        }

        public function asFile($fileName, $mime = 'application/octet-stream') {
            header("Content-type: $mime");
            header("Content-Disposition: attachment; filename=$fileName");
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        public function getLoginRedirect(string $reason, bool $return = false, string $url = '', string $to = '') {
            $query = http_build_query(['redir' => $to ?: $this->request->getUri(), 'msg' => $reason]);
            $url   = sprintf('%s?%s', $url ?: $this->config->get('private/urls/login', '/login'), $query);

            if (!$return) {
                $this->setCookie('redir', $url);
                $redir = new Redirection($url);
                $redir->redirect();
            }

            return $url;
        }
    }
}