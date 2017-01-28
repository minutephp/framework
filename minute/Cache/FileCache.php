<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 11/27/2016
 * Time: 3:16 AM
 */
namespace Minute\Cache {

    use App\Config\BootLoader;

    class FileCache extends QCache {
        public function __construct(string $type = 'file', string $namespace = '', BootLoader $bootLoader) {
            parent::__construct($type, $namespace, $bootLoader);
        }
    }
}