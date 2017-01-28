<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/21/2016
 * Time: 9:52 AM
 */
namespace Minute\User {

    use Minute\Cache\QCache;
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
         * UserInfo constructor.
         *
         * @param Resolver $resolver
         * @param QCache $cache
         */
        public function __construct(Resolver $resolver, QCache $cache) {
            $this->resolver = $resolver;
            $this->cache    = $cache;
        }

        public function getUserGroups($userId, $extended = false, $fresh = false) {
            $get = function () use ($userId, $extended) {
                if ($userGroupModel = $this->resolver->getModel('UserGroup', true)) {
                    $groups = $userGroupModel::where('user_id', '=', $userId)->where('credits', '>', 0)->get();

                    /** @var ModelEx $group */
                    foreach ($groups as $group) {
                        $results[] = $extended ? $group->getAttributes() : $group->group_name;
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