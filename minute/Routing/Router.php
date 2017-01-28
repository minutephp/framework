<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 6:04 PM
 */
namespace Minute\Routing {

    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Support\Str;
    use Minute\Cache\FileCache;
    use Minute\Config\Config;
    use Minute\Database\Database;
    use Minute\Error\AuthError;
    use Minute\Error\ModelError;
    use Minute\Error\RouteError;
    use Minute\Error\ValidationError;
    use Minute\Event\AuthEvent;
    use Minute\Event\ControllerEvent;
    use Minute\Event\Dispatcher;
    use Minute\Event\RequestEvent;
    use Minute\Event\RouterEvent;
    use Minute\Http\HttpResponseEx;
    use Minute\Model\ModelEx;
    use Minute\Model\ModelLoader;
    use Minute\Model\Permission;
    use Minute\Model\SpecialPermission;
    use Minute\Resolver\Resolver;
    use Symfony\Component\Routing\Exception\ResourceNotFoundException;
    use Symfony\Component\Routing\Matcher\UrlMatcher;
    use Symfony\Component\Routing\RequestContext;
    use Symfony\Component\Routing\RouteCollection;

    class Router {
        /**
         * @var Dispatcher
         */
        private $dispatcher;
        /**
         * @var RouteCollection
         */
        private $routeCollection;
        /**
         * @var Resolver
         */
        private $resolver;
        /**
         * @var Database
         */
        private $database;
        /**
         * @var ModelLoader
         */
        private $modelLoader;
        /**
         * @var  RouteEx
         */
        private $lastMatchingRoute;
        /**
         * @var Config
         */
        private $config;
        /**
         * @var FileCache
         */
        private $cache;
        /**
         * @var HttpResponseEx
         */
        private $cachedResponse;

        /**
         * Router constructor.
         *
         * @param RouteCollection $routeCollection
         * @param Dispatcher $dispatcher
         * @param Resolver $resolver
         * @param Database $database
         * @param ModelLoader $modelLoader
         * @param Config $config
         * @param FileCache $cache
         * @param HttpResponseEx $cachedResponse
         */
        public function __construct(RouteCollection $routeCollection, Dispatcher $dispatcher, Resolver $resolver, Database $database, ModelLoader $modelLoader, Config $config,
                                    FileCache $cache, HttpResponseEx $cachedResponse) {
            $this->routeCollection = $routeCollection;
            $this->dispatcher      = $dispatcher;
            $this->resolver        = $resolver;
            $this->database        = $database;
            $this->modelLoader     = $modelLoader;
            $this->config          = $config;
            $this->cache           = $cache;
            $this->cachedResponse  = $cachedResponse;
        }

        /**
         * Add a new GET route in Router
         *
         * @param string $url
         * @param null $controller
         * @param bool $auth
         * @param array ...$models
         *
         * @return RouteEx
         */
        public function get(string $url, $controller = null, $auth = false, ...$models): RouteEx {
            return $this->addRoute('GET', $url, $controller, $auth, $models);
        }

        /**
         * Add a new POST route in Router
         *
         * @param string $url
         * @param null $controller
         * @param bool $auth
         * @param array ...$models
         *
         * @return RouteEx
         */
        public function post(string $url, $controller = null, $auth = false, ...$models): RouteEx {
            return $this->addRoute('POST', $url, $controller, $auth, $models);
        }

        /**
         * Returns an array of routes matching the HTTP method and URL
         * Arranged by priority
         *
         * @param $method
         * @param $url
         *
         * @return RouteEx|null
         */
        public function match($method, $url) {
            $matcher = new UrlMatcher($this->routeCollection, new RequestContext('/', $method));

            if ($match = $matcher->match($url)) {
                /** @var RouteEx $routeEx */
                foreach ($this->routeCollection as $routeEx) {
                    if ([$routeEx->getPath(), $routeEx->getDefault('controller')] === [$match['url'], $match['controller']]) {
                        $copy = clone($routeEx);
                        $copy->setDefaults(array_merge($routeEx->getDefaults(), $match));

                        return $copy;
                    }
                }
            }

            return null;
        }

