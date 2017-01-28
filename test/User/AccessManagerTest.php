<?php

namespace Test\User {

    use Auryn\Injector;
    use Minute\Config\Config;
    use Minute\User\AccessManager;

    class AccessManagerTest extends \PHPUnit_Framework_TestCase {

        public function testBelongsToGroup() {
            $config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->setMethods(['get'])->getMock();
            $config->expects($this->once())->method('get')->will($this->returnValue(['trial' => [], 'paid' => ['trial'], 'admin' => ['paid'], 'editor' => []]));

            /** @var AccessManager $accessManager */
            $accessManager = (new Injector())->make('Minute\User\AccessManager', [':config' => $config]);

            $authorized = $accessManager->belongsToGroup('trial', ['editor']);
            $this->assertFalse($authorized, 'Editor does not have access to trial');

            $authorized = $accessManager->belongsToGroup('trial', ['paid']);
            $this->assertTrue($authorized, 'Paid does have access to trial');

            $authorized = $accessManager->belongsToGroup('trial', ['admin']);
            $this->assertTrue($authorized, 'Admin does have access to trial');

            $authorized = $accessManager->belongsToGroup('paid', ['paid']);
            $this->assertTrue($authorized, 'Paid does have access to paid');

            $authorized = $accessManager->belongsToGroup('admin', ['paid']);
            $this->assertFalse($authorized, 'Paid does not have access to admin');

            $authorized = $accessManager->belongsToGroup('editor', ['admin']);
            $this->assertFalse($authorized, 'Admin does not have access to editor');
        }
    }
}