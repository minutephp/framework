<?php

namespace Test\View {

    use Auryn\Injector;
    use Minute\View\Redirection;

    class RedirectionTest extends \PHPUnit_Framework_TestCase {

        public function testRedirect() {
            /** @var Redirection $redirection */
            $redirection = (new Injector())->make('Minute\View\Redirection', ['/home', ['foo' => 'bar'], false]);
            $this->expectOutputString('<script>location = "/home?foo=bar";</script>');
            $redirection->redirect();
        }
    }
}