<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 10/24/2016
 * Time: 12:53 PM
 */
namespace Minute\Tracker {

    use Minute\Config\Config;
    use Minute\Http\HttpRequestEx;
    use Minute\Http\HttpResponseEx;

    class Tracker {
        //tracking cookies
        const HTTP_CAMPAIGN_COOKIE = "http_campaign";
        const HTTP_REFERRER_COOKIE = "http_referrer";

        /**
         * @var HttpResponseEx
         */
        private $response;
        /**
         * @var Config
         */
        private $config;
        /**
         * @var HttpRequestEx
         */
        private $request;

        /**
         * Tracker constructor.
         *
         * @param HttpRequestEx $request
         * @param HttpResponseEx $response
         * @param Config $config
         */
        public function __construct(HttpRequestEx $request, HttpResponseEx $response, Config $config) {
            $this->request  = $request;
            $this->response = $response;
            $this->config   = $config;
        }

        public function track() {
            if (empty($_COOKIE[self::HTTP_REFERRER_COOKIE]) && ($referrer = getenv('HTTP_REFERER'))) {
                $uri    = parse_url($referrer);
                $domain = preg_quote($this->config->getPublicVars('domain'));
                $value  = !preg_match("/$domain/", $uri['host']) ? sprintf('%s||%s', $this->request->getPath(), $referrer) : '/';

                $this->response->setCookie(self::HTTP_REFERRER_COOKIE, $value, '+1 year');
            }

            if (empty($_COOKIE[self::HTTP_CAMPAIGN_COOKIE]) && ($cmp = $this->request->getParameter('_cmp'))) {
                $this->response->setCookie(self::HTTP_CAMPAIGN_COOKIE, $cmp, '+1 year');
            }
        }
    }
}