<?php

namespace Test\User {

    use Auryn\Injector;
    use Illuminate\Database\Eloquent\Collection;
    use Minute\Model\ModelEx;
    use Minute\User\UserInfo;

    class UserInfoTest extends \PHPUnit_Framework_TestCase {
        protected function setUp() {
            parent::setUp();

            define('MODEL_DIR', 'Test\Model');
        }

        public function testGetUserGroups() {
            $mock = new class extends ModelEx {
                public static function where() {
                    return new class ($data) {
                        /** @var  Collection */
                        protected $data;

                        public function __construct($data) {
                            $data       = json_decode(json_encode([['group_name' => 'power'], ['group_name' => 'editor']]));
                            $this->data = new Collection($data);
                        }

                        public function first() {
                            return $this->data->first();
                        }

                        public function get() {
                            return $this->data->all();
                        }
                    };
                }
            };

            class_alias(get_class($mock), 'Test\Model\MUserGroup');

            /** @var UserInfo $userInfo */
            $userInfo = (new Injector())->make('Minute\User\UserInfo');
            $groups   = $userInfo->getUserGroups(1);
            $this->assertEquals(['power', 'editor'], $groups, 'Groups match');
        }
    }
}