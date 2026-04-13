<?php
/**
 * Created by IntelliJ IDEA.
 * User: Nikolay Chervyakov 
 * Date: 08.09.2014
 * Time: 12:46
 */


namespace App\Installation\Step;


use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConfirmationStep extends AbstractStep
{
    protected $template = 'installation/confirmation';

    /**
     * @var array|AbstractStep[]
     */
    protected $previousSteps = [];

    /**
     * @var string
     */
    protected $configDir;

    /**
     * @var string
     */
    protected $bakDir;

    /**
     * @var bool
     */
    protected $canWrite = true;

    /**
     * @var string Version of config
     */
    protected $configVersion = '';

    /**
     * @var array
     */
    protected $configsToAdd = [];

    public function init()
    {
        $this->configVersion = time();
    }

    protected function persistFields()
    {
        return ['configVersion', 'configsToAdd', 'canWrite', 'configDir', 'bakDir'];
    }

    /**
     * @inheritdoc
     */
    protected function processRequest(array $data = [])
    {
        $this->collectPreviousSteps();
        $this->configDir = base_path('assets/config');
        $this->bakDir = $this->configDir . '/bak/'.date('Y_m_d_H_i_s');

        try {
            $this->updateConfigs();

            // Ask user to manually needed configs.
            if (!$this->canWrite && count($this->configsToAdd)) {
                return false;
            }

            $this->installDB();

        } catch (\Exception $e) {
            $this->errors[] = 'Error ' . $e->getCode() . ': ' . $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function getViewData()
    {
        $result = [];

        $prev = $this;
        while ($prev = $prev->getPrevStep()) {
            if ($prev->getName() == 'db_settings') {
                $result['database'] = $prev->getViewData();
            }
            if ($prev->getName() == 'email_settings') {
                $result['email'] = $prev->getViewData();
            }
        }

        $result['configsToAdd'] = $this->configsToAdd;

        return $result;
    }

    /**
     * Cleans DB and installs all schema and data from scratch.
     */
    protected function installDB()
    {
        $conn = DB::connection()->getPdo();
        $conn->setAttribute(\PDO::ATTR_TIMEOUT, 300);

        DB::statement("SET foreign_key_checks = 0;");

        // Remove Foreign Keys
        $sql = "SELECT tc.TABLE_NAME as `table`, tc.CONSTRAINT_NAME as `fk` "
            . "FROM information_schema.TABLE_CONSTRAINTS tc "
            . "WHERE tc.CONSTRAINT_SCHEMA=(SELECT DATABASE()) AND tc.CONSTRAINT_TYPE='FOREIGN KEY'";

        $foreignKeys = DB::select($sql);
        foreach ($foreignKeys as $fk) {
            $conn->exec("ALTER TABLE `{$fk->table}` DROP FOREIGN KEY `{$fk->fk}`;");
        }

        // Remove tables
        $tables = DB::select("SELECT GROUP_CONCAT(table_name) as tbl FROM information_schema.tables WHERE table_schema = (SELECT DATABASE())");
        $tblList = $tables[0]->tbl ?? '';
        if ($tblList) {
            $conn->exec("DROP TABLE IF EXISTS " . $tblList);
        }

        // Install schema
        $dbScript = base_path("database/db.sql");
        $conn->exec(file_get_contents($dbScript));

        // Install migrations
        foreach (scandir(base_path("database/migrations")) as $file) {
            $file = base_path("database/migrations/") . $file;
            if (is_file($file)) {
                $sqlContent = file_get_contents($file);
                if (strpos($sqlContent, '# IGNORE') !== 0) {
                    $conn->exec($sqlContent);
                }
            }
        }

        // Install demo data
        $demoScript = base_path("database/demo_database.sql");
        $conn->exec(file_get_contents($demoScript));

        // Post-install scripts
        $dirIterator = new \DirectoryIterator(base_path("database/post_migration"));
        foreach ($dirIterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->isReadable()) {
                $ext = strtolower($fileInfo->getExtension());
                $filePath = $fileInfo->getRealPath();

                if ($ext == 'sql') {
                    $sqlContent = file_get_contents($filePath);
                    if (strpos($sqlContent, '# IGNORE') !== 0) {
                        $conn->exec($sqlContent);
                    }
                } else if ($ext == 'php') {
                    $runner = function () use ($filePath) {
                        include $filePath;
                    };
                    $runner();
                }
            }
        }
        DB::statement("SET foreign_key_checks = 1;");

        $adminCredentials = $this->previousSteps['admin_credentials']->getViewData();
        User::changeUserPassword('admin', $adminCredentials['password']);
    }

    /**
     * Writes new config to files.
     * @throws \Exception
     */
    private function updateConfigs()
    {
        $configDir = $this->configDir;
        $fileInfo = new \SplFileInfo($configDir);

        if (!$fileInfo->isDir()) {
            if (!mkdir($configDir, 0777, true)) {
                throw new \Exception("Please create config directory [$configDir] and give PHP full access to it.");
            }
        }

        $configDir = realpath($configDir);
        if (!$fileInfo->isWritable()) {
            $this->canWrite = false;
        }

        if (isset($this->previousSteps['admin_credentials'])) {
            $adminCredSettings = $this->previousSteps['admin_credentials']->getViewData();
            $paramsConfig = config('parameters') ?: [];
            $paramsConfig['installer_password'] = $adminCredSettings['password'];
            $this->writeConfigFile($this->configDir . "/parameters.php", $paramsConfig);
        } else {
            $paramsConfig = config('parameters') ?: [];
            $this->writeConfigFile($this->configDir . "/parameters.php", $paramsConfig);
        }

        $restConfig = config('rest') ?: [];
        $this->writeConfigFile($this->configDir . "/rest.php", $restConfig);

        // Update DB settings
        $dbSettings = $this->previousSteps['db_settings']->getViewData();
        $dbConfig = [
            'default' => [
                'driver' => 'mysql',
                'host'   => $dbSettings['host'],
                'port'   => $dbSettings['port'],
                'db'     => $dbSettings['db'],
                'user'   => $dbSettings['user'],
                'password' => $dbSettings['password'],
            ]
        ];
        $this->writeConfigFile($configDir.'/db.php', $dbConfig);

        // Update email settings
        $emailSettings = $this->previousSteps['email_settings']->getViewData();
        $emailConfig = config('email') ?: [];
        $emailConfig['default']['hostname']         = $emailSettings['hostname'];
        $emailConfig['default']['port']             = $emailSettings['port'];
        $emailConfig['default']['username']         = $emailSettings['username'];
        $emailConfig['default']['password']         = $emailSettings['password'];
        $emailConfig['default']['encryption']       = $emailSettings['encryption'];
        $emailConfig['default']['type']             = $emailSettings['type'];
        $emailConfig['default']['sendmail_command'] = $emailSettings['sendmail_command'];
        $emailConfig['default']['mail_parameters']  = $emailSettings['mail_parameters'];
        $emailConfig['default']['timeout']          = $emailSettings['timeout'];
        $this->writeConfigFile($configDir.'/email.php', $emailConfig);

        // Vuln configs
        $vulnSampleDir = $this->configDir . '/vuln.sample';
        $vulnTargetDir = $this->configDir . '/vuln';

        foreach (new \DirectoryIterator($vulnSampleDir) as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->getExtension() != 'php') {
                continue;
            }

            $configName = $fileInfo->getBasename();
            $targetFileName = $vulnTargetDir . '/' . $configName;
            if (file_exists($targetFileName) && is_file($targetFileName)) {
                continue;
            }

            try {
                copy($fileInfo->getPathname(), $targetFileName);

            } catch (\Exception $e) {
                $this->configsToAdd[$targetFileName] = file_get_contents($fileInfo->getPathname());
            }
        }
    }

    /**
     * Flattens chain of steps for simple using.
     */
    protected function collectPreviousSteps() {
        $this->previousSteps = [];
        $prev = $this;
        while ($prev = $prev->getPrevStep()) {
            $this->previousSteps[$prev->getName()] = $prev;
        }
    }

    /**
     * @param $fileName
     * @param $config
     */
    protected function writeConfigFile($fileName, $config)
    {
        if (!$this->canWrite) {
            $currentConfig = @include($fileName);
            if (!file_exists($fileName) || $config != $currentConfig) {
                $this->configsToAdd[$fileName] = $this->serializeConfig($config);
            }
            return;
        }
        if (file_exists($fileName) && is_file($fileName) && $this->checkBakDir()) {
            $bakDir = $this->bakDir;
            if (!file_exists($bakDir)) {
                mkdir($bakDir);
            }

            if (file_exists($bakDir) && is_writable($bakDir)) {
                copy($fileName, $bakDir.'/'.basename($fileName));
            }
        }
        file_put_contents($fileName, $this->serializeConfig($config));
    }

    /**
     * Serializes config array as PHP code
     * @param $config
     * @return string
     */
    protected function serializeConfig($config) {
        return "<?php\nreturn ".var_export($config, true).";\n";
    }

    /**
     * Just writes overridden config.
     * @param $groupName
     */
    public function createOverriddenConfig($groupName)
    {
        $configData = config($groupName) ?: [];
        $this->writeConfigFile($this->configDir . "/{$groupName}.php", $configData);
    }

    /**
     * Ensures that BAK directory for old configs exists, or creates it.
     * @return bool
     */
    public function checkBakDir()
    {
        if (!$this->canWrite) {
            return false;
        }
        $bakDir = $this->configDir.'/bak';
        if (file_exists($bakDir) && is_dir($bakDir)) {
            return true;
        } else {
            if (is_writable($this->configDir)) {
                mkdir($bakDir);
                return is_writable($bakDir);
            }
        }
        return false;
    }
}