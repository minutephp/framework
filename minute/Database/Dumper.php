<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 12/18/2016
 * Time: 7:46 PM
 */
namespace Minute\Database {

    use Minute\Error\DatabaseError;
    use Minute\File\TmpDir;
    use Minute\Shell\Shell;

    class Dumper {
        /**
         * @var Database
         */
        private $database;
        /**
         * @var Shell
         */
        private $shell;
        /**
         * @var TmpDir
         */
        private $tmpDir;

        /**
         * Dumper constructor.
         *
         * @param Database $database
         * @param Shell $shell
         * @param TmpDir $tmpDir
         */
        public function __construct(Database $database, Shell $shell, TmpDir $tmpDir) {
            $this->database = $database;
            $this->shell    = $shell;
            $this->tmpDir   = $tmpDir;
        }

        public function mysqlDump(bool $structure = true, bool $withData = true, int $limit = 0, bool $tweak = true, array $tables = []) {
            $cnf = $this->tmpDir->getTempDir('mysql') . '/.htpasswd'; //just in case!
            $sql = $this->tmpDir->getTempDir('mysql') . '/.htdump';

            try {
                $local = $this->database->getDsn();
                $cred  = sprintf("[mysqldump]\nuser=%s\npassword=%s\nhost=%s\n\n", $local['username'], $local['password'], $local['host']);

                file_put_contents($cnf, $cred);

                $cnf    = realpath($cnf);
                $params = !$structure ? "--no-create-info " : "";
                $params .= !$withData ? "--no-data " : "";
                $params .= !empty($tables) ? join(' ', $tables) . ' ' : '';
                $params .= !empty($limit) ? " --where=\"1 limit $limit\" " : '';
                $cmd = sprintf('mysqldump --defaults-extra-file="%s" --opt --default-character-set=UTF8 --single-transaction %s %s > "%s"', $cnf, $local['database'], $params, $sql);
                $run = $this->shell->raw($cmd);

                if ($run['code'] === 0) {
                    $dump = file_get_contents($sql);

                    if ($tweak) {
                        $dump = preg_replace('/\)\s+ENGINE=MyISAM/', ') ENGINE=InnoDB', $dump);
                    }
                } else {
                    throw new DatabaseError("Unable to run \"mysqldump\" on localhost. Please make sure \"mysqldump\" and \"mysql\" are in your PATH");
                }
            } finally {
                @unlink($cnf);
                @unlink($sql);
            }

            return $dump ?? '';
        }

        public function mysqlImport(string $dump, string $dbname, array $credentials = []) {
            $cnf = $this->tmpDir->getTempDir('mysql') . '/.htpasswd'; //just in case!
            $sql = $this->tmpDir->getTempDir('mysql') . '/.htdump';

            try {
                $local = !empty($credentials['username']) ? $credentials : $this->database->getDsn();
                $cred  = sprintf("[mysql]\nuser=%s\npassword=%s\nhost=%s\n\n", $local['username'], $local['password'], $local['host'] ?? 'localhost');

                file_put_contents($cnf, $cred);
                file_put_contents($sql, $dump);

                $cnf  = realpath($cnf);
                $cmd  = sprintf('mysql --defaults-extra-file="%s" %s < "%s" 2>&1', realpath($cnf), $dbname, realpath($sql));
                $run  = $this->shell->raw($cmd);
                $pass = $run['code'] === 0;
            } finally {
                @unlink($cnf);
                @unlink($sql);
            }

            return $pass ?? false;
        }
    }
}
