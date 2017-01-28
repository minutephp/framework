<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/14/2016
 * Time: 2:39 PM
 */

namespace Test\Model {

    use Auryn\Injector;
    use Minute\Model\ModelParserGet;

    class ModelParserGetTest extends \PHPUnit_Framework_TestCase {

        public function testModelParsingWorkingOkay() {
            /** @var ModelParserGet $parser */
            $parser = (new Injector())->make('Minute\Model\ModelParserGet', [['test["column" = "4"] as d order by rand() asc']]);
            $models = $parser->getModels();
            $this->assertEquals([
                'name' => 'test',
                'match' => '"column" = "4"',
                'limit' => 1,
                'alias' => 'd',
                'order' => 'rand() asc',
                'single' => true,
                'matchInfo' => [
                    'type' => 'string',
                    'col' => 'column',
                    'value' => '4',
                ],
            ], $models[0], 'Parsing model with match and without specific limit');

            $parser = (new Injector())->make('Minute\Model\ModelParserGet', [['test[test.col_id][10] as d order by rand() asc']]);
            $models = $parser->getModels();
            $this->assertEquals([
                'name' => 'test',
                'match' => 'test.col_id',
                'limit' => 10,
                'alias' => 'd',
                'order' => 'rand() asc',
                'single' => false,
                'matchInfo' => [
                    'type' => 'relational',
                    'column' => 'col_id',
                    'parent' => 'test',
                    'key' => 'col_id'
                ],
            ], $models[0], 'Parsing model with match and specific limit');

            $parser = (new Injector())->make('Minute\Model\ModelParserGet', [['test[test_id = project.project_id || "3"][10] as d order by rand() asc']]);
            $models = $parser->getModels();
            $this->assertEquals([
                'name' => 'test',
                'match' => 'test_id = project.project_id || "3"',
                'limit' => 10,
                'alias' => 'd',
                'order' => 'rand() asc',
                'single' => false,
                'matchInfo' => [
                    'type' => 'relational',
                    'column' => 'test_id',
                    'parent' => 'project',
                    'key' => 'project_id',
                    'default' => '3'
                ],
            ], $models[0], 'Parsing model with match and specific limit and default value');

            $parser = (new Injector())->make('Minute\Model\ModelParserGet', [['hello']]);
            $models = $parser->getModels();
            $this->assertEquals([
                'name' => 'hello',
                'match' => null,
                'limit' => 1,
                'alias' => 'hello',
                'order' => '',
                'single' => true,
            ], $models[0], 'Parsing model without anything');

            $parser = (new Injector())->make('Minute\Model\ModelParserGet', [['hello[3] as test']]);
            $models = $parser->getModels();
            $this->assertEquals([
                'name' => 'hello',
                'match' => null,
                'limit' => 3,
                'alias' => 'test',
                'order' => '',
                'single' => false,
            ], $models[0], 'Parsing model without match but limit');

            $parser = (new Injector())->make('Minute\Model\ModelParserGet', [['hello[] as test']]);
            $models = $parser->getModels();
            $this->assertEquals([
                'name' => 'hello',
                'match' => null,
                'limit' => 20,
                'alias' => 'test',
                'order' => '',
                'single' => false,
            ], $models[0], 'Parsing model without match and default limit');

        }

        public function testGetParentsWithChildren() {
            /** @var ModelParserGet $parser */
            $args    = ['projects[project_id] as project', 'comments[project.project_id][10]', 'users[comments.user_id] as commentator', 'likes[project.project_id][]',
                        'details[commentator.commentator]', 'archives[10]', 'more[archives.archive_id]'];
            $parser  = (new Injector())->make('Minute\Model\ModelParserGet', [$args]);
            $parents = $parser->getParentsWithChildren();

            $this->assertArrayHasKey('comments', $parents['project']['children'], 'Project has comments');
            $this->assertArrayHasKey('likes', $parents['project']['children'], 'Project has likes');
            $this->assertArrayHasKey('commentator', $parents['project']['children']['comments']['children'], 'Comments has commentator');
            $this->assertArrayHasKey('details', $parents['project']['children']['comments']['children']['commentator']['children'], 'Commentator has details');
            $this->assertArrayHasKey('more', $parents['archives']['children'], 'Archives has more');
        }

        public function testGetParentWithExternalParams() {
            $args    = ['projects[param]', 'children[projects.project_id]'];
            $parser  = (new Injector())->make('Minute\Model\ModelParserGet', [$args]);
            $parents = $parser->getParentsWithChildren();

            echo '';
        }
    }
}