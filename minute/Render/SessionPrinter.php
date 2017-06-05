<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/22/2016
 * Time: 8:14 AM
 */

namespace Minute\Render {

    use App\Model\User;
    use Minute\Cache\QCache;
    use Minute\Config\Config;
    use Minute\Database\Database;
    use Minute\Event\AuthProviderEvent;
    use Minute\Event\Dispatcher;
    use Minute\Event\SessionEvent;
    use Minute\Event\ViewEvent;
    use Minute\Http\HttpRequestEx;
    use Minute\Provider\AuthProviders;
    use Minute\Routing\RouteEx;
    use Minute\Session\Session;
    use Minute\User\UserInfo;

    class SessionPrinter {
        /**
         * @var Session
         */
        private $session;
        /**
         * @var Config
         */
        private $config;
        /**
         * @var QCache
         */
        private $cache;
        /**
         * @var HttpRequestEx
         */
        private $request;
        /**
         * @var Dispatcher
         */
        private $dispatcher;
        /**
         * @var UserInfo
         */
        private $userInfo;
        /**
         * @var Database
         */
        private $database;
        /**
         * @var AuthProviders
         */
        private $providers;

        /**
         * SessionPrinter constructor.
         *
         * @param Session $session
         * @param Config $config
         * @param HttpRequestEx $request
         * @param Dispatcher $dispatcher
         * @param QCache $cache
         * @param UserInfo $userInfo
         * @param Database $database
         * @param AuthProviders $providers
         */
        public function __construct(Session $session, Config $config, HttpRequestEx $request, Dispatcher $dispatcher, QCache $cache, UserInfo $userInfo, Database $database, AuthProviders $providers) {
            $this->session    = $session;
            $this->config     = $config;
            $this->cache      = $cache;
            $this->request    = $request;
            $this->dispatcher = $dispatcher;
            $this->userInfo   = $userInfo;
            $this->database   = $database;
            $this->providers  = $providers;
        }

        public function importSession(ViewEvent $event) {
            if (!$this->database->isConnected()) {
                return;
            }

            /** @var RouteEx $route */
            $view  = $event->getView();
            $vars  = $view->getVars();
            $route = $view->get('_route');
            $data  = $this->getCachedSessionData(false);

            $data['request'] = array_merge(['url' => sprintf('%s%s', $data['site']['host'], getenv('REQUEST_URI'))], $this->request->getParameters());
            $data['params']  = array_diff_key($route->getDefaults(), array_flip(['controller', 'auth', 'models', '_route']));

            foreach ($vars as $key => $value) {
                if (($key[0] !== '_') && (is_scalar($value) || is_array($value))) {
                    $data['vars'][$key] = $value;
                }
            }

            if (!empty($data['site'])) {
                $data['site']['version'] = $this->database->hasRdsAccess() ? 'production' : 'debug';
            }

            if ($viewData = $view->getViewData()) {
                if (!empty($viewData) && is_array($viewData)) {
                    $data['view'] = $viewData;
                }
            }

            $printer = sprintf('<script' . '>Minute.setSessionData(%s)</script>', json_encode($data));
            $event->setContent($printer);
        }

        public function getCachedSessionData($reload) {
            $key      = sprintf("session-user-%d", $this->session->getLoggedInUserId());
            $userData = function () {
                $user_id = $this->session->getLoggedInUserId();
                /** @var User $user_info */
                if ($user_info = User::find($user_id)) {
                    $user_data = array_diff_key($user_info->getAttributes(), ['password' => 1, 'verified' => 1, 'ident' => 1]);
                    $paid      = false;

                    $user_data['groups'] = $this->userInfo->getUserGroups($user_id, true) ?: [];
                    $user_data['expiry'] = 0;

                    foreach ($user_data['groups'] as $group) {
                        if ($group['access'] == 'primary') {
                            $user_data['expiry'] = max($user_data['expiry'], $group['expiry_days'] ?? 0);
                            $paid = $paid || ($group['group_name'] != 'trial' && $group['expiry_days'] > 0);
                        }
                    }

                    $user_data['trial']  = !$paid;
                } else {
                    $user_data = null;
                }

                if (!empty($user_data) && empty($user_data['full_name'])) {
                    $user_data['full_name'] = trim(sprintf('%s %s', $user_data['first_name'], $user_data['last_name'])) ?: 'Anonymous';
                }

                foreach ($this->providers->getEnabled() as $provider) {
                    unset($provider['key'], $provider['secret']);
                    $providers[] = $provider;
                }

                return ['site' => $this->config->getPublicVars(), 'user' => $user_data, 'providers' => $providers ?? []];
            };

            $data = $reload ? $userData() : $this->cache->get($key, $userData, 300);

            return $data;
        }
    }
}