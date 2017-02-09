<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 1/31/2017
 * Time: 10:35 PM
 */
namespace Minute\Password {

    class PasswordHash {
        public function getHashedPassword(string $password): string {
            return password_hash($password, PASSWORD_DEFAULT);
        }
    }
}