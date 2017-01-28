<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 7/8/2016
 * Time: 11:15 AM
 */
namespace Minute\Http {

    use GuzzleHttp\Client;
    use Minute\File\TmpDir;
    use Minute\Log\LoggerEx;
    use Minute\Utils\PathUtils;

    class Browser {
        const GUZZLE_DEFAULTS = ['connect_timeout' => 5, 'timeout' => 5, 'verify' => false];
        /**
         * @var Client
         */
        private $client;
        /**
         * @var TmpDir
         */
        private $tmpDir;
        /**
         * @var LoggerEx
         */
        private $logger;
        /**
         * @var PathUtils
         */
        private $utils;

        /**
         * Browser constructor.
         *
         * @param Client $client
         * @param TmpDir $tmpDir
         * @param array $auth
         * @param LoggerEx $logger
         * @param PathUtils $utils
         */
        public function __construct(Client $client, TmpDir $tmpDir, array $auth = [], LoggerEx $logger, PathUtils $utils) {
            $this->client   = $client;
            $this->tmpDir   = $tmpDir;
            $this->defaults = self::GUZZLE_DEFAULTS;
            $this->logger   = $logger;
            $this->utils    = $utils;

            if (!empty($auth)) {
                array_merge($this->defaults, ['auth' => $auth]);
            }
        }

        public function getUrl(string $url, $options = []) {
            try {
                $res = $this->client->request('GET', $url, array_merge($this->defaults, $options));

                if ($res->getStatusCode() === 200) {
                    return (string) $res->getBody();
                }
            } catch (\Throwable $e) {
                $this->logger->warn("unable to load url: $url");
            }

            return false;
        }

        public function download(string $url, string $path) {
            $resource = fopen($path, 'w');
            $this->client->request('GET', $url, array_merge($this->defaults, ['timeout' => 120, 'connect_timeout' => 60, 'sink' => $resource]));

            return realpath($path);
        }

        public function downloadCached(string $url) {
            $path = sprintf('%s/%s.%s', $this->tmpDir->getTempDir('downloads'), md5($url), $this->utils->extension($url));

            return file_exists($path) ? $path : $this->download($url, $path);
        }

        public function postUrl(string $url, $params = [], $options = []) {
            $res = $this->client->request('POST', $url, array_merge($this->defaults, $options, ['form_params' => $params]));

            if ($res->getStatusCode() === 200) {
                return (string) $res->getBody();
            }

            return false;
        }
    }
}