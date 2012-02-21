<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Command\Command;
use Alchemy\Phrasea\Core;
use Symfony\Component\Yaml;

/**
 * @todo write tests
 *
 * @package
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
  protected $env;
  protected $testSuite = array(
    'checkPhraseanetScope'
    , 'checkDatabaseScope'
    , 'checkTeamplateEngineService'
    , 'checkOrmService'
  );
  protected $connexionOk = false;

  public function __construct($name = null)
  {
    parent::__construct($name);

    $this->setDescription('Ensure production settings');

    return $this;
  }

  public function execute(InputInterface $input, OutputInterface $output)
  {

    $this->initTests($output);

    $this->prepareTests($output);

    $this->runTests($output);

    return 0;
  }

  private function initTests(OutputInterface $output)
  {
    $spec    = new Core\Configuration\Application();
    $parser  = new Core\Configuration\Parser\Yaml();
    $handler = new Core\Configuration\Handler($spec, $parser);

    $this->configuration = new Core\Configuration($handler);

    if (!$this->configuration->isInstalled())
    {
      $output->writeln(sprintf("\nPhraseanet is not installed\n"));

      return 1;
    }
  }

  private function prepareTests(OutputInterface $output)
  {
    try
    {
      $this->checkParse($output);
      $this->checkGetSelectedEnvironement($output);
      $this->checkGetSelectedEnvironementFromFile($output);
    }
    catch (\Exception $e)
    {
      $previous = $e->getPrevious();

      $output->writeln(sprintf(
          "<error>%s FATAL error : %s</error>"
          , $e->getMessage()
          , $previous instanceof \Exception ?
            $previous->getMessage() : 'Unknown.'
        )
      );
      $output->writeln(sprintf("\nCheck test suite can not continue please correct FATAL error and relaunch.\n"));

      return 1;
    }
  }

  private function runTests(OutputInterface $output)
  {
    $nbErrors = 0;

    foreach ($this->testSuite as $test)
    {
      $display = "";
      switch ($test)
      {
        case 'checkPhraseanetScope' :
          $display = "Phraseanet Scope Configuration";
          break;
        case 'checkDatabaseScope' :
          $display = "Database configuration & connexion";
          break;
        case 'checkTeamplateEngineService' :
          $display = "Template Engine Service";
          break;
        case 'checkOrmService' :
          $display = "ORM Service";
          break;
      }

      $output->writeln(sprintf("=== CHECKING %s ===", $display));
      $output->writeln("");

      try
      {
        call_user_func(array($this, $test), $output);
      }
      catch (\Exception $e)
      {
        $nbErrors++;
        $previous = $e->getPrevious();

        $output->writeln(sprintf(
            "<error>%s FAILED : %s</error>"
            , $e->getMessage()
            , $previous instanceof \Exception ?
              $previous->getMessage() : 'Unknow'
          )
        );
        $output->writeln("");
      }
    }
    if (!$nbErrors)
    {
      $output->writeln("<info>Your production settings are setted correctly ! Enjoy</info>");
    }
    else
    {
      $output->writeln("<error>Please correct errors and relaunch</error>");
    }
    $output->writeln("");
    return (int) ($nbErrors > 0);
  }

  private function checkParse(OutputInterface $output)
  {
    $parser        = $this
      ->configuration
      ->getConfigurationHandler()
      ->getParser();
    $fileConfig    = $this
      ->configuration
      ->getConfigurationHandler()
      ->getSpecification()
      ->getConfigurationFile();
    $fileService   = $this
      ->configuration
      ->getConfigurationHandler()
      ->getSpecification()
      ->getServiceFile();
    $fileConnexion = $this
      ->configuration
      ->getConfigurationHandler()
      ->getSpecification()
      ->getConnexionFile();

    try
    {
      $parser->parse($fileConfig);
      $parser->parse($fileService);
      $parser->parse($fileConnexion);
    }
    catch (\Exception $e)
    {
      $message = str_replace("\\", "", $e->getMessage());
      $e       = new \Exception($message);
      throw new \Exception(sprintf("CHECK parsing file\n"), null, $e);
    }

    return;
  }

  private function checkGetSelectedEnvironement(OutputInterface $output)
  {
    try
    {
      $this->configuration->getConfiguration();
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf("CHECK get selected environment\n"), null, $e);
    }

    return;
  }

  private function checkGetSelectedEnvironementFromFile(OutputInterface $output)
  {
    $spec    = new Core\Configuration\Application();
    $parser  = new Core\Configuration\Parser\Yaml();
    $handler = new Core\Configuration\Handler($spec, $parser);

    $configuration = new Core\Configuration($handler);

    try
    {
      $configuration->getConfiguration();
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf("CHECK get selected environment from file\n"), null, $e);
    }


    $output->writeln("=== ENVIRONMENT ===");
    $output->writeln("");
    $output->writeln(sprintf("Current is '%s'", $configuration->getEnvironnement()));
    $output->writeln("");
    return;
  }

  private function checkPhraseanetScope(OutputInterface $output)
  {
    try
    {
      $phraseanet = $this->configuration->getPhraseanet();

      $this->printConf($output, 'phraseanet', $phraseanet->all());

      $url = $phraseanet->get("servername");

      if (empty($url))
      {
        throw new \Exception("phraseanet:servername connot be empty");
      }

      if (!filter_var($url, FILTER_VALIDATE_URL))
      {
        throw new \Exception(sprintf("%s url is not valid", $url));
      }

      $parseUrl = parse_url($url);

      if ($parseUrl["scheme"] !== "https")
      {
        $output->writeln(sprintf("<comment> /!\ %s url scheme should be https</comment>", $url));
      }

      if ($phraseanet->get("debug") !== false)
      {
        throw new \Exception("phraseanet:debug must be initialized to false");
      }

      if ($phraseanet->get("display_errors") !== false)
      {
        throw new \Exception("phraseanet:debug must be initialized to false");
      }

      if ($phraseanet->get("maintenance") === true)
      {
        throw new \Exception("phraseanet:warning maintenance is set to false");
      }
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf("Check Phraseanet Scope\n"), null, $e);
    }
    $output->writeln("");
    $output->writeln("<info>Phraseanet scope is correctly setted</info>");
    $output->writeln("");
    return;
  }

  private function checkDatabaseScope(OutputInterface $output)
  {
    try
    {
      $connexionName = $this->configuration->getPhraseanet()->get('database');
      $connexion     = $this->configuration->getConnexion($connexionName);

      $output->writeln(sprintf("Current connexion is '%s'", $connexionName));
      $output->writeln("");
      foreach ($connexion->all() as $key => $element)
      {
        $output->writeln(sprintf("%s: %s", $key, $element));
      }

      if ($connexion->get("driver") === "pdo_sqlite")
      {
        throw new \Exception("A sqlite database is not recommanded for production environment");
      }

      try
      {
        $config = new \Doctrine\DBAL\Configuration();
        $conn   = \Doctrine\DBAL\DriverManager::getConnection(
            $connexion->all()
            , $config
        );
        unset($conn);
        $this->connexionOk = true;
      }
      catch (\Exception $e)
      {
        throw new \Exception(sprintf(
            "Unable to connect to database declared in connexion '%s' for the following reason %s"
            , $connexionName
            , $e->getMessage()
          )
        );
      }

      $output->writeln("");
      $output->writeln(sprintf("<info>'%s' successfully connect to database</info>", $connexionName));
      $output->writeln("");
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf("CHECK Database Scope\n"), null, $e);
    }

    return;
  }

  private function checkTeamplateEngineService(OutputInterface $output)
  {
    try
    {
      $templateEngineName = $this->configuration->getTemplating();
      $output->writeln(sprintf("Current template engine service is '%s' ", $templateEngineName));
      $output->writeln("");
      try
      {
        $configuration = $this->configuration->getService($templateEngineName);
        $this->printConf($output, $templateEngineName, $configuration->all());
      }
      catch (\Exception $e)
      {
        $message = sprintf(
          "%s called from %s in %s:template_engine scope"
          , $e->getMessage()
          , $this->configuration->getFile()->getFilename()
          , "PROD"
          , $templateEngineName
        );
        $e       = new \Exception($message);
        throw $e;
      }

      $service = Core\Service\Builder::create(
          \bootstrap::getCore()
          , $templateEngineName
          , $configuration
      );

      if ($service->getType() === 'twig')
      {
        $twig = $service->getService();

        if ($twig->isDebug())
        {
          throw new \Exception(sprintf("%s service should not be in debug mode", $service->getName()));
        }

        if ($twig->isStrictVariables())
        {
          throw new \Exception(sprintf("%s service should not be set in strict variables mode", $service->getName()));
        }
      }
    }
    catch (\Exception $e)
    {
      if ($e instanceof \Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException)
      {
        if ($e->getKey() === 'template_engine')
        {
          $e = new \Exception(sprintf(
                "Missing parameter %s "
                , $e->getKey()
              )
          );
        }
        else
        {
          $e = new \Exception(sprintf(
                "Missing parameter %s for %s service"
                , $e->getKey()
                , $templateEngineName
              )
          );
        }
      }

      throw new \Exception(sprintf("Check Template Service\n"), null, $e);
    }
    $output->writeln("");
    $output->writeln(sprintf("<info>'%s' template engine service is correctly setted </info>", $templateEngineName));
    $output->writeln("");
    return;
  }

  private function checkOrmService(OutputInterface $output)
  {
    if (!$this->connexionOk)
    {
      $output->writeln("<comment>As ORM service test depends on database test success, it is not executed</comment>");

      return;
    }

    try
    {
      $ormName = $this->configuration->getOrm();

      $output->writeln(sprintf("Current ORM service is '%s'", $ormName));
      $output->writeln("");
      try
      {
        $configuration = $this->configuration->getService($ormName);
        $this->printConf($output, $ormName, $configuration->all());
      }
      catch (\Exception $e)
      {
        $message  = sprintf(
          "%s called from %s in %s scope"
          , $e->getMessage()
          , $this->configuration->getFile()->getFilename()
          , $ormName
        );
        $e        = new \Exception($message);
        throw $e;
      }
      $registry = \registry::get_instance();

      $service = new Core\Service\Builder(
          \bootstrap::getCore()
          , $ormName
          , $configuration
      );

      if ($service->getType() === 'doctrine')
      {
        $output->writeln("");

        $caches = $service->getCacheServices()->all();

        if ($service->isDebug())
        {
          throw new \Exception(sprintf(
              "%s service should not be in debug mode"
              , $service->getName()
            )
          );
        }

        $output->writeln("");

        $options = $configuration->get("options");

        if (!isset($options['orm']['cache']))
        {

          throw new \Exception(sprintf(
              "%s:doctrine:orm:cache must not be empty. In production environment the cache is highly recommanded."
              , $service->getName()
            )
          );
        }

        foreach ($caches as $key => $cache)
        {
          $originalServiceName   = $options['orm']['cache'][$key];
          $originalConfiguration = $this->configuration->getService($originalServiceName);

          $cacheOptions = $originalConfiguration->all();

          if (!empty($cacheOptions["options"]))
          {
            $output->writeln(sprintf(" %s cache service", $originalServiceName));
            $this->printConf($output, $key . ":" . $originalServiceName, $cacheOptions["options"]);
          }

          $originalService = Core\Service\Builder::create(
              \bootstrap::getCore(), $originalServiceName, $originalConfiguration
          );

          if ($originalService->getType() === 'memcache')
          {
            if (!memcache_connect($originalService->getHost(), $originalService->getPort()))
            {
              throw new \Exception(sprintf("Unable to connect to memcache service %s with host '%s' and port '%s'", $originalService->getName(), $originalService->getHost(), $originalService->getPort()));
            }
          }
          if ($originalService->getType() === 'array')
          {
            throw new \Exception(sprintf(
                "%s:doctrine:orm:%s %s service should not be an array cache type"
                , $service->getName()
                , $key
                , $cache->getName()
              )
            );
          }
        }

        try
        {
          $logServiceName = $options['log'];
          $configuration  = $this->configuration->getService($logServiceName);
          $serviceLog     = Core\Service\Builder::create(
              \bootstrap::getCore()
              , $logServiceName
              , $configuration
          );

          $exists = true;
        }
        catch (\Exception $e)
        {
          $exists = false;
        }

        if ($exists)
        {
          throw new \Exception(sprintf(
              "doctrine:orm:log %s service should not be enable"
              , $serviceLog->getName()
            )
          );
        }
      }
    }
    catch (\Exception $e)
    {
      if ($e instanceof \Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException)
      {
        if ($e->getKey() === 'orm')
        {
          $e = new \Exception(sprintf(
                "Missing parameter %s for service %s"
                , $e->getKey()
                , $service->getName()
              )
          );
        }
        else
        {
          $e = new \Exception(sprintf(
                "Missing parameter %s for %s service declared"
                , $e->getKey()
                , $service->getName()
              )
          );
        }
      }

      throw new \Exception(sprintf("Check ORM Service\n"), null, $e);
    }

    $output->writeln("");
    $output->writeln(sprintf("<info>'%s' ORM service is correctly setted </info>", $ormName));
    $output->writeln("");

    return;
  }

  private function printConf($output, $scope, $value, $scopage = false)
  {
    if (is_array($value))
    {
      foreach ($value as $key => $val)
      {
        if ($scopage)
          $key = $scope . ":" . $key;
        $this->printConf($output, $key, $val, $scopage);
      }
    }
    elseif (is_bool($value))
    {
      if ($value === false)
      {
        $value = '0';
      }
      elseif ($value === true)
      {
        $value = '1';
      }
      $output->writeln(sprintf("%s: %s", $scope, $value));
    }
    elseif (!empty($value))
    {
      $output->writeln(sprintf("%s: %s", $scope, $value));
    }
  }

}
