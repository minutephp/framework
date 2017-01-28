<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/18/2016
 * Time: 7:10 PM
 */
namespace Minute\View {

    use Minute\Cache\QCache;
    use Minute\Dom\TagUtils;
    use Minute\Error\ViewError;
    use Minute\Event\Dispatcher;
    use Minute\Event\ImportEvent;
    use Minute\Event\SeoEvent;
    use Minute\Event\ViewEvent;
    use Minute\Event\ViewParserEvent;
    use Minute\Http\HttpRequestEx;
    use Minute\Log\LoggerEx;
    use Minute\Resolver\Resolver;
    use Minute\Routing\RouteEx;
    use SimpleXMLElement;

    class ViewParser {
        const CACHE_TIMEOUT = 60 * 60;
        /**
         * @var string
         */
        private $layout;
        /**
         * @var array
         */
        private $viewData;
        /**
         * @var array
         */
        private $helpers = [];
        /**
         * @var array
         */
        private $vars;
        /**
         * @var Dispatcher
         */
        private $dispatcher;
        /**
         * @var LoggerEx
         */
        private $logger;
        /**
         * @var QCache
         */
        private $cache;
        /**
         * @var Resolver
         */
        private $resolver;
        /**
         * @var string
         */
        private $content = '';
        /**
         * @var array
         */
        private $additionalLayoutFiles;
        /**
         * @var Compiler
         */
        private $compiler;
        /**
         * @var TagUtils
         */
        private $tagUtils;
        /**
         * @var HttpRequestEx
         */
        private $request;

        /**
         * View constructor.
         *
         * @param Resolver $resolver
         * @param Dispatcher $dispatcher
         * @param LoggerEx $logger
         * @param QCache $cache
         * @param Compiler $compiler
         * @param TagUtils $tagUtils
         * @param HttpRequestEx $request
         */
        public function __construct(Resolver $resolver, Dispatcher $dispatcher, LoggerEx $logger, QCache $cache, Compiler $compiler, TagUtils $tagUtils, HttpRequestEx $request) {
            $this->dispatcher = $dispatcher;
            $this->logger     = $logger;
            $this->cache      = $cache;
            $this->resolver   = $resolver;
            $this->compiler   = $compiler;
            $this->tagUtils   = $tagUtils;
            $this->request    = $request;
        }

        /**
         * @return array
         */
        public function getViewData(): array {
            return $this->viewData ?? [];
        }

        /**
         * @param array $viewData
         *
         * @return ViewParser
         */
        public function setViewData(array $viewData): ViewParser {
            $this->viewData = $viewData;

            return $this;
        }

        /**
         * @return array
         */
        public function getAdditionalLayoutFiles() {
            return $this->additionalLayoutFiles;
        }

        /**
         * @param array $additionalLayoutFiles
         *
         * @return ViewParser
         */
        public function setAdditionalLayoutFiles($additionalLayoutFiles) {
            $this->additionalLayoutFiles = $additionalLayoutFiles;

            return $this;
        }

        /**
         * @return string
         */
        public function getContent() {
            return $this->content;
        }

        /**
         * @param string $content
         *
         * @return ViewParser
         */
        public function setContent($content): ViewParser {
            $this->content = $content;

            return $this;
        }

        /**
         * @return string
         */
        public function getLayout() {
            return $this->layout;
        }

        /**
         * @param string $layout
         *
         * @return ViewParser
         */
        public function setLayout($layout): ViewParser {
            $this->layout = $layout;

            return $this;
        }

        /**
         * @return array
         */
        public function getHelpers() {
            return $this->helpers;
        }

        /**
         * @param array $helpers
         *
         * @return ViewParser
         */
        public function setHelpers($helpers) {
            $this->helpers = $helpers;

            return $this;
        }

        public function addHelper(Helper $helper) {
            $this->helpers[] = $helper;

            return $this;
        }

        /**
         * @return array
         */
        public function getVars() {
            return $this->vars;
        }

        /**
         * @param array $vars
         *
         * @return ViewParser
         */
        public function setVars($vars) {
            $this->vars = $vars;

            return $this;
        }

