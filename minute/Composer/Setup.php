<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 9/17/2016
 * Time: 5:57 AM
 */
namespace Minute\Composer {

    class Setup {
        public static function setupPlugin($event, $type, $baseDir) {
            $operation  = $type === 'uninstall' ? 'uninstall' : 'install';
            $pluginName = $event->getOperation()->getPackage();
            $installDir = $event->getComposer()->getInstallationManager()->getInstallPath($pluginName);
            $vendorDir  = $baseDir . '/vendor';

            if ($pluginDir = realpath(sprintf('%s/plugin', $installDir))) {
                if ($pluginMigrationDir = realpath(sprintf('%s/migrations/%s', $pluginDir, $operation))) {
                    $appMigrationDir = sprintf('%s/db/migrations', $baseDir);

                    if (!is_dir($appMigrationDir)) {
                        @mkdir($appMigrationDir, 0777, true);
                    }

                    foreach (glob("$pluginMigrationDir/*.php") as $migration) {
                        if (copy($migration, sprintf('%s/%s', $appMigrationDir, basename($migration)))) {
                            $filesCopied = true;
                        }
                    }

                    if (!empty($filesCopied)) {
                        self::migrate($baseDir);
                    }
                }

                if ($scriptsDir = realpath(sprintf('%s/scripts', $pluginDir))) {
                    foreach (glob("$scriptsDir/setup -*.php") as $script) {
                        $cmd = sprintf('php "%s" --operation="%s" --install-dir="%s" --vendor-dir="%s"', realpath($script), $operation, $installDir, $vendorDir);

                        echo "Running '$script': $cmd\n";
                        self::run($cmd, $capture);
                    }
                }
            }

            return true;
        }

        public static function migrate($baseDir) {
            $vendorDir = $baseDir . '/vendor';
            $dbConfig  = realpath($baseDir . '/app/Config/db-config');

            if ($dbConfig) {
                chdir($baseDir);
                $cmd = sprintf('%s migrate -n -vvv', realpath("$vendorDir/bin/phinx"), realpath("$vendorDir/phinx.php"));

                echo "Running migrations: $cmd\n";

                return self::run($cmd, $capture);
            }

            return false;
        }

        public static function run($cmd, &$capture) {
            $capture = exec("$cmd 2>&1", $code);

            return $code === 0;
        }
    }
}