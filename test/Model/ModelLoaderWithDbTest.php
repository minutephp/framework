<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/15/2016
 * Time: 5:35 PM
 */

namespace Test\Model {

    use Auryn\Injector;
    use Minute\Model\ModelEx;
    use Minute\Model\ModelLoader;
    use Test\Unit\PHPUnit_Db_Test_Base;

    class ModelLoaderTestWithDb extends PHPUnit_Db_Test_Base {
        protected $detail;
        protected $project;
        protected $comment;

        public static function setUpBeforeClass() {
            parent::setUpBeforeClass();

            define('DEBUG_MODE', true);
        }

        protected function setUp() {
            parent::setUp();

            define('MODEL_DIR', 'Test\Model');

            if (!class_exists('\Test\Model\Project')) {
                $this->project = new class extends ModelEx {
                    protected $table      = 'projects';
                    protected $primaryKey = 'project_id';
                };

                class_alias(get_class($this->project), '\Test\Model\Project');
            }

            if (!class_exists('\Test\Model\Comment')) {
                $this->comment = new class extends ModelEx {
                    protected $table      = 'comments';
                    protected $primaryKey = 'comment_id';
                };

                class_alias(get_class($this->comment), '\Test\Model\Comment');
            }

            if (!class_exists('\Test\Model\Detail')) {
                $this->detail = new class extends ModelEx {
                    protected $table      = 'details';
                    protected $primaryKey = 'detail_id';
                };

                class_alias(get_class($this->detail), '\Test\Model\Detail');
            }
        }

        public function testModelLoaderWithActualData() {
            $modelParser = (new Injector())->make('Minute\Model\ModelParserGet', [['projects[project_id][2] as project', 'comments[project.project_id][2]',
                                                                                   'details[comments.comment_id][2] as detail']]);

            $parents = $modelParser->getParentsWithChildren();

            $this->assertTableRowCount('projects', 2, 'There is one project');

            /** @var ModelLoader $modelLoader */
            $modelLoader = (new Injector())->make('Minute\Model\ModelLoader');
            $params      = $modelLoader->loadModels($parents);

            $this->assertArrayHasKey('project', $params, 'Project exists in parents');
            $this->assertEquals(2, $params['project']->count(), 'Project has two items');
            $this->assertEquals(2, $params['project']->count(), 'Project has two items');
            $this->assertEquals(2, $params['project'][0]->comments->count(), 'First project has two comments');
            $this->assertEquals(2, $params['project'][1]->comments->count(), 'Second project has two comments');
            $this->assertEquals(2, $params['project'][0]->comments[0]->detail->count(), 'First project comment has two details');
            $this->assertEquals(1, $params['project'][1]->comments[1]->detail->count(), 'First project comment has two details');
        }

        public function testModelExtenderWithActualDataAndConstraints() {
            $modelParser = (new Injector())->make('Minute\Model\ModelParserGet', [['projects[project_id] as project', 'comments[project.project_id][1]']]);
            $parents     = $modelParser->getParentsWithChildren();

            $this->assertTableRowCount('projects', 2, 'There is one project');

            $modelProps = [
                'project' => ['conditions' => [['project_id', '=', '1'], ['project_id', '=', '2']], 'limit' => '2']
            ];

            /** @var ModelLoader $modelLoader */
            $modelLoader = (new Injector())->make('Minute\Model\ModelLoader');
            $params      = $modelLoader->loadModels($parents, $modelProps);
            $this->markTestSkipped('ignore');
            $this->assertEquals(0, $params['project']->count(), 'Both conditions are executing');
        }

        protected function getDataSet() {
            return $this->loadData(__FILE__);
        }
    }
}