<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 10/10/2016
 * Time: 7:34 AM
 */

namespace Minute\Seo {

    use Minute\Cache\QCache;
    use Minute\Config\Config;
    use Minute\Event\SeoEvent;

    class SeoData {
        /**
         * @var QCache
         */
        private $cache;
        /**
         * @var Config
         */
        private $config;

        /**
         * SeoData constructor.
         *
         * @param QCache $cache
         * @param Config $config
         */
        public function __construct(QCache $cache, Config $config) {
            $this->cache  = $cache;
            $this->config = $config;
        }

        public function getData(SeoEvent $event) {
            $url    = $event->getUrl();
            $result = $this->cache->get('seo-titles', function () {
                return $this->config->get('seo');
            }, 3600);

            if (!($data = $result['titles'][$url] ?? null)) {
                if (!empty($result['titles'])) {
                    $urlRegEx = preg_replace('~/\{~', '/?{', $url);
                    $urlRegEx = preg_replace('/\{[^}]+\}/', '(.*?)', $urlRegEx);

                    foreach ($result['titles'] as $path => $pathData) {
                        if (preg_match("~$urlRegEx~", $path)) {
                            $data = $pathData;
                            break;
                        }
                    }
                }
            }

            if (!empty($data['title'])) {
                $event->setTitle($data['title']);
            }

            if (!empty($data['description'])) {
                $event->setMeta([['name' => 'description', 'content' => $data['description']]]);
            }
        }
    }
}