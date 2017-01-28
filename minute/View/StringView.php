<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 10/1/2016
 * Time: 6:03 AM
 */
namespace Minute\View {

    use Minute\Resolver\Resolver;

    class StringView extends View {
        /**
         * @var Resolver
         */
        private $resolver;
        /**
         * @var Compiler
         */
        private $compiler;

        /**
         * ViewFromString constructor.
         *
         * @param Resolver $resolver
         * @param Compiler $compiler
         */
        public function __construct(Resolver $resolver, Compiler $compiler) {
            parent::__construct('', [], false);

            $this->resolver = $resolver;
            $this->compiler = $compiler;
        }

        public function setContentWithLayout(string $textContent, string $layout = 'Global', array $vars = []) {
            $this->compiler->setContents($textContent);

            if ($path = $this->resolver->getView("Layout\\$layout")) {
                $textContent = $this->compiler->compile([$path]);
            }

            $this->setVars($vars);
            $this->setContent($textContent);

            return $this;
        }
    }
}