<?php

namespace Test\Controller\Generic {

    use App\Controller\Generic\Page;
    use Auryn\Injector;

    class PageTest extends \PHPUnit_Framework_TestCase {

        public function testGetViewPath() {
            /** @var Page $page */
            $page = (new Injector())->make('App\Controller\Generic\Page');
            $view = $page->getViewPath('/admin/pages');
            $this->assertEquals('Admin/Pages', $view, 'View path matches');

            $view = $page->getViewPath('/admin/themes/components/list/{theme_id}');
            $this->assertEquals('Admin/Themes/ComponentsList', $view, 'View path matches');

            $view = $page->getViewPath('/list');
            $this->assertEquals('List', $view, 'View path matches');
        }
    }
}