        /**
         * Find the matching route and execute the controller
         *
         * Algorithm - http://www.minutephp.com/ADD
         *      # Get the URL and Method from the event
         *      Find the matching route by URL and method
         *          Get the controller, auth and list of models from route
         *          Throw error if the user is not authorized to access the route
         *
         *          If Method is GET
         *              Parse the models to create parent child relations
         *              Throw error if the user isn't allowed to Read the model
         *              Create a list of constraints using \
         *                  \ conditions in the route model definitions
         *                  \ conditions added using `addConstraint` method
         *              Perform additional checks if permission is SAME_USER
         *              If the controller is null guess it from the route URL
         *              If this is an ajax request with metadata then override the controller with our own GET handler
         *              Call the controller with the constraints using an event
         *
         *          If Method is POST
         *              If this is an ajax request and we have model definitions
         *                  Parse the models in the route
         *                  Get the model, and items and type of operation from the request
         *                  Throw error if the user cannot the operation on selected model
         *                  Create models instances for each item
         *                  Call the GenericPostHandler with the instances using an event
         * end.
         *
         * @param RequestEvent $requestEvent
         *
         * @throws AuthError
         * @throws ModelError
         * @throws RouteError
         * @throws ValidationError
         */
        public function handle(RequestEvent $requestEvent) {
            $request = $requestEvent->getRequest();

            //try {
            $parents = null;
            $method  = $request->getMethod();

            $this->compile();

            //Since we can have multiple POST requests for a single Url
            if (($method === 'POST') && ($alias = $request->getParameter('alias')) && ($class = $request->getParameter('model'))) {
                $newCollection = new RouteCollection();

                foreach (['alias' => $alias, 'name' => $class] as $key => $value) {
                    /** @var RouteEx $routeEx */
                    foreach ($this->routeCollection as $name => $routeEx) {
                        if (in_array('POST', $routeEx->getMethods())) {
                            $parents = $routeEx->parsePostModels();

                            if ($filtered = array_filter($parents, function ($m) use ($key, $value) { return $m[$key] === $value; })) {
                                $newCollection->add($name, $routeEx);
                            }
                        }
                    }
                }

                if ($newCollection->count()) {
                    $this->routeCollection = $newCollection;
                } else {
                    throw new ResourceNotFoundException("No Post handler configured for alias '$alias' or class '$class'");
                }
            }

            try {
                $route = $this->match($method, $request->getPath());
            } catch (ResourceNotFoundException $e) {
                $event = new RouterEvent($method, $request->getPath());
                $this->dispatcher->fire(RouterEvent::ROUTER_GET_FALLBACK_RESOURCE, $event);

                if (!($route = $event->getRoute())) {
                    throw $e;
                }
            }

            $event = new AuthEvent($route->getDefault('auth'));

            $this->lastMatchingRoute = $route;
            $this->dispatcher->fire(AuthEvent::AUTH_CHECK_ACCESS, $event);

            if ($event->isAuthorized()) {
                $user_id     = $event->getActiveUserId();
                $controller  = $controllerArgs = null;
                $contentType = 'html';

                if ($method === 'POST') {
                    $parents    = $route->parsePostModels();
                    $controller = $route->getDefault('controller');
                    $matches    = [];

                    if (empty($controller)) {
                        if (!empty($parents)) {
                            $controller = 'Generic/DefaultPostHandler.php';
                        } else {
                            $controller = trim(Str::camel(str_replace(' ', '', ucwords(preg_replace('/(\W+)/', '\\1 ', $route->getPath())))), '/');
                        }
                    }

                    if (!empty($parents)) {
                        if ($alias = $request->getParameter('alias')) {
                            $matches = array_filter($parents, function ($f) use ($alias) { return $f['alias'] === $alias; });
                        }

                        if (count($matches) === 1) {
                            $model = array_shift($matches);
                        } else {
                            if ($class = $request->getParameter('model')) {
                                $matches = array_filter($parents, function ($f) use ($class) { return $f['name'] === $class; });
                            }

                            if (count($matches) === 1) {
                                $model = array_shift($matches);
                            } else {
                                throw new ModelError("Post route does not have a matching model for alias:'$alias' or class:'$class'");
                            }
                        }

                        /** @var ModelEx $inst */
                        if ($modelClass = $this->resolver->getModel($model['name'])) {
                            $inst  = new $modelClass;
                            $cmd   = $request->getParameter('cmd', 'save');
                            $pri   = $inst->getKeyName();
                            $items = $request->getParameter('items', []);
                            $cols  = $this->database->getColumns($inst->getTable());

                            if (!empty($items)) {
                                foreach ((array) $items as $item) {
                                    $accessMode = $cmd == 'save' ? (!empty($item[$pri]) ? 'update' : 'create') : 'delete';
                                    $permission = call_user_func([$route, sprintf('get%sPermission', ucwords($accessMode))], $model['alias']);

                                    if ($this->canAccessModel($permission, $event->isLoggedInUser())) {
                                        $fields    = $model['fields'] ?? $cols;
                                        $immutable = [$pri, 'user_id'];
                                        $fields    = array_diff($fields, $immutable);
                                        $has_uid   = in_array('user_id', $cols);

                                        if (count(array_diff(array_keys($item), array_merge($fields, $immutable))) !== 0) {
                                            throw new ValidationError(sprintf("Field restriction on '%s' failed on fields: '%s'", $model['alias'], join(', ', array_keys($item))));
                                        }

                                        if ($accessMode === 'create') {
                                            /** @var ModelEx $m */
                                            $modelClass::unguard();
                                            $instance = new $modelClass($item);
                                        } else {
                                            if ($instance = $modelClass::where($pri, '=', $item[$pri])->first()) {
                                                if ($accessMode !== 'delete') {
                                                    foreach ($fields as $field) {
                                                        $instance->$field = $item[$field] ?? ($instance->$field ?? null);
                                                    }
                                                }
                                            } else {
                                                throw new ModelError(sprintf("No record found in %s for '%s' = '%s'", $model['name'], $pri, $item[$pri]));
                                            }
                                        }

                                        if ($permission == Permission::SAME_USER) {
                                            if (!$instance->user_id) {
                                                $instance->user_id = $user_id;
                                            } else if ($instance->user_id !== $user_id) {
                                                throw new ModelError(sprintf("%s user_id does not match to current user.", ucfirst($model['name'])));
                                            }
                                        }

                                        if ($has_uid && ($accessMode !== 'delete')) {
                                            $instance->user_id = $instance->user_id ?: ($user_id ?: ($item['user_id'] ?: 0));
                                        }

                                        $models[] = $instance;
                                    } else {
                                        throw new ModelError(sprintf("%s access denied to '%s' in %s. Enable auth or change read permission", ucwords($accessMode), $model['alias'], $route->getPath()));
                                    }
                                }
                            }
                        } else {
                            throw new RouteError(sprintf("Cannot create model for class: %s in %s", $model['name'], $route->getPath()));
                        }
                    }

                    $controllerArgs = ['_parents' => $parents, '_models' => $models ?? null, '_mode' => $accessMode ?? $cmd ?? 'update'];
                } elseif ($method === 'GET') {
                    $cache    = $route->getCached();
                    $parents  = $route->parseGetModels();
                    $metadata = $request->getParameter('metadata');

                    if ($cache > 0) {
                        $values   = array_intersect_key($route->getDefaults(), array_flip(['url', 'controller', 'auth', 'models']));
                        $cacheKey = sprintf("content-cache-%s", md5(json_encode($values)));

                        if ($content = $this->cache->get($cacheKey)) {
                            $this->cachedResponse->setStatusCode(200);
                            $this->cachedResponse->setContent($content);
                            $this->cachedResponse->setFinal(true);

                            $requestEvent->setResponse($this->cachedResponse);

                            return;
                        }
                    }

                    $addConstraints = function ($alias, $permission, $self) use ($route, $event, $user_id) {
                        if ($canIgnore = $permission === SpecialPermission::SAME_USER_OR_IGNORE) {
                            $permission = Permission::SAME_USER;
                        }

                        $hasAccess = $this->canAccessModel($permission, $event->isLoggedInUser());

                        if (!$hasAccess && $canIgnore) {
                            $route->addConstraint($alias, function (Builder $builder) { return $builder->whereRaw('1 = 0'); });
                        } elseif ($hasAccess) {
                            $colValue = null;

                            if ($permission === Permission::SAME_USER) {
                                $route->addConstraint($alias, ['user_id', '=', $user_id]);
                            }

                            if ($matchInfo = $self['matchInfo'] ?? null) {
                                if (!empty($matchInfo['name'])) {
                                    list($name, $col, $type) = [$matchInfo['name'], $matchInfo['col'], $matchInfo['type']];
                                    $colValue = $type === 'url_param' ? $route->getDefault($name) : ($type === 'var' ? ${$name} : ($type === 'string' ? $matchInfo['value'] : null));
                                }
                            }

                            if (!empty($col) && isset($colValue)) {
                                $route->addConstraint($alias, [$col, '=', $colValue]);
                            } elseif (($route->getDefault($alias) !== '*') && ($permission !== Permission::SAME_USER)) {
                                //we block all queries when there is no matchInfo (unless alias explicitly defaults to *)
                                $route->addConstraint($alias, function (Builder $builder) { return $builder->whereRaw('1 = 0'); });
                            }
                        } else {
                            throw new ModelError(sprintf("Read access denied to %s in %s. Enable auth or change read permission", $alias, $route->getPath()));
                        }
                    };

                    $joiner = function ($parents) use (&$joiner, $route, $addConstraints) {
                        foreach ($parents['children'] as $alias => $value) {
                            $permission = $route->getJoinPermission($alias);

                            if ($permission !== Permission::EVERYONE) {
                                $addConstraints($alias, $permission, $value['self']);
                            }

                            if (!empty($value['children'])) {
                                $joiner($value);
                            }
                        }
                    };

                    foreach ($parents as $key => $value) {
                        $self = $value['self'];

                        if ($parentModelClass = $this->resolver->getModel($self['name'])) {
                            $parentAlias = $self['alias'];
                            $permission  = $route->getReadPermission($parentAlias);

                            $addConstraints($parentAlias, $permission, $self);
                        } else {
                            throw new RouteError(sprintf("Cannot create model for class: %s in %s", $self['name'], $route->getPath()));
                        }

                        $joiner($value);
                    }

                    $controller = $route->getDefault('controller');

                    $modifyNodeByAlias = function (&$root, $alias, $modifiers, $append) use (&$modifyNodeByAlias) {
                        foreach ($root as $parent => $child) {
                            if ($parent === $alias) {
                                foreach ($modifiers as $key => $value) {
                                    if ($append) { //conditions
                                        $root[$parent]['self'][$key] = array_merge($root[$parent]['self'][$key] ?? [], $value);
                                    } else {  //offset, limit, etc
                                        $root[$parent]['self'][$key] = $value;
                                    }
                                }

                                return true;
                            } elseif (!empty($child['children']) && ($found = $modifyNodeByAlias($root[$parent]['children'], $alias, $modifiers, $append))) {
                                return $found;
                            }
                        }

                        return false;
                    };

                    foreach ($route->getAllConstraints() as $alias => $constraint) {
                        if (!$modifyNodeByAlias($parents, $alias, ['conditions' => $constraint], true)) {
                            throw new ModelError("Could not find model $alias to add condition");
                        }
                    }

                    if ($controller === null) {
                        $controller = 'Generic/Page.php';
                    }

                    if ($request->isAjaxRequest() && !empty($metadata)) { //we override controller for ajax request which have $_GET['metadata'] set
                        $metadata    = json_decode($metadata, true);
                        $contentType = 'ajax';

                        foreach ($metadata ?? [] as $alias => $values) {
                            if (!$modifyNodeByAlias($parents, $alias, $values, false)) {
                                throw new ModelError("Could not find model $alias to apply metadata");
                            }
                        }
                    }

                    $controllerArgs = ['_parents' => $parents];
                }

                $defaults = array_diff_key($route->getDefaults(), array_flip(['url', 'controller', 'auth', 'models', '_route']));
                $params   = array_merge($defaults, $request->getParameters());
                $event    = new ControllerEvent($controller, array_merge(['_route' => $route, '_method' => $method, '_params' => $params,
                                                                          '_contentType' => $contentType], $params, $controllerArgs ?? []));
                $this->dispatcher->fire(ControllerEvent::CONTROLLER_EXECUTE, $event);
                $response = $event->getResponse();

                if (!empty($cacheKey) && !empty($cache)) {
                    $this->cache->set($cacheKey, $response->getContent(), $cache);
                }

            } else {
                throw new AuthError('Forbidden');
            }

            $requestEvent->setResponse($response);
        }

