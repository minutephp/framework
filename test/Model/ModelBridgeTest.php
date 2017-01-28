<?php

namespace Test\Model {

    use Auryn\Injector;
    use Minute\Model\ModelBridge;
    use Minute\Model\ModelEx;
    use Minute\Model\ModelParserGet;

    class ModelBridgeTest extends \PHPUnit_Framework_TestCase {
        protected function setUp() {
            parent::setUp();

            define('MODEL_DIR', 'Test\Model');

            if (!class_exists('\Test\Model\Blog')) {
                $blog = new class extends ModelEx {
                    protected $table      = 'blogs';
                    protected $primaryKey = 'blog_id';
                };

                class_alias(get_class($blog), '\Test\Model\Blog');
            }

            if (!class_exists('\Test\Model\Post')) {
                $post = new class extends ModelEx {
                    protected $table      = 'posts';
                    protected $primaryKey = 'post_id';
                };

                class_alias(get_class($post), '\Test\Model\Post');
            }

            if (!class_exists('\Test\Model\Comment')) {
                $comment = new class extends ModelEx {
                    protected $table      = 'comments';
                    protected $primaryKey = 'comment_id';
                };

                class_alias(get_class($comment), '\Test\Model\Comment');
            }

            if (!class_exists('\Test\Model\User')) {
                $user = new class extends ModelEx {
                    protected $table      = 'users';
                    protected $primaryKey = 'user_id';
                };

                class_alias(get_class($user), '\Test\Model\User');
            }
        }

        public function testModelToJsClasses() {
            /** @var ModelParserGet $modelParser */
            $models      = ['blogs[2]', 'posts[blogs.blog_id][2] as stories', 'comments[blogs.blog_id] as comment', 'users[stories.post_id] as teller'];
            $modelParser = (new Injector())->make('Minute\Model\ModelParserGet', [$models]);
            $parents     = $modelParser->getParentsWithChildren();

            $this->assertArrayHasKey('blogs', $parents, 'Parents has blogs key');

            /** @var ModelBridge $modelBridge */
            $modelBridge = (new Injector())->make('Minute\Model\ModelBridge');
            $template    = $modelBridge->modelToJsClasses($parents['blogs']);

            $this->markTestSkipped('to be fixed');
            $this->assertContains('BlogItem = (function (_super) {', $template, 'BlogItem is present');
            $this->assertContains('this.stories = (new StoryArray(this));', $template, 'BlogItem has stories');
            $this->assertContains('this.comment = (new CommentArray(this)).create();', $template, 'BlogItem has a comment item');
            $this->assertContains("_super.call(this, BlogItem, parent, 'blogs', 'Blog', 'blog_id', null);", $template, 'BlogItemArray is correctly initialized');
            $this->assertContains("this.teller = (new TellerArray(this)).create();", $template, 'StoryItem has teller item');
            $this->assertContains("this.teller = (new TellerArray(this)).create();", $template, 'StoryItem has teller item');
            $this->assertContains("_super.call(this, StoryItem, parent, 'stories', 'Post', 'post_id', 'blog_id');", $template, 'StoryItemArray is correctly initialized');
            $this->assertContains("_super.call(this, CommentItem, parent, 'comment', 'Comment', 'comment_id', 'blog_id');", $template, 'CommentItemArray is correctly initialized');

        }
    }
}