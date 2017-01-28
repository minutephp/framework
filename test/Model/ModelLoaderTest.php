<?php

namespace Test\Model {

    use Auryn\Injector;
    use Minute\Model\ModelEx;
    use Minute\Model\ModelExtender;
    use Minute\Model\ModelLoader;

    class ModelLoaderTest extends \PHPUnit_Framework_TestCase {
        protected $project;

        protected function setUp() {
            parent::setUp();

            define('MODEL_DIR', 'Test\Model');
            define('DEBUG_MODE', true);

            if (!class_exists('\Test\Model\Project')) {
                $this->project = new class extends ModelEx {
                    protected $table      = 'projects';
                    protected $primaryKey = 'project_id';
                };

                class_alias(get_class($this->project), '\Test\Model\Project');
            }
        }

        public function testMetadataIsAppliedToParentModel() {
            $modelExtender = $this->getMockBuilder(ModelExtender::class)->disableOriginalConstructor()->setMethods(['extend'])->getMock();
            $modelExtender->expects($this->once())->method('extend')->willReturnCallback(function ($model, $args) {
                $this->assertEquals(['offset' => 0, 'limit' => 2, 'conditions' => [['project', '=', '3']]], $args);

                return $model;
            });

            $nodes = ['project' => ['self' => ['name' => 'Project', 'offset' => 0, 'limit' => 2, 'conditions' => [['project', '=', '3']]]]];

            /** @var ModelLoader $modelLoader */
            $modelLoader = (new Injector())->make('Minute\Model\ModelLoader', [':modelExtender' => $modelExtender]);
            $modelLoader->createRelations($nodes['project']);
        }
    }
}