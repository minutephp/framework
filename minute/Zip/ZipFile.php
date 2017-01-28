<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 8/1/2016
 * Time: 8:23 PM
 */
namespace Minute\Zip {

    use Illuminate\Support\Str;
    use Minute\File\TmpDir;
    use Minute\Utils\PathUtils;
    use StringTemplate\Engine;
    use ZipArchive;

    class ZipFile {
        /**
         * @var ZipArchive
         */
        private $zipArchive;
        /**
         * @var TmpDir
         */
        private $tmpDir;
        /**
         * @var PathUtils
         */
        private $utils;
        /**
         * @var Engine
         */
        private $tagReplacer;

        /**
         * ZipFile constructor.
         *
         * @param ZipArchive $zipArchive
         * @param TmpDir $tmpDir
         * @param PathUtils $utils
         * @param Engine $tagReplacer
         */
        public function __construct(ZipArchive $zipArchive, TmpDir $tmpDir, PathUtils $utils, Engine $tagReplacer) {
            $this->zipArchive  = $zipArchive;
            $this->tmpDir      = $tmpDir;
            $this->utils       = $utils;
            $this->tagReplacer = $tagReplacer;
        }

        public function extract($zipFile, $dest) {
            $zip = $this->zipArchive;
            $res = $zip->open($zipFile);

            if ($res === TRUE) {
                $zip->extractTo($dest);
                $zip->close();

                return true;
            }

            return false;
        }

        /**
         * @param array $contents - ['san.txt' => '@san.txt', 'string.txt' => 'anything can be here']
         * @param string $zipFile
         * @param array $tags
         *
         * @return string
         */
        public function create(array $contents = [], string $zipFile = '', array $tags = []) {
            $zip  = new ZipArchive;
            $file = sprintf('%s/%s.zip', $this->tmpDir->getTempDir('zip'), $this->utils->filename($zipFile) ?: Str::random(6));
            $res  = $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            foreach ($contents as $filename => $data) {
                if (strpos($data, '@') === 0) { //file attachment
                    $data = file_get_contents(substr($data, 1));
                }

                if (!empty($tags)) {
                    $data = $this->tagReplacer->render($data, $tags);
                }

                $zip->addFromString($filename, $data = preg_replace("/\r\n/", "\n", $data));
            }

            $zip->close();

            return filesize($file) > 0 ? $file : false;
        }
    }
}