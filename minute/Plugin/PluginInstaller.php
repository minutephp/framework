<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 9/20/2016
 * Time: 3:25 PM
 */
namespace Minute\Plugin {

    use App\Config\BootLoader;
    use Minute\Shell\Shell;

    class PluginInstaller {
        public function __construct(BootLoader $bootLoader, Shell $shell) {
            set_time_limit(0);
            $this->bootLoader = $bootLoader;
            $this->shell      = $shell;
        }

        public function install(array $plugins, string $operation = 'require', bool $capture = false) {
            chdir($this->bootLoader->getBaseDir());

            $output = call_user_func([$this->shell, $capture ? 'capture' : 'run'], 'composer --no-interaction -o -vv %s %s', $operation, join(' ', array_map('escapeshellarg', $plugins)));

            return $capture ? $output : $output['code'] === 0;
        }
    }
}