        /**
         * @return RouteCollection
         */
        public function getRouteCollection(): RouteCollection {
            return $this->routeCollection;
        }

        /**
         * @return RouteEx - for debugging purpose only
         */
        public function getLastMatchingRoute(): RouteEx {
            return $this->lastMatchingRoute;
        }

        /**
         * Add a new route in Router
         *
         * @param string $method
         * @param string $url
         * @param $controller
         * @param bool $auth
         * @param array ...$models
         *
         * @return RouteEx
         */
        protected function addRoute(string $method, string $url, $controller = null, $auth = false, ...$models): RouteEx {
            $params = ['url' => $url, 'controller' => $controller, 'auth' => $auth, 'models' => $models[0] ?? null];
            $route  = new RouteEx($url, $params, [], [], '', [], [$method], ''); //condition can be used to restrict $models?
            $name   = spl_object_hash((object) func_get_args());
            $this->routeCollection->add($name, $route);

            return $route;
        }

        /**
         * @param string $permission
         * @param bool $isLoggedInUser
         *
         * @return bool
         */
        protected function canAccessModel(string $permission, bool $isLoggedInUser) {
            if ($permission === Permission::NOBODY) {
                return false;
            } elseif ($permission === Permission::EVERYONE) {
                return true;
            } elseif ($permission === Permission::GUEST) {
                return !($isLoggedInUser);
            } elseif (($permission === Permission::ANY_USER) || ($permission === true)) {
                return $isLoggedInUser;
            } elseif ($permission === Permission::SAME_USER) {
                return $isLoggedInUser;                                                                //but do additional checks depending on operation
            } else {
                $event = new AuthEvent($permission);
                $this->dispatcher->fire(AuthEvent::AUTH_CHECK_ACCESS, $event);                            //the permission is a level like 'admin', 'member', etc

                return $event->isAuthorized();
            }
        }

        /**
         * Find all route.php files and add them to route collection
         */
        public function compile() {
            if ($files = $this->resolver->getRoutes()) {
                /** @noinspection PhpUnusedLocalVariableInspection - is accessed in routes.php file */
                $router = $this;

                foreach ($files as $file) {
                    require_once($file);
                }
            }

            return $this;
        }
    }
}