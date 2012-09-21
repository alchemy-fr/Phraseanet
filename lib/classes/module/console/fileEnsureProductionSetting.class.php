<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Service\Builder;

/**
 * @todo write tests
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_console_fileEnsureProductionSetting extends Command
{
    const ALERT = 1;
    const ERROR = 0;

    /**
     *
     * @var \Alchemy\Phrasea\Core\Configuration
     */
    protected $configuration;
    protected $testSuite = array(
        'checkPhraseanetScope'
        , 'checkDatabaseScope'
        , 'checkTeamplateEngineService'
        , 'checkOrmService'
        , 'checkCacheService'
        , 'checkOpcodeCacheService'
        , 'checkBorderService'
    );
    protected $errors = 0;
    protected $alerts = 0;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Ensure production settings');

        $this->addArgument('conf', InputArgument::OPTIONAL, 'The file to check', null);
        $this->addOption('strict', 's', InputOption::VALUE_NONE, 'Wheter to fail on alerts or not');

        return $this;
    }

    public function requireSetup()
    {
        return true;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->configuration = $this->container['phraseanet.configuration'];

        $this->checkParse($output);
        $output->writeln(sprintf("Will Ensure Production Settings on <info>%s</info>", $this->configuration->getEnvironnement()));

        $this->runTests($output);

        $retval = $this->errors;
        if ($input->getOption('strict')) {
            $retval += $this->alerts;
        }

        if ($retval > 0) {
            $output->writeln("\n<error>Some errors found in your conf</error>");
        } else {
            $output->writeln("\n<info>Your production settings are setted correctly ! Enjoy</info>");
        }
        $output->writeln('End');

        return $retval;
    }

    private function runTests(OutputInterface $output)
    {
        foreach ($this->testSuite as $test) {
            $display = "";
            switch ($test) {
                case 'checkPhraseanetScope' :
                    $display = "Phraseanet Configuration";
                    break;
                case 'checkDatabaseScope' :
                    $display = "Database";
                    break;
                case 'checkTeamplateEngineService' :
                    $display = "Template Engine";
                    break;
                case 'checkOrmService' :
                    $display = "ORM";
                    break;
                case 'checkCacheService' :
                    $display = "Cache";
                    break;
                case 'checkOpcodeCacheService' :
                    $display = "Opcode";
                    break;
                case 'checkBorderService' :
                    $display = "Border";
                    break;
                default:
                    throw new \Exception('Unknown test');
                    break;
            }

            $output->writeln(sprintf("\n||| %s", mb_strtoupper($display)));

            call_user_func(array($this, $test), $output);
        }
    }

    private function checkParse(OutputInterface $output)
    {

        if (!$this->configuration->getConfigurations()) {
            throw new \Exception("Unable to load configurations\n");
        }
        if (!$this->configuration->getConnexions()) {
            throw new \Exception("Unable to load connexions\n");
        }
        if (!$this->configuration->getServices()) {
            throw new \Exception("Unable to load services\n");
        }

        return;
    }

    private function checkCacheService(OutputInterface $output)
    {
        $cache = $this->configuration->getCache();

        if ($this->probeCacheService($output, $cache)) {
            if ($this->recommendedCacheService($output, $cache, true)) {
                $work_message = '<info>Works !</info>';
            } else {
                $work_message = '<comment>Cache server recommended</comment>';
                $this->alerts++;
            }
        } else {
            $work_message = '<error>Failed - could not connect !</error>';
            $this->errors++;
        }

        $verification = sprintf("\t--> Verify <info>%s</info> : %s", 'MainCache', $work_message);

        $this->printConf($output, "\t" . 'service', $cache, false, $verification);
        $this->verifyCacheOptions($output, $cache);
    }

    private function checkOpcodeCacheService(OutputInterface $output)
    {
        $cache = $this->configuration->getOpcodeCache();

        if ($this->probeCacheService($output, $cache)) {
            if ($this->recommendedCacheService($output, $cache, false)) {
                $work_message = '<info>Works !</info>';
            } else {
                $work_message = '<comment>Opcode recommended</comment>';
                $this->alerts++;
            }
        } else {
            $work_message = '<error>Failed - could not connect !</error>';
            $this->errors++;
        }

        $verification = sprintf("\t--> Verify <info>%s</info> : %s", 'OpcodeCache', $work_message);

        $this->printConf($output, "\t" . 'service', $cache, false, $verification);
        $this->verifyCacheOptions($output, $cache);
    }

    private function checkBorderService(OutputInterface $output)
    {
        $serviceName = $this->configuration->getBorder();
        $configuration = $this->configuration->getService($serviceName);

        $listChecks = false;
        try {
            $service = Builder::create($this->container, $configuration);
            $work_message = '<info>Works !</info>';
            $listChecks = true;
        } catch (\Exception $e) {
            $work_message = '<error>Failed - could not load Border Manager service !</error>';
            $this->errors++;
        }

        $output->writeln(sprintf("\t--> Verify Border Manager<info>%s</info> : %s", $serviceName, $work_message));

        if ($listChecks) {
            $borderManager = $service->getDriver();

            foreach ($service->getUnregisteredCheckers() as $check) {
                $output->writeln(sprintf("\t\t--> <comment>check %s could not be loaded for the following reason %s</comment>", $check['checker'], $check['message']));
            }
        }
    }

    private function checkPhraseanetScope(OutputInterface $output)
    {
        $required = array('servername', 'maintenance', 'debug', 'display_errors', 'database');

        $phraseanet = $this->configuration->getPhraseanet();

        foreach ($phraseanet->all() as $conf => $value) {
            switch ($conf) {
                default:
                    $this->alerts++;
                    $this->printConf($output, $conf, $value, false, '<comment>Not recognized</comment>');
                    break;
                case 'servername':
                    $url = $value;
                    $required = array_diff($required, array($conf));

                    $parseUrl = parse_url($url);

                    if (empty($url)) {
                        $message = "<error>should not be empty</error>";
                        $this->errors++;
                    } elseif ($url == 'http://sub.domain.tld/') {
                        $this->alerts++;
                        $message = "<comment>may be wrong</comment>";
                    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                        $message = "<error>not valid</error>";
                        $this->errors++;
                    } elseif ($parseUrl["scheme"] !== "https") {
                        $this->alerts++;
                        $message = "<comment>should be https</comment>";
                    } else {
                        $message = "<info>OK</info>";
                    }
                    $this->printConf($output, $conf, $value, false, $message);
                    break;
                case 'maintenance':
                case 'debug':
                case 'display_errors':
                    $required = array_diff($required, array($conf));
                    $message = '<info>OK</info>';

                    if ($value !== false) {
                        $message = '<error>Should be false</error>';
                        $this->errors++;
                    }

                    $this->printConf($output, $conf, $value, false, $message);
                    break;
                case 'database':
                    $required = array_diff($required, array($conf));
                    try {
                        $service = $this->configuration->getConnexion($value);
                        if ($this->verifyDatabaseConnexion($service)) {
                            $message = '<info>OK</info>';
                        } else {
                            $message = '<error>Connection not available</error>';
                            $this->errors++;
                        }
                    } catch (\Exception $e) {
                        $message = '<error>Unknown connection</error>';
                        $this->errors++;
                    }
                    $this->printConf($output, $conf, $value, false, $message);
                    break;
            }
        }

        if (count($required) > 0) {
            $output->writeln(sprintf('<error>Miss required keys %s</error>', implode(', ', $required)));
            $this->errors++;
        }

        return;
    }

    private function checkDatabaseScope(OutputInterface $output)
    {
        $connexionName = $this->configuration->getPhraseanet()->get('database');
        $connexion = $this->configuration->getConnexion($connexionName);

        try {
            if ($this->verifyDatabaseConnexion($connexion)) {
                $work_message = '<info>Works !</info>';
            } else {
                $work_message = '<error>Failed - could not connect !</error>';
                $this->errors++;
            }
        } catch (\Exception $e) {

            $work_message = '<error>Failed - could not connect !</error>';
            $this->errors++;
        }

        $output->writeln(sprintf("\t--> Verify connection <info>%s</info> : %s", $connexionName, $work_message));

        $required = array('driver');

        if (!$connexion->has('driver')) {
            $output->writeln("\n<error>Connection has no driver</error>");
            $this->errors++;
        } elseif ($connexion->get('driver') == 'pdo_mysql') {
            $required = array('driver', 'dbname', 'charset', 'password', 'user', 'port', 'host');
        } elseif ($connexion->get('driver') == 'pdo_sqlite') {
            $required = array('driver', 'path', 'charset');
        } else {
            $output->writeln("\n<error>Your driver is not managed</error>");
            $this->errors++;
        }

        foreach ($connexion->all() as $conf => $value) {
            switch ($conf) {
                default:
                    $this->alerts++;
                    $this->printConf($output, $conf, $value, false, '<comment>Not recognized</comment>');
                    break;
                case 'charset':
                    $required = array_diff($required, array($conf));
                    $message = '<info>OK</info>';
                    if ($value !== 'UTF8') {
                        $message = '<comment>Not recognized</comment>';
                        $this->alerts++;
                    }
                    $this->printConf($output, $conf, $value, false, $message);
                    break;
                case 'path':
                    $required = array_diff($required, array($conf));
                    $message = is_writable(dirname($value)) ? '<info>OK</info>' : '<error>Not writeable</error>';
                    $this->printConf($output, $conf, $value, false, $message);
                    break;
                case 'dbname':
                case 'user':
                case 'host':
                    $required = array_diff($required, array($conf));
                    $message = '<info>OK</info>';

                    if (!is_scalar($value)) {
                        $message = '<error>Should be scalar</error>';
                        $this->errors++;
                    }

                    $this->printConf($output, $conf, $value, false, $message);
                    break;
                case 'port':
                    $required = array_diff($required, array($conf));
                    $message = '<info>OK</info>';

                    if (!ctype_digit($value)) {
                        $message = '<error>Should be ctype_digit</error>';
                        $this->errors++;
                    }

                    $this->printConf($output, $conf, $value, false, $message);
                    break;
                case 'password':
                    $required = array_diff($required, array($conf));
                    $message = '<info>OK</info>';

                    if (!is_scalar($value)) {
                        $message = '<error>Should be scalar</error>';
                        $this->errors++;
                    }

                    $value = '***********';
                    $this->printConf($output, $conf, $value, false, $message);
                    break;
                case 'driver':
                    $required = array_diff($required, array($conf));
                    $message = '<info>OK</info>';

                    if ($value !== 'pdo_mysql') {
                        $message = '<error>MySQL recommended</error>';
                        $this->errors++;
                    }
                    $this->printConf($output, $conf, $value, false, $message);
                    break;
            }
        }

        if (count($required) > 0) {
            $output->writeln(sprintf('<error>Miss required keys %s</error>', implode(', ', $required)));
            $this->errors++;
        }

        return;
    }

    protected function verifyDatabaseConnexion(\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag $connexion)
    {
        try {
            $config = new \Doctrine\DBAL\Configuration();
            $conn = \Doctrine\DBAL\DriverManager::getConnection($connexion->all(), $config);

            return true;
        } catch (\Exception $e) {

        }

        return false;
    }

    private function checkTeamplateEngineService(OutputInterface $output)
    {
        $templateEngineName = $this->configuration->getTemplating();
        $configuration = $this->configuration->getService($templateEngineName);

        try {
            Builder::create($this->container, $configuration);
            $work_message = '<info>Works !</info>';
        } catch (\Exception $e) {
            $work_message = '<error>Failed - could not load template engine !</error>';
            $this->errors++;
        }

        $output->writeln(sprintf("\t--> Verify Template engine <info>%s</info> : %s", $templateEngineName, $work_message));

        if (!$configuration->has('type')) {
            $output->writeln("\n<error>Configuration has no type</error>");
            $this->errors++;
        } elseif ($configuration->get('type') == 'TemplateEngine\\Twig') {
            $required = array('debug', 'charset', 'strict_variables', 'autoescape', 'optimizer');
        } else {
            $output->writeln("\n<error>Your type is not managed</error>");
            $this->errors++;
        }

        foreach ($configuration->all() as $conf => $value) {
            switch ($conf) {
                case 'type':
                    $message = '<info>OK</info>';

                    if ($value !== 'TemplateEngine\\Twig') {
                        $message = '<error>Not recognized</error>';
                        $this->alerts++;
                    }

                    $this->printConf($output, $conf, $value, false, $message);
                    break;
                case 'options':
                    $message = '<info>OK</info>';

                    if (!is_array($value)) {
                        $message = '<error>Should be array</error>';
                        $this->errors++;
                    }

                    $this->printConf($output, $conf, 'array()', false, $message);
                    break;
                default:
                    $this->alerts++;
                    $this->printConf($output, $conf, 'unknown', false, '<comment>Not recognized</comment>');
                    break;
            }
        }

        foreach ($configuration->get('options') as $conf => $value) {
            switch ($conf) {
                case 'debug';
                case 'strict_variables';
                    $required = array_diff($required, array($conf));
                    $message = '<info>OK</info>';

                    if ($value !== false) {
                        $message = '<error>Should be false</error>';
                        $this->errors++;
                    }

                    $this->printConf($output, "\t" . $conf, $value, false, $message);
                    break;
                case 'autoescape';
                case 'optimizer';
                    $required = array_diff($required, array($conf));
                    $message = '<info>OK</info>';

                    if ($value !== true) {
                        $message = '<error>Should be true</error>';
                        $this->errors++;
                    }

                    $this->printConf($output, "\t" . $conf, $value, false, $message);
                    break;
                case 'charset';
                    $required = array_diff($required, array($conf));
                    $message = '<info>OK</info>';

                    if ($value !== 'utf-8') {
                        $message = '<comment>Not recognized</comment>';
                        $this->alerts++;
                    }

                    $this->printConf($output, "\t" . $conf, $value, false, $message);
                    break;
                default:
                    $this->alerts++;
                    $this->printConf($output, "\t" . $conf, $value, false, '<comment>Not recognized</comment>');
                    break;
            }
        }

        if (count($required) > 0) {
            $output->writeln(sprintf('<error>Miss required keys %s</error>', implode(', ', $required)));
            $this->errors++;
        }

        return;
    }

    private function checkOrmService(OutputInterface $output)
    {
        $ormName = $this->configuration->getOrm();
        $configuration = $this->configuration->getService($ormName);

        try {
            $service = Builder::create($this->container, $configuration);
            $work_message = '<info>Works !</info>';
        } catch (\Exception $e) {
            $work_message = '<error>Failed - could not connect !</error>';
            $this->errors++;
        }

        $output->writeln(sprintf("\t--> Verify ORM engine <info>%s</info> : %s", $ormName, $work_message));

        if (!$configuration->has('type')) {
            $output->writeln("\n<error>Configuration has no type</error>");
            $this->errors++;
        } elseif ($configuration->get('type') == 'Orm\\Doctrine') {
            $required = array('debug', 'dbal', 'cache');
        } else {
            $output->writeln("\n<error>Your type is not managed</error>");
            $this->errors++;
        }

        foreach ($configuration->all() as $conf => $value) {
            switch ($conf) {
                case 'type':
                    $message = $value == 'Orm\\Doctrine' ? '<info>OK</info>' : '<error>Not recognized</error>';
                    $this->printConf($output, $conf, $value, false, $message);
                    break;
                case 'options':
                    $message = '<info>OK</info>';

                    if (!is_array($value)) {
                        $message = '<error>Should be array</error>';
                        $this->errors++;
                    }

                    $this->printConf($output, $conf, 'array()', false, $message);
                    break;
                default:
                    $this->alerts++;
                    $this->printConf($output, $conf, 'unknown', false, '<comment>Not recognized</comment>');
                    break;
            }
        }

        foreach ($configuration->get('options') as $conf => $value) {
            switch ($conf) {
                case 'log':
                    $message = '<info>OK</info>';

                    if ($value !== false) {
                        $message = '<error>Should be deactivated</error>';
                        $this->errors++;
                    }

                    $this->printConf($output, $conf, $value, false, $message);
                    break;
                case 'cache':
                    $required = array_diff($required, array($conf));
                    $message = '<info>OK</info>';

                    if (!is_array($value)) {
                        $message = '<error>Should be Array</error>';
                        $this->errors++;
                    }

                    $this->printConf($output, $conf, 'array()', false, $message);

                    $required_caches = array('query', 'result', 'metadata');
                    foreach ($value as $name => $cache_type) {
                        $required_caches = array_diff($required_caches, array($name));

                        foreach ($cache_type as $key_cache => $value_cache) {
                            switch ($key_cache) {
                                case 'service':
                                    if ($this->probeCacheService($output, $value_cache)) {
                                        $server = $name === 'result';
                                        if ($this->recommendedCacheService($output, $value_cache, $server)) {
                                            $work_message = '<info>Works !</info>';
                                        } else {
                                            $this->alerts++;
                                            if ($server) {
                                                $work_message = '<comment>Cache server recommended</comment>';
                                            } else {
                                                $work_message = '<comment>Opcode cache recommended</comment>';
                                            }
                                        }
                                    } else {
                                        $work_message = '<error>Failed - could not connect !</error>';
                                        $this->errors++;
                                    }

                                    $verification = sprintf("\t--> Verify <info>%s</info> : %s", $name, $work_message);

                                    $this->printConf($output, "\t" . $key_cache, $value_cache, false, $verification);
                                    $this->verifyCacheOptions($output, $value_cache);
                                    break;
                                default:
                                    $this->alerts++;
                                    $this->printConf($output, "\t" . $key_cache, $value_cache, false, '<comment>Not recognized</comment>');
                                    break;
                            }
                            if (!isset($cache_type['service'])) {
                                $output->writeln('<error>Miss service for %s</error>', $cache_type);
                                $this->errors++;
                            }
                        }
                    }

                    if (count($required_caches) > 0) {
                        $output->writeln(sprintf('<error>Miss required caches %s</error>', implode(', ', $required_caches)));
                        $this->errors++;
                    }
                    break;
                case 'debug':
                    $required = array_diff($required, array($conf));
                    $message = '<info>OK</info>';

                    if ($value !== false) {
                        $message = '<error>Should be false</error>';
                        $this->errors++;
                    }

                    $this->printConf($output, $conf, $value, false, $message);
                    break;
                case 'dbal':
                    $required = array_diff($required, array($conf));
                    try {
                        $connexion = $this->configuration->getConnexion($value);
                        $this->verifyDatabaseConnexion($connexion);
                        $message = '<info>OK</info>';
                    } catch (\Exception $e) {
                        $message = '<error>Failed</error>';
                        $this->errors++;
                    }
                    $this->printConf($output, $conf, $value, false, $message);
                    break;
                default:
                    $this->alerts++;
                    $this->printConf($output, $conf, $value, false, '<comment>Not recognized</comment>');
                    break;
            }
        }

        if (count($required) > 0) {
            $output->writeln(sprintf('<error>Miss required keys %s</error>', implode(', ', $required)));
            $this->errors++;
        }

        return;
    }

    protected function verifyCacheOptions(OutputInterface $output, $ServiceName)
    {
        try {
            $conf = $this->configuration->getService($ServiceName);

            $Service = Builder::create($this->container, $conf);
        } catch (\Exception $e) {
            return false;
        }

        $required_options = array();

        switch ($Service->getType()) {
            default:
                break;
            case 'memcache':
            case 'memcached':
            case 'redis':
                $required_options = array('host', 'port');
                break;
        }

        if ($required_options) {
            foreach ($conf->get('options') as $conf => $value) {
                switch ($conf) {
                    case 'host';
                        $required_options = array_diff($required_options, array($conf));
                        $message = '<info>OK</info>';

                        if (!is_scalar($value)) {
                            $message = '<error>Should be scalar</error>';
                            $this->errors++;
                        }

                        $this->printConf($output, "\t\t" . $conf, $value, false, $message);
                        break;
                    case 'port';
                        $required_options = array_diff($required_options, array($conf));
                        $message = '<info>OK</info>';

                        if (!ctype_digit($value)) {
                            $message = '<comment>Not recognized</comment>';
                            $this->alerts++;
                        }

                        $this->printConf($output, "\t\t" . $conf, $value, false, $message);
                        break;
                    default:
                        $this->alerts++;
                        $this->printConf($output, "\t\t" . $conf, $value, false, '<comment>Not recognized</comment>');
                        break;
                }
            }
        }

        if (count($required_options) > 0) {
            $output->writeln(sprintf('<error>Miss required keys %s</error>', implode(', ', $required_options)));
            $this->errors++;
        }
    }

    protected function probeCacheService(OutputInterface $output, $ServiceName)
    {
        try {
            $originalConfiguration = $this->configuration->getService($ServiceName);

            $Service = Builder::create($this->container, $originalConfiguration);
        } catch (\Exception $e) {
            return false;
        }

        try {
            $driver = $Service->getDriver();
        } catch (\Exception $e) {
            return false;
        }

        if ($driver->isServer()) {
            switch ($Service->getType()) {
                default:
                    return false;
                    break;
                case 'memcache':
                    if (!@memcache_connect($Service->getHost(), $Service->getPort())) {
                        return false;
                    }
                    break;
                case 'memcached':
                    $ret = false;
                    try {
                        $memcached = new \Memcached();
                        $memcached->addServer($Service->getHost(), $Service->getPort());
                        $stats = $memcached->getStats();

                        if (!isset($stats[$key]) || !$stats[$key]) {
                            throw new \Exception('Unable to connect to memcached server');
                        }

                        $ret = true;
                    } catch (\Exception $e) {

                    }

                    unset($memcached);

                    return $ret;
                    break;
                case 'redis':
                    $ret = false;
                    try {
                        $redis = new \Redis();
                        if (@$redis->connect($Service->getHost(), $Service->getPort())) {
                            $ret = true;
                        }
                    } catch (\Exception $e) {

                    }
                    unset($redis);

                    return $ret;
                    break;
            }
        }

        return true;
    }

    protected function recommendedCacheService(OutputInterface $output, $ServiceName, $server)
    {
        try {
            $originalConfiguration = $this->configuration->getService($ServiceName);

            $Service = Builder::create($this->container, $originalConfiguration);
        } catch (\Exception $e) {
            return false;
        }

        if ($Service->getType() === 'array') {
            return false;
        }

        return $server === $Service->getDriver()->isServer();
    }

    private function printConf($output, $scope, $value, $scopage = false, $message = '')
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                if ($scopage)
                    $key = $scope . ":" . $key;
                $this->printConf($output, $key, $val, $scopage, '');
            }
        } elseif (is_bool($value)) {
            if ($value === false) {
                $value = 'false';
            } elseif ($value === true) {
                $value = 'true';
            }
            $output->writeln(sprintf("\t%s: %s %s", $scope, $value, $message));
        } elseif (!empty($value)) {
            $output->writeln(sprintf("\t%s: %s %s", $scope, $value, $message));
        }
    }
}
