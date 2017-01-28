<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 4:28 PM
 */
namespace Minute\Database {

    use App\Config\BootLoader;
    use Illuminate\Database\Capsule\Manager;
    use Illuminate\Database\Events\QueryExecuted;
    use Minute\Cache\QCache;
    use Minute\Error\DatabaseError;
    use Minute\Event\Dispatcher;
    use Minute\File\TmpDir;
    use Minute\Log\LoggerEx;
    use Monolog\Handler\StreamHandler;

    class Database {
        /**
         * @var string
         */
        protected $logFile;
        /**
         * @var \Illuminate\Database\Connection
         */
        protected $connection;
        /**
         * @var Manager
         */
        private $capsule;
        /**
         * @var BootLoader
         */
        private $bootLoader;
        /**
         * @var Dispatcher
         */
        private $dispatcher;
        /**
         * @var LoggerEx
         */
        private $logger;
        /**
         * @var QCache
         */
        private $cache;
        /**
         * @var TmpDir
         */
        private $tmpDir;

        /**
         * Database constructor.
         *
         * @param Manager $capsule
         * @param BootLoader $bootLoader
         * @param Dispatcher $dispatcher
         * @param LoggerEx $logger
         * @param QCache $cache
         * @param TmpDir $tmpDir
         */
        public function __construct(Manager $capsule, BootLoader $bootLoader, Dispatcher $dispatcher,
                                    LoggerEx $logger, QCache $cache, TmpDir $tmpDir) {
            $this->bootLoader = $bootLoader;
            $this->cache      = $cache;
            $this->capsule    = $capsule;
            $this->dispatcher = $dispatcher;
            $this->logger     = $logger;
            $this->tmpDir     = $tmpDir;

            if ($this->connect()) {
                if (!$this->hasRdsAccess()) {
                    $this->startQueryLog();
                }
            }
        }

        /**
         * Start up eloquent
         *
         * @param array|null $credentials
         *
         * @return \Illuminate\Database\Connection|null
         */
        public function connect(array $credentials = null) {
            if ($credentials = $credentials ?? $this->getDsn()) {
                $this->capsule->getDatabaseManager()->purge($this->capsule->getDatabaseManager()->getDefaultConnection());
                $this->capsule->addConnection(array_merge(['driver' => 'mysql', 'host' => 'localhost', 'charset' => 'utf8', 'collation' => 'utf8_unicode_ci', 'prefix' => ''], $credentials));
                $this->capsule->setEventDispatcher($this->dispatcher);
                $this->capsule->setAsGlobal();
                $this->capsule->bootEloquent();
                $this->connection = $this->capsule->getConnection();
                $this->connection->enableQueryLog();

                return $this->connection;
            }

            return null;
        }

        /**
         * Return the DSN
         *
         * Priority
         * 1. CUSTOM_DSN environment variable
         * 2. RDS environment variables
         * 3. DSN string stored in /app/config/db-config
         *
         * @return array
         */
        public function getDsn(): array {
            if (!empty(getenv('PHP_CUSTOM_DSN'))) {
                $dsn = getenv('PHP_CUSTOM_DSN');
            } elseif ($this->hasRdsAccess()) {
                $dsn = sprintf("mysql://%s:%s@%s/%s", getenv('RDS_USERNAME'), getenv('RDS_PASSWORD'), getenv('RDS_HOSTNAME'), getenv('RDS_DB_NAME'));
            } elseif ($fn = realpath($this->bootLoader->getBaseDir() . '/app/Config/db-config')) {
                $dsn = trim(file_get_contents($fn));
            }

            return !empty($dsn) ? $this->parseDsn($dsn) : [];
        }

        /**
         * @return \Illuminate\Database\Connection
         */
        public function getConnection() {
            return $this->connection;
        }

        /**
         * @param string $table
         *
         * @return array
         */
        public function getColumns($table) {
            return $this->cache->get("columns-$table", function () use ($table) {
                return $this->capsule->schema()->getColumnListing($table);
            });
        }

        public function isConnected() {
            return (!empty($this->connection) && ($this->connection->getPdo() instanceof \PDO));
        }

        /**
         * Parses a database DSN string into DSN object
         *
         * @param string $dsn
         *
         * @return array
         * @throws DatabaseError
         */
        private function parseDsn(string $dsn): array {
            if ($parse = parse_url($dsn)) {
                return [
                    'username' => $parse['user'],
                    'password' => $parse['pass'],
                    'host' => $parse['host'],
                    'port' => $parse['port'] ?? 3306,
                    'database' => ltrim($parse['path'], '/') . ((!empty($_COOKIE['USE_TEST_DSN']) && ($_COOKIE['USE_TEST_DSN'] === getenv('USE_TEST_DSN') ?: 'sanchit123')) ? '_test' : '')
                ];
            }

            throw new DatabaseError("Cannot parse dsn: $dsn");
        }

        /**
         * Return the PDO object
         *
         * @return \PDO
         */
        public function getPdo() {
            return $this->getConnection()->getPdo();
        }

        public function hasRdsAccess() {
            return (!empty(getenv('RDS_DB_NAME')) && !empty(getenv('RDS_HOSTNAME')) && !empty(getenv('RDS_PASSWORD')) && !empty(getenv('RDS_USERNAME')));
        }

        protected function startQueryLog() {
            $this->logFile = $this->tmpDir->getTempDir('logs') . '/query.log';
            @unlink($this->logFile);

            $this->connection->enableQueryLog();
            $this->logger->pushHandler(new StreamHandler($this->logFile, LoggerEx::INFO));

            $this->dispatcher->listen(QueryExecuted::class, function (QueryExecuted $query) {
                $sql = preg_replace_callback('/\?/', function () use ($query, &$index) { return sprintf("'%s'", $query->bindings[$index++ ?? 0]); }, $query->sql);
                $this->logger->info($sql);
            });
        }
    }
}