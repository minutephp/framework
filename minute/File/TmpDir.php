<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 7/8/2016
 * Time: 11:35 AM
 */
namespace Minute\File {

    use App\Config\BootLoader;
    use Illuminate\Support\Str;

    class TmpDir {
        /**
         * @var BootLoader
         */
        private $bootLoader;

        /**
         * TmpDir constructor.
         *
         * @param BootLoader $bootLoader
         */
        public function __construct(BootLoader $bootLoader) {
            $this->bootLoader = $bootLoader;
        }

        public function getTempDir($suffix = '') {
            $dir = sprintf('%s%stmp%s', $this->bootLoader->getBaseDir(), DIRECTORY_SEPARATOR, $suffix ? "/$suffix" : '');

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            return $dir;
        }

        public function getTempFile($ext = 'tmp') {
            return sprintf('%s%s%s.%s', $this->getTempDir(), DIRECTORY_SEPARATOR, Str::random(10), ltrim($ext, '.'));
        }
    }
}