<?php

namespace Test\View {

    use Auryn\Injector;
    use Minute\Event\Dispatcher;
    use Minute\Event\ViewEvent;
    use Minute\View\Helper;
    use Minute\View\ViewParser;

    class ViewTest extends \PHPUnit_Framework_TestCase {
        protected function setUp() {
            parent::setUp();

            define('VIEW_DIR', '\Test\View\Sample');
        }

        public function testLoadTemplate() {
            /** @var ViewParser $view */

            $view = (new Injector())->make('Minute\View\ViewParser', ['foo' => 'bar']);
            $view->loadTemplate('A\B\C');
            $template = $view->getTemplate();
            $this->assertEquals('<html><body><inner>Content</inner></body></html>', $template, 'Template is compiling correctly');
        }

        public function testRendering() {
            /** @var ViewParser $view */
            /** @var Dispatcher $dispatcher */
            $dispatcher = (new Injector())->make('Minute\Event\Dispatcher');
            $dispatcher->listen('one.plus.one', function (ViewEvent $event) {
                $event->setContent('2');
            });

            $injector = new Injector();
            $injector->define('Dispatcher', [$dispatcher]);
            $injector->share($dispatcher);

            $view = $injector->make('Minute\View\ViewParser', ['foo' => 'bar']);
            $view->loadTemplate('A\HasEvent');

            $output = $view->render();
            $this->assertEquals('<html><body>1 + 1 = 2.</body></html>', $output, 'Output is rendering correctly');

            $view->setHelpers([new Helper('D/e', Helper::POSITION_BODY)]);
            $output = $view->render();
            $this->assertEquals('<html><body>1 + 1 = 2.[Helper 1!]</body></html>', $output, 'Output is rendering correctly');
        }
    }
}