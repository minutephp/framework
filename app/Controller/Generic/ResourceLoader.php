<?php
/**
 * Created by: MinutePHP framework
 */
namespace App\Controller\Generic {

    use Minute\Crypto\JwtEx;
    use Minute\Http\HttpResponseEx;
    use Minute\Log\LoggerEx;

    class ResourceLoader {
        /**
         * @var JwtEx
         */
        private $jwt;
        /**
         * @var HttpResponseEx
         */
        private $response;
        /**
         * @var LoggerEx
         */
        private $logger;

        /**
         * ResourceLoader constructor.
         *
         * @param JwtEx $jwt
         * @param HttpResponseEx $response
         * @param LoggerEx $logger
         */
        public function __construct(JwtEx $jwt, HttpResponseEx $response, LoggerEx $logger) {
            $this->jwt      = $jwt;
            $this->response = $response;
            $this->logger   = $logger;
        }

        public function index(string $name) {
            $path = pathinfo($name);
            $ext  = $path['extension'];

            $this->response->setStatusCode(404);

            if (($ext === 'js') || ($ext === 'css')) {
                $token = $path['filename'];

                if ($decoded = $this->jwt->decode($token)) {
                    if ($file = realpath($decoded->path)) {
                        $dir = $ext === 'js' ? 'scripts' : 'styles';

                        if ((basename(dirname($file)) === $dir)) {
                            if ((filesize($file) == $decoded->size)) {
                                $this->response->setStatusCode(200);
                                $this->response->setFinal(true);
                                $this->response->setHeader('Content-Type', preg_match('/\.js$/', $name) ? 'application/javascript' : 'text/css');
                                $this->response->setHeader('Cache-Control', 'max-age=31622400, public');
                                $this->response->setContent(file_get_contents($decoded->path));
                            } else {
                                $this->logger->warn("File size mismatch: $file");
                            }
                        } else {
                            $this->logger->warn("File directory mismatch: $dir");
                        }
                    }
                } else {
                    $this->logger->warn("Unable to decode token: $token");
                }
            } else {
                $this->logger->warn("Unsupported resource extension: $ext");
            }

            return $this->response;
        }
    }
}