        /**
         * @param string $viewFile
         * @param bool $withLayout
         * @param bool $useCache
         *
         * @return ViewParser
         * @throws ViewError
         */
        public function loadViewFile(string $viewFile, bool $withLayout = true, bool $useCache = true) {
            $compiler = function () use ($viewFile, $withLayout) {
                if ($files[] = $path = $this->resolver->getView($viewFile)) {
                    if ($withLayout === true) {
                        for ($i = 0, $layout = dirname($viewFile); $i <= 10 && $layout !== '.'; $layout = dirname($layout), $i++) {
                            $includes[] = $layout;
                        }

                        $includes = array_merge($includes ?? [], ['Global']);
                    }

                    if ($additionalLayouts = $this->getAdditionalLayoutFiles()) {
                        $includes = array_merge($includes ?? [], $additionalLayouts);
                    }

                    if (!empty($includes)) {
                        $files = array_merge($files, array_filter(array_map(function ($l) { return $this->resolver->getView("Layout\\$l"); }, $includes)));
                    }

                    $compiled = $this->compiler->compile($files, $this->getViewData());

                    return $compiled;
                } else {
                    throw new ViewError("Unable to find view file: $viewFile");
                }
            };

            $this->content = $useCache ? $this->cache->get("compiled:$viewFile", $compiler, self::CACHE_TIMEOUT) : $compiler();

            return $this;
        }

        /**
         * Get all <event name="$eventName"></event> tags and fire $eventName events,
         * then replace the <event> tag with the response generated by event handlers.
         *
         */
        public function render() {
            $html  = !empty($this->layout) ? $this->compiler->replaceContentTagInLayout($this->content ?? '', $this->layout) : $this->content ?? '';
            $event = new ViewParserEvent($this, $html);
            $this->dispatcher->fire(ViewParserEvent::VIEWPARSER_RENDER, $event);

            $output = preg_replace_callback('~(<minute-event.*?</minute-event>)~mi', function ($matches) {
                $tag       = $matches[1];
                $attrs     = (array) new SimpleXMLElement($tag);
                $attrs     = current($attrs);
                $event     = new ViewEvent($this, $tag, $attrs);
                $eventName = strtolower(strtr($attrs['name'], ['_' => '.']));

                if (preg_match('/^(user|import)\./i', $eventName)) {
                    if (!empty($attrs['as'])) {
                        $routeParams = !empty($this->vars['_route']) ? $this->vars['_route']->getDefaults() : [];
                        $importEvent = new ImportEvent($event, $tag, $attrs, $routeParams);
                        $this->dispatcher->fire($eventName, $importEvent);

                        foreach ($attrs as $k => $v) {
                            if ($k !== 'name' && $k !== 'as') {
                                $values[] = sprintf('%s="%s"', $k, htmlentities($v));
                            }
                        }

                        $data   = $importEvent->getContent();
                        $import = sprintf('<minute-importer into="%s" data="%s" %s></minute-importer>', $attrs['as'], htmlentities(json_encode($data ?? [])), join(' ', $values ?? []));

                        $event->setContent($import);
                    } else {
                        $this->dispatcher->fire($eventName, $event);
                    }
                } else {
                    throw new ViewError("Server side event '$eventName' failed (server side events may start with USER_ or IMPORT_ only)");
                }

                return $event->getContent();
            }, $event->getHtml());

            /** @var Helper $helper */
            foreach ($this->helpers ?? [] as $helper) {
                if ($templateUrl = $helper->getTemplateUrl()) {
                    if ($path = $this->resolver->getView("Helper\\$templateUrl")) {
                        $content = $this->compiler->compile([$path]);
                        $tagName = $helper->getPosition() === Helper::POSITION_BODY ? '</body>' : '</head>';
                        $output  = $this->tagUtils->insertBeforeTag($tagName, $content, $output);
                    } else {
                        throw new ViewError(sprintf("Unable to find helper: %s", $templateUrl));
                    }
                }
            }

            $output = preg_replace_callback('~<title></title>~', function () {
                $url     = $this->request->getPath();
                $details = $this->cache->get("seo-$url", function () use ($url) {
                    $event = new SeoEvent($url);
                    $this->dispatcher->fire(SeoEvent::SEO_GET_TITLE, $event);

                    return !empty($event->getTitle()) ? ['title' => $event->getTitle(), 'meta' => $event->getMeta()] : null;
                }, 3600);

                $result = sprintf('<title>%s</title>%s', $details['title'] ?? ucwords(trim(join(' ', preg_split('/\W+/', $url)))), PHP_EOL);

                if (!empty($details['meta']) && is_array($details['meta'])) {
                    foreach ($details['meta'] as $meta) {
                        $result .= sprintf('<meta name="%s" content="%s" />%s', $meta['name'], $meta['content'], PHP_EOL);
                    }
                }

                return $result;
            }, $output);

            return $output;
        }

        /**
         * Get a stored variable
         *
         * @param string $name
         *
         * @return mixed|null
         */
        public function get(string $name) {
            return $this->vars[$name] ?? null;
        }

        /**
         * Add a new variable
         *
         * @param string $name
         * @param $value
         *
         * @return $this
         */
        public function set(string $name, $value) {
            $this->vars[$name] = $value;

            return $this;
        }
    }
}