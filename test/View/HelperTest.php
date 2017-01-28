<?php

namespace Test\View {

    use Auryn\Injector;
    use Minute\View\Helper;

    class HelperTest extends \PHPUnit_Framework_TestCase {
        protected function setUp() {
            parent::setUp();

            define('VIEW_DIR', '\Test\View\Sample');
        }

        public function testHelperTemplate() {
            /** @var Helper $helper */
            $helper = (new Injector())->make('Minute\View\Helper', ['D/e']);
            $this->assertEquals('[Helper 1!]', $helper->getTemplate(), 'Helper is compiling correctly');
        }
    }
}