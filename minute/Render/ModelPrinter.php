<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/22/2016
 * Time: 8:14 AM
 */
namespace Minute\Render {

    use Minute\Event\ViewEvent;
    use Minute\Model\ModelBridge;
    use Minute\Model\ModelLoader;

    class ModelPrinter {
        /**
         * @var ModelBridge
         */
        private $modelBridge;
        /**
         * @var ModelLoader
         */
        private $modelLoader;

        /**
         * ModelPrinter constructor.
         *
         * @param ModelLoader $modelLoader
         * @param ModelBridge $modelBridge
         */
        public function __construct(ModelLoader $modelLoader, ModelBridge $modelBridge) {
            $this->modelLoader = $modelLoader;
            $this->modelBridge = $modelBridge;
        }

        public function importModels(ViewEvent $event) {
            $parents     = $event->getView()->get('_parents');
            $jsClasses   = $this->modelToJsClasses($parents);
            $modelAsJson = $this->modelToJsonData($parents, $event->getView()->get('_models'));
            $template    = '';

            if (!empty($jsClasses)) {
                $template = sprintf("\n(function() { \n\t%s })();\n", $jsClasses);
            }

            if (!empty($modelAsJson)) {
                $template .= sprintf("\nMinute.Loader = function(\$scope) { \n\t%s };\n", $modelAsJson);
            }

            $event->setContent($template ? sprintf('<scr' . 'ipt>%s</script>', $template) : '');
        }

        protected function modelToJsClasses($parents) {
            $jsClasses = '';

            foreach ($parents as $parent) {
                $jsClasses .= $this->modelBridge->modelToJsClasses($parent);
            }

            return $jsClasses;
        }

        protected function modelToJsonData($parents, $preLoaded) {
            $modelData = '';

            if ($models = $preLoaded ?? $this->modelLoader->loadModels($parents)) {
                foreach ($models as $name => $model) {
                    $modelData .= $this->modelBridge->modelToJsData($model, $name);
                }
            }

            return $modelData;
        }
    }
}