<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/15/2016
 * Time: 5:35 PM
 */

namespace Test\Model {

    use Auryn\Injector;
    use Illuminate\Database\Eloquent\Collection;
    use Illuminate\Database\Schema\Builder;
    use Minute\Model\ModelEx;
    use Minute\Model\ModelExtender;
    use Test\Unit\PHPUnit_Db_Test_Base;

    class ModelExtenderWithDbTest extends PHPUnit_Db_Test_Base {
        protected $detail;
        protected $project;
        protected $comment;

        protected function setUp() {
            parent::setUp();

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

        public function testSingleModelForExtendedConditions() {
            $class = '\Test\Model\Project';

            /** @var ModelEx|Builder $project */
            /** @var Collection $results */

            $project = new $class();
            $results = $project->get();
            $this->markTestSkipped('ignore');
            
            $this->assertEquals(2, $results->count(), 'Project has two rows');

            $project    = new $class();
            $modelProps = ['conditions' => [['project_id', '=', '1']]];
            $project    = $this->getModelExtender()->extend($project, $modelProps);
            $results    = $project->get();
            $this->assertEquals(1, $results->count(), 'Project has one project_id');

            $project    = new $class();
            $modelProps = ['limit' => '1'];
            $project    = $this->getModelExtender()->extend($project, $modelProps);
            $results    = $project->get();
            $this->assertEquals(1, $results->count(), 'Project returns one row as per limit');

            $project    = new $class();
            $modelProps = ['offset' => '2'];
            $project    = $this->getModelExtender()->extend($project, $modelProps);
            $results    = $project->get();
            $this->assertEquals(0, $results->count(), 'Project has no rows after offset 2');

            $project    = new $class();
            $modelProps = ['order' => 'project_id DESC'];
            $project    = $this->getModelExtender()->extend($project, $modelProps);
            $results    = $project->get();
            $this->assertEquals(2, $results[0]->project_id, 'Project order is reversed (project_id DESC)');

            $project    = new $class();
            $modelProps = ['pk' => '3'];
            $project    = $this->getModelExtender()->extend($project, $modelProps);
            $results    = $project->get();
            $this->assertEquals(0, $results[0]->project_id, 'Project id #3 does not exists');

            $project    = new $class();
            $modelProps = ['search' => ['columns' => 'project_id, project_name', 'operator' => '=', 'value' => 'two']];
            $project    = $this->getModelExtender()->extend($project, $modelProps);
            $results    = $project->get();

            $this->assertEquals(1, $results->count(), 'Only one project is found');
            $this->assertEquals('two', $results->first()->project_name, 'Project name matches search');

        }

        public function testModelAndRelationsForExtendedConditions() {
            $projectClass = '\Test\Model\Project';
            $commentClass = '\Test\Model\Comment';
            $detailClass  = '\Test\Model\Detail';

            /** @var ModelEx|Builder $project */
            /** @var Collection $results */

            $project = new $projectClass();
            $project->addRelation('hasMany', 'comment', $commentClass, 'project_id', 'project_id', []);
            $rows = $project->with('comment')->get();

            $this->assertEquals(2, $rows[0]->comment->count(), 'Project #1 has two comments');
            $this->assertEquals(2, $rows[1]->comment->count(), 'Project #2 has two comments');

            $project    = new $projectClass([], $this->database);
            $modelProps = ['conditions' => [['comment_id', '=', '1']]];
            $project->addRelation('hasMany', 'comment', $commentClass, 'project_id', 'project_id', $modelProps);
            $rows = $project->with('comment')->get();

            $this->assertEquals(1, $rows[0]->comment->count(), 'Project #1 has one comment');
            $this->assertEquals(0, $rows[1]->comment->count(), 'Project #2 has 0 comment');

            $project    = new $projectClass();
            $modelProps = ['pk' => '2'];
            $project->addRelation('hasMany', 'comment', $commentClass, 'project_id', 'project_id', $modelProps);
            $rows = $project->with('comment')->get();

            $this->assertEquals(2, $rows->first()->comment->first()->comment_id, 'Project #1 has one comment');

            $project    = new $projectClass();
            $modelProps = ['limit' => '1', 'order' => 'comment_id DESC'];
            $project->addRelation('hasMany', 'comment', $commentClass, 'project_id', 'project_id', $modelProps);
            $rows = $project->get();
            foreach ($rows as $row) {
                $row->load('comment');
            }

            $this->assertEquals(1, $rows[0]->comment->count(), 'Project #1 has one comment');
            $this->assertEquals(1, $rows[1]->comment->count(), 'Project #2 has 0 comment');
            $this->assertEquals(2, $rows->first()->comment->first()->comment_id, 'Comments are sorted in reversed (comment_id DESC)');

            $modelParser = (new Injector())->make('Minute\Model\ModelParserGet', [['projects[project_id] as project', 'comments[project.project_id][10]', 'details[comments.comment_id][2] as detail']]);
            $relations   = $modelParser->getParentsWithChildren();

            $project    = new $projectClass();
            $modelProps = ['search' => ['columns' => 'project_name, comments.comment, comments.detail.text', 'operator' => '=', 'value' => 'detail four'], 'relations' => $relations['project']];
            $project->addRelation('hasMany', 'comments', $commentClass, 'project_id', 'project_id', []);
            $project = $this->getModelExtender()->extend($project, $modelProps);
            $rows    = $project->with('comments')->get();
            $this->assertEquals(2, $rows->first()->project_id, 'Detail four belongs to project_id #2');
        }

        protected function getModelExtender() {
            return (new ModelExtender());
        }

        protected function getDataSet() {
            return $this->loadData(__FILE__);
        }
    }
}