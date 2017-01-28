<?php

namespace Test\Crypto {

    use Auryn\Injector;
    use Firebase\JWT\ExpiredException;
    use Minute\Crypto\JwtEx;
    use stdClass;

    class JwtExTest extends \PHPUnit_Framework_TestCase {

        public function testEncodeAndDecode() {
            /** @var JwtEx $jwtEx */
            $jwtEx   = (new Injector())->make(JwtEx::class);
            $data    = new stdClass(['foo' => 'bar']);
            $encode  = $jwtEx->encode($data);
            $decoded = $jwtEx->decode($encode);

            $this->assertEquals($data, $decoded, 'Encoding / Decoding is working okay');
        }

        public function testExpiredToken() {
            /** @var JwtEx $jwtEx */
            $jwtEx  = (new Injector())->make(JwtEx::class);
            $data   = new stdClass(['foo' => 'bar']);
            $encode = $jwtEx->encode($data, -1);

            $this->expectException(ExpiredException::class);
            $jwtEx->decode($encode);
        }
    }
}