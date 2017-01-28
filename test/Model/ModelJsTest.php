<?php

namespace Test\Model {

    use Auryn\Injector;
    use Minute\Model\ModelJs;

    class ModelJsTest extends \PHPUnit_Framework_TestCase {

        public function testCreateItem() {
            $this->markTestSkipped('ignore');
            /** @var ModelJs $modelJs */
            $modelJs      = (new Injector())->make('Minute\Model\ModelJs');
            $itemTemplate = $modelJs->createItem('test', [['self' => ['alias' => 'many', 'single' => false]], ['self' => ['alias' => 'one', 'single' => true]]]);
            $this->assertContains('this.many = (new ManyArray(this));', $itemTemplate, 'Contains child #1');
            $this->assertContains('this.one = (new OneArray(this)).create();', $itemTemplate, 'Contains child #2');
        }

        public function testCreateItemArray() {
            $this->markTestSkipped('ignore');
            /** @var ModelJs $modelJs */
            $modelJs      = (new Injector())->make('Minute\Model\ModelJs');
            $itemTemplate = $modelJs->createItemArray('test', 'Test', 'test_id', 'test_id');
            $this->assertContains("_super.call(this, TestItem, parent, 'test', 'Test', 'test_id', 'test_id');", $itemTemplate, 'Constructor 1 matches successfully');

            $itemTemplate = $modelJs->createItemArray('test', 'Test', 'test_id');
            $this->assertContains("_super.call(this, TestItem, parent, 'test', 'Test', 'test_id', null);", $itemTemplate, 'Constructor 2 matches successfully');
        }
    }
}