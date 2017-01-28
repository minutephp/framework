<?php

namespace Test\Routing {

    use Auryn\Injector;
    use Minute\Model\Permission;
    use Minute\Routing\RouteEx;

    class RouteExTest extends \PHPUnit_Framework_TestCase {

        public function testPermissionForModel() {
            /** @var RouteEx $routeEx */
            $routeEx = (new Injector())->make('Minute\Routing\RouteEx', ['/', ['key' => 'value']]);

            foreach (['Create', 'Read', 'Update', 'Delete'] as $type) {
                $expected = (($type == 'Read') || ($type == 'Update')) ? Permission::SAME_USER : Permission::NOBODY;
                call_user_func_array([$routeEx, "set{$type}Permission"], ['modelOne', Permission::EVERYONE]);

                $this->assertEquals('value', $routeEx->getDefault('key'), 'All keys are being preserved');
                $this->assertEquals(Permission::EVERYONE, call_user_func([$routeEx, "get{$type}Permission"], 'modelOne'), 'ModelOne has correct read permissions');
                $this->assertEquals($expected, call_user_func([$routeEx, "get{$type}Permission"], 'modelTwo'), 'ModelTwo has correct read permissions');
            }
        }

        public function testAllPermissions() {
            /** @var RouteEx $routeEx */
            $routeEx = (new Injector())->make('Minute\Routing\RouteEx', ['/', ['key' => 'value']]);
            $routeEx->setAllPermissions('modelOne', Permission::EVERYONE);

            foreach (['Create', 'Read', 'Update', 'Delete'] as $type) {
                $this->assertEquals(Permission::EVERYONE, call_user_func([$routeEx, "get{$type}Permission"], 'modelOne'), 'ModelOne has correct read permissions');
            }
        }
    }
}