<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/18/2016
 * Time: 6:42 PM
 */
namespace Minute\Controller {

    use Auryn\InjectionException;
    use Auryn\Injector;
    use Closure;
    use Minute\Error\ControllerError;
    use Minute\Event\ControllerEvent;
    use Minute\Event\Dispatcher;
    use Minute\Event\RedirectEvent;
    use Minute\Http\HttpResponseEx;
    use Minute\Model\CollectionEx;
    use Minute\Model\ModelLoader;
    use Minute\Resolver\Resolver;
    use Minute\View\Redirection;
    use Minute\View\View;
    use Minute\View\ViewParser;

    class Runnable {
        /**
         * @var Injector
         */
        private $injector;
        /**
         * @var ViewParser
         */
        private $viewParser;
        /**
         * @var ModelLoader
         */
        private $modelLoader;
        /**
         * @var Resolver
         */
        private $resolver;
        /**
         * @var HttpResponseEx
         */
        private $response;
        /**
         * @var Dispatcher
         */
        private $dispatcher;

        /**
         * Run constructor.
         *
         * @param Injector $injector
         * @param ViewParser $viewParser
         * @param ModelLoader $modelLoader
         * @param Resolver $resolver
         * @param HttpResponseEx $response
         * @param Dispatcher $dispatcher
         */
        public function __construct(Injector $injector, ViewParser $viewParser, ModelLoader $modelLoader, Resolver $resolver, HttpResponseEx $response, Dispatcher $dispatcher) {
            $this->injector    = $injector;
            $this->viewParser  = $viewParser;
            $this->modelLoader = $modelLoader;
            $this->resolver    = $resolver;
            $this->response    = $response;
            $this->dispatcher  = $dispatcher;
        }

        public function execute(ControllerEvent $event) {
            try {
                $params  = $event->getParams(true) ?? [];
                $ajaxReq = $params['_contentType'] === 'ajax';

                if (($params['_method'] === 'GET') && ($parents = $params['_parents'] ?? null)) {
                    $alias = $ajaxReq ? $params['alias'] : null;

                    if ($models = $this->modelLoader->loadModels($parents, $alias)) {
                        $event->setParam('_models', $models);

                        foreach ($models as $key => $value) {
                            $event->setParam($key, $value);
                        }
                    }

                    foreach ($parents as $key => $value) {
                        $event->setParam("_$key", $models[$key] ?? []);
                    }
                }

                $args     = array_merge([':event' => $event], $event->getParams() ?? []);
                $action   = $this->getController($event->getController());
                $final    = true;
                $response = $output = $this->injector->execute($action, $args);

                if ($response instanceof Redirection) {
                    $this->dispatcher->fire(RedirectEvent::REDIRECT, new RedirectEvent($response)); //chance to do something with redirection
                    $response->redirect(); //exists
                } else if ($response instanceof HttpResponseEx) {
                    $event->setResponse($this->response);
                } else {
                    if ($response instanceof View) {
                        if ($ajaxReq && !empty($alias) && !empty($models)) {
                            $output = '{}';

                            /** @var CollectionEx $model */
                            foreach ($models as $tlp => $model) {
                                if ($array = $model->toArray()) {
                                    if ($child = $tlp === $alias ? $array : $this->findChildByAlias($array, $alias)) {
                                        $output = json_encode($child);
                                    }
                                }
                            }
                        } else {
                            /** @var View $view */
                            $view = $response;
                            $final = $response->isFinal();

                            $this->viewParser->setHelpers($view->getHelpers());
                            $this->viewParser->setLayout($view->getLayout());
                            $this->viewParser->setAdditionalLayoutFiles($view->getAdditionalLayoutFiles());
                            $this->viewParser->setVars(array_merge($event->getParams(true) ?? [], $view->getVars() ?? []));
                            $this->viewParser->setViewData($view->getViewData());

                            if ($content = $view->getContent()) {
                                if (!empty($view->getViewFile())) {
                                    trigger_error('You should only specify either content or view file');
                                }

                                $this->viewParser->setContent($content);
                            } elseif ($templatePath = $view->getViewFile()) {
                                $this->viewParser->loadViewFile($templatePath, $view->isPathLayouts());
                            } elseif (empty($view->getViewFile())) {
                                if (is_string($event->getController())) {
                                    @list($class) = explode('@', $event->getController(), 2);
                                    $this->viewParser->loadViewFile($class, $view->isPathLayouts());
                                } else {
                                    throw new ControllerError("View must be explicitly set (when Controller is not a string)");
                                }
                            }

                            $output = $this->viewParser->render();
                        }
                    }

                    $this->response->setStatusCode(200);
                    $this->response->setContent(is_string($output) ? $output : '');
                    $this->response->setFinal($final);
                    $event->setResponse($this->response);
                }
            } catch (InjectionException $e) {
                throw new ControllerError(sprintf("Unable to run controller: %s [%s]", $e->getMessage(), var_export($event->getController(), true)));
            }
        }

        /**
         * @param $controller
         *
         * @return mixed
         */
        protected function getController($controller) {
            if ($controller instanceof Closure) {
                return $controller;
            } elseif (is_string($controller)) {
                @list($class, $function) = explode('@', $controller, 2);

                return [$this->resolver->getController($class), $function ?? 'index'];
            }

            return $controller;
        }

        protected function findChildByAlias($nodes, $alias) {
            foreach ($nodes as $me => $children) {
                if ($me === $alias) {
                    return $nodes[$me];
                } elseif (is_array($children) && ($found = $this->findChildByAlias($children, $alias))) {
                    return $found;
                }
            }

            return false;
        }
    }
}