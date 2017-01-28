<?php

namespace Test\Session {

    use Auryn\Injector;
    use Minute\Event\AuthEvent;
    use Minute\Session\Session;

    class SessionTest extends \PHPUnit_Framework_TestCase {

        public function testCheckAccess() {
            /** @var Session $session */
            $session = (new Injector())->make('Minute\Session\Session');

            $event = new AuthEvent(false);
            $session->checkAccess($event);
            $this->assertTrue($event->isAuthorized(), 'Allowed when access is set to false');

            $event = new AuthEvent(true);
            $session->checkAccess($event);
            $this->assertFalse($event->isAuthorized(), 'Not allowed when access is set to true');

            $event = new AuthEvent('admin');
            $session->checkAccess($event);
            $this->assertFalse($event->isAuthorized(), 'Not allowed when access is set to "admin"');
        }
    }
}