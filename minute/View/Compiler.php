<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/20/2016
 * Time: 7:24 AM
 */
namespace Minute\View {

    use Minute\Crypto\JwtEx;
    use Minute\Error\ViewError;
    use Minute\Http\HttpRequestEx;
    use Minute\Utils\PathUtils;

    class Compiler {
        const scriptsDir = 'scripts';
        const stylesDir  = 'styles';

        /**
         * @var  string
         */
        protected $contents;
        /**
         * @var JwtEx
         */
        private $jwt;
        /**
         * @var HttpRequestEx
         */
        private $request;

        /**
         * Compiler constructor.
         *
         * @param HttpRequestEx $request
         * @param JwtEx $jwt
         */
        public function __construct(HttpRequestEx $request, JwtEx $jwt) {
            $this->request = $request;
            $this->jwt     = $jwt;
        }

        /**
         * @return string
         */
        public function getContents() {
            return $this->contents;
        }

        /**
         * @param string $contents
         */
        public function setContents(string $contents) {
            $this->contents = $contents;
        }

        public function capture($file, $viewData) {
            try {
                ob_start();

                if (!empty($viewData) && is_array($viewData)) {
                    extract($viewData);
                }

                if (@include($file)) {
                    $this->contents = ob_get_contents();
                }
            } catch (\Throwable $e) {
                ob_end_clean();

                throw new ViewError(sprintf("Unable to compile view: %s: %s", $file, $e->getMessage()));
            } finally {
                ob_end_clean();
            }

            return $this->contents;
        }

        /**
         * @param $files
         * @param $viewData - any view variables
         *
         * @return string
         */
        public function compile($files, $viewData = []) {
            $content = $this->contents;

            foreach ($files as $file) {
                try {
                    $capture   = $this->capture($file, $viewData);
                    $content   = $this->replaceContentTagInLayout($content, $capture);
                    $resources = ['scripts' => self::scriptsDir, 'styles' => self::stylesDir];

                    foreach ($resources as $type => $dir) {
                        if (!empty($dir)) {
                            $pathInfo = (new PathUtils())->pathinfo($file);
                            $isScript = $type === 'scripts';
                            $fileExt  = $isScript ? 'js' : 'css';
                            $resource = sprintf('%s/%s/%s.%s', $pathInfo['dirname'], $dir, $pathInfo['filename'], $fileExt);

                            if (file_exists($resource)) {
                                if ($this->request->isAjaxRequest()) { //We embed ajax requests inline (to avoid cross-domain issues)
                                    $data = file_get_contents($resource);
                                    if ($isScript) { //this should dispatch as event instead of concating it (so that minimizer can include it and return false)
                                        $content = sprintf("\n<script type=\"text/javascript\"" . ">\n%s\n</script>\n%s\n", $data, $content);
                                    } else {
                                        $content = sprintf("\n<style" . ">\n%s\n</style>\n%s\n", $data, $content);
                                    }
                                } else {
                                    $size = filesize($resource);
                                    $name = $this->jwt->encode((object) ['path' => $resource, 'size' => $size]);

                                    if ($isScript) {
                                        $content = sprintf('<' . 'script src="/static/_resource/%s.js"></script>', $name) . "\n$content\n";
                                    } else {
                                        $content = sprintf('<' . 'link rel="stylesheet" href="/static/_resource/%s.css">', $name) . "\n$content\n";
                                    }
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    trigger_error(sprintf("Unable to include view: %s: %s", $file, $e->getMessage()));
                }
            }

            return $content;
        }

        public function replaceContentTagInLayout($content, $layout) {
            return str_replace('<minute-include-content' . '></minute-include-content>', $content, $layout);
        }
    }
}