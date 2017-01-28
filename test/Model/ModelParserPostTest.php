<?php

namespace Test\Model {

    use Auryn\Injector;
    use Minute\Model\ModelParserPost;

    class ModelParserPostTest extends \PHPUnit_Framework_TestCase {

        public function testParse() {
            /** @var ModelParserPost $modelParserPostTest */
            $modelParserPost = (new Injector())->make('Minute\Model\ModelParserPost', [['ModelTwo']]);
            $models          = $modelParserPost->getModels();
            $this->assertEquals(['name' => 'ModelTwo', 'fields' => null, 'alias' => 'ModelTwo'], $models[0], 'Model parsed correctly');

            $modelParserPost = (new Injector())->make('Minute\Model\ModelParserPost', [['ModelOne[first_name, last_name,email] as two']]);
            $models          = $modelParserPost->getModels();
            $this->assertEquals(['name' => 'ModelOne', 'fields' => ['first_name', 'last_name', 'email'], 'alias' => 'two'], $models[0], 'Model parsed correctly');
        }
    }
}