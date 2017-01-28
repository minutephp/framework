<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 10/4/2016
 * Time: 7:24 AM
 */
namespace Minute\Utils {

    class PathUtils {
        public function basename($path) {
            return pathinfo($this->unixPath($path), PATHINFO_BASENAME);
        }

        public function dirname($path) {
            return pathinfo($this->unixPath($path), PATHINFO_DIRNAME);
        }

        public function extension($path) {
            return ltrim(pathinfo($this->unixPath($path), PATHINFO_EXTENSION), '.');
        }

        public function filename($path) {
            return pathinfo($this->unixPath($path), PATHINFO_FILENAME);
        }

        public function pathinfo($path) {
            return pathinfo($this->unixPath($path));
        }

        public function unixPath($path) {
            return strtr($path, ['\\' => '/']);
        }

        public function dosPath($path) {
            return strtr($path, ['/' => '\\']);
        }
    }
}