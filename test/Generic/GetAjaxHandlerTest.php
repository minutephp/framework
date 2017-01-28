<?php

namespace Test\Generic {

    use Auryn\Injector;
    use Minute\Event\ControllerEvent;
    use Minute\Generic\GetAjaxHandler;

    class GetAjaxHandlerTest extends \PHPUnit_Framework_TestCase {

        public function testIndex() {
            /** @var GetAjaxHandler $getAjaxHandler */
            $event = new ControllerEvent(null, ['_props' => '']);
            
            $getAjaxHandler = (new Injector())->make('Minute\Generic\GetAjaxHandler', [':event' => $event]);

        }
    }
}