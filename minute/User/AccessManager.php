<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/21/2016
 * Time: 7:46 AM
 */
namespace Minute\User {

    use Minute\Config\Config;

    class AccessManager {
        const GROUP_KEY = "groups";
        /**
         * @var Config
         */
        private $config;

        /**
         * AccessManager constructor.
         *
         * @param Config $config
         */
        public function __construct(Config $config) {
            $this->config = $config;
            $this->groups = $this->config->get(self::GROUP_KEY, ['trial' => [], 'paid' => ['trial'], 'admin' => ['paid'], 'editor' => []]);
        }

        /**
         * @param string $requiredGroup - the level of access desired
         * @param array $userGroups     - the groups to which the user is subscribed
         *
         * @return bool
         */
        public function belongsToGroup(string $requiredGroup, array $userGroups) {
            if (in_array('admin', $userGroups)) {
                return true;
            }

            $hasAccess = function ($myGroup, $visited = []) use ($requiredGroup, &$hasAccess) {
                $visited[] = $myGroup;

                if ($requiredGroup === $myGroup) {
                    return true;
                }

                foreach ($this->groups[$myGroup] ?? [] as $group) {
                    if (!in_array($group, $visited) && $hasAccess($group, $visited)) {
                        return true;
                    }
                }

                return false;
            };

            foreach ($userGroups as $userGroup) {
                if ($hasAccess($userGroup)) {
                    return true;
                }
            }

            return false;
        }
    }
}