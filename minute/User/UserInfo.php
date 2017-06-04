<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/21/2016
 * Time: 9:52 AM
 */

namespace Minute\User {

    use Carbon\Carbon;
    use Minute\Cache\QCache;
    use Minute\Config\Config;
    use Minute\Model\ModelEx;
    use Minute\Resolver\Resolver;

    class UserInfo {
        /**
         * @var ModelEx
         */
        protected $userModel;
        /**
         * @var Resolver
         */
        private $resolver;
        /**
         * @var QCache
         */
        private $cache;
        /**
         * @var Config
         */
        private $config;

        /**
         * UserInfo constructor.
         *
         * @param Resolver $resolver
         * @param Config $config
         * @param QCache $cache
         */
        public function __construct(Resolver $resolver, Config $config, QCache $cache) {
            $this->resolver = $resolver;
            $this->cache    = $cache;
            $this->config   = $config;
        }

        public function getUserGroups($userId, $extended = false, $fresh = false) {
            $get = function () use ($userId, $extended) {
                if ($userGroupModel = $this->resolver->getModel('UserGroup', true)) {
                    $now    = Carbon::now();
                    $access = $this->config->get(AccessManager::GROUP_KEY . '/access', ['editor' => 'secondary']);
                    $groups = $userGroupModel::where('user_id', '=', $userId)->where('credits', '>', 0)->where('expires_at', '>', $now)->get();

                    /** @var ModelEx $group */
                    foreach ($groups as $group) {
                        if ($extended) {
                            $attrs     = $group->getAttributes();
                            $info      = ['access' => $access[$group->group_name] ?? 'primary', 'expiry_days' => $now->diffInDays(Carbon::parse($group->expires_at))];
                            $results[] = array_merge($attrs, $info);
                        } else {
                            $results[] = $group->group_name;
                        }
                    }
                }

                return $results ?? [];
            };

            $results = $fresh ? $get() : $this->cache->get("user-groups-$userId-$extended", $get, 3600);

            return $results ?? [];
        }

        public function containsGroup(int $userId, array $matches) {
            $groups = $this->getUserGroups($userId);

            foreach ($matches as $group) {
                if (in_array($group, $groups)) {
                    return $group;
                }
            }

            return false;
        }

        public function clearCache($userId) {
            foreach ([true, false] as $extended) {
                $this->cache->remove("user-groups-$userId-$extended");
            }
        }
    }
}