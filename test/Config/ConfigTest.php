<?php

namespace Test\Config {

    use Auryn\Injector;
    use Minute\Config\Config;
    use Minute\Model\ModelEx;

    class ConfigTest extends \PHPUnit_Framework_TestCase {
        protected function setUp() {
            parent::setUp();

            define('MODEL_DIR', 'Test\Model');
        }

        public function testGet() {
            global $set;

            $mock = new class extends ModelEx {
                public static function where() {
                    return new class {
                        public function first() {
                            $data = new \stdClass();;
                            $data->data_json = json_encode(['foo' => ['bar' => 'baz']]);

                            return $data;
                        }
                    };
                }

                public static function updateOrCreate($type, $data) {
                    global $set;
                    $set[$type['type']] = $data;
                }
            };
            class_alias(get_class($mock), 'Test\Model\MConfig');

            /** @var Config $config */
            $config = (new Injector())->make('Minute\Config\Config');
            $this->assertEquals('baz', $config->get('private/foo/bar'), 'Config::get is working properly');
            $this->assertEquals(false, $config->get('private/foo/bat'), 'Config::get is working properly');

            $config->set('private/test/best', 1, true);
            $this->assertEquals(['data_json' => '{"foo":{"bar":"baz"},"test":{"best":1}}'], $set['private'], 'Config::set is working properly');
        }
    }
}