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

/**
 * @todo write tests
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_console_fileConfigCheck extends Command
{

  const PROD  = 'prod';
  const DEV   = 'dev';
  const TEST  = 'test';
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

    $this->setDescription('check configuration file');

    return $this;
  }

  public function execute(InputInterface $input, OutputInterface $output)
  {

    foreach (array(self::DEV, self::PROD, self::TEST) as $env)
    {
      $output->writeln("");
      $output->writeln(sprintf("Checking for %s configuration settings", $env));
      $output->writeln("=========================================");
      $output->writeln("");
      $this->env = $env;

      $this->initTests($output);

      $this->prepareTests($output);

      $this->runTests($output);
    }

    return 0;
  }

  private function initTests(OutputInterface $output)
  {
    $spec    = new Core\Configuration\Application();
    $parser  = new Core\Configuration\Parser\Yaml();
    $handler = new Core\Configuration\Handler($spec, $parser);

    $this->configuration = new Core\Configuration($handler, $this->env);

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
      $output->writeln(sprintf("\nConfig check test suite can not continue please correct FATAL error and relaunch.\n"));

      return 1;
    }
  }

  private function runTests(OutputInterface $output)
  {
    $nbErrors = 0;
    foreach ($this->testSuite as $test)
    {
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
      }
    }

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
      throw new \Exception(sprintf("Check parsing file\n"), null, $e);
    }
    $output->writeln("<info>Parsing File OK</info>");

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
      throw new \Exception(sprintf("Check get selected environment\n"), null, $e);
    }
    $output->writeln("<info>Get Selected Environment OK</info>");

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
      throw new \Exception(sprintf("Check get selected environment from file\n"), null, $e);
    }
    $output->writeln("<info>Get Selected Environment from file OK</info>");

    return;
  }

  private function checkPhraseanetScope(OutputInterface $output)
  {
    try
    {
      $phraseanet = $this->configuration->getPhraseanet();

      $url = $phraseanet->get("servername");

      if ($this->env === self::TEST)
      {
        if ($phraseanet->get("debug") !== true)
        {
          $output->writeln(sprintf(
              "<comment>%s:phraseanet:debug must be initialized to true<:comment>"
              , $this->env
            )
          );
        }

        if ($phraseanet->get("display_errors") !== true)
        {
          throw new \Exception(sprintf(
              "%s:phraseanet:debug must be initialized to true"
              , $this->env
            )
          );
        }
      }

      if ($this->env === self::PROD)
      {
        if ($phraseanet->get("debug") !== false)
        {
          throw new \Exception(sprintf(
              "%s:phraseanet:debug must be initialized to false"
              , $this->env
            )
          );
        }

        if ($phraseanet->get("display_errors") !== false)
        {
          throw new \Exception(sprintf(
              "%s:phraseanet:debug must be initialized to false"
              , $this->env
            )
          );
        }
      }
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf("Check Phraseanet Scope\n"), null, $e);
    }

    $output->writeln("<info>Phraseanet Scope OK</info>");

    return;
  }

  private function checkDatabaseScope(OutputInterface $output)
  {
    try
    {
      $connexionName = $this->configuration->getPhraseanet()->get('database');
      $connexion     = $this->configuration->getConnexion($connexionName);

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
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf("Check Database Scope\n"), null, $e);
    }
    $output->writeln("<info>Database Scope OK</info>");

    return;
  }

  private function checkTeamplateEngineService(OutputInterface $output)
  {
    try
    {
      $templateEngineName = $this->configuration->getTemplating();

      try
      {
        $configuration = $this->configuration->getService($templateEngineName);
      }
      catch (\Exception $e)
      {
        $message = sprintf(
          "%s called from %s in %s:%s:template_engine scope"
          , $e->getMessage()
          , $this->configuration->getFile()->getFilename()
          , $this->env
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

        if (self::PROD === $this->env && $twig->isDebug())
        {
          $output->writeln(sprintf("<comment>%s service should not be in debug mode for %s environment</comment>", $service->getName(), $this->env));
        }
        elseif ((self::TEST === $this->env || self::DEV === $this->env) && !$twig->isDebug())
        {
          $output->writeln(sprintf("<comment>%s service should be in debug mode for %s environment</comment>", $service->getName(), $this->env));
        }

        if ($twig->isStrictVariables() && self::PROD === $this->env)
        {
          $output->writeln(sprintf("<comment>%s service should not be set in strict variables mode for %s environment</comment>", $service->getName(), $this->env));
        }
        elseif ((self::TEST === $this->env || self::DEV === $this->env) && !$twig->isStrictVariables())
        {
          $output->writeln(sprintf("<comment>%s service should be set in strict variables mode for %s environment</comment>", $service->getName(), $this->env));
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
                "Missing parameter %s for %s environment scope"
                , $e->getKey()
                , $this->env
              )
          );
        }
        else
        {
          $e = new \Exception(sprintf(
                "Missing parameter %s for %s service declared in %s scope."
                , $e->getKey()
                , $templateEngineName
                , $this->env
              )
          );
        }
      }

      throw new \Exception(sprintf("Check Template Service\n"), null, $e);
    }

    $output->writeln("<info>Template engine service OK</info>");

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

      try
      {
        $configuration = $this->configuration->getService($ormName);
      }
      catch (\Exception $e)
      {
        $message = sprintf(
          "%s called from %s in %s:orm scope"
          , $e->getMessage()
          , $this->configuration->getFile()->getFilename()
          , $this->env
          , $ormName
        );
        $e       = new \Exception($message);
        throw $e;
      }

      $service = Core\Service\Builder::create(
          \bootstrap::getCore()
          , $ormName
          , $configuration
      );

      if ($service->getType() === 'doctrine')
      {
        $caches = $service->getCacheServices();

        if ($service->isDebug() && self::PROD === $this->env)
        {
          $output->writeln(sprintf(
              "<comment>%s service should not be in debug mode </comment>"
              , $service->getName()
            )
          );
        }
        elseif ((self::TEST === $this->env || self::DEV === $this->env) && !$service->isDebug())
        {
          $output->writeln(sprintf(
              "<comment>%s service should be in debug mode</comment>"
              , $service->getName()
            )
          );
        }

        foreach ($caches->all() as $key => $cache)
        {
          if ($cache->getType() === 'array' && self::PROD === $this->env)
          {
            $output->writeln(sprintf(
                "<comment>%s:doctrine:orm:%s %s service should not be an array cache type for %s environment</comment>"
                , $service->getName()
                , $key
                , $cache->getName()
                , $this->env
              )
            );
          }
          elseif ($cache->getType() !== 'array' && (self::TEST === $this->env || self::DEV === $this->env))
          {
            $output->writeln(sprintf(
                "<comment>%s:doctrine:orm:%s %s service should be an array cache type for %s environment</comment>"
                , $service->getName()
                , $key
                , $cache->getName()
                , $this->env
              )
            );
          }
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
                "Missing parameter %s for %s environment scope"
                , $e->getKey()
                , $this->env
              )
          );
        }
        else
        {
          $e = new \Exception(sprintf(
                "Missing parameter %s for %s service declared in %s scope."
                , $e->getKey()
                , $service->getName()
                , $this->env
              )
          );
        }
      }

      throw new \Exception(sprintf("Check ORM Service\n"), null, $e);
    }

    $output->writeln("<info>ORM service OK</info>");

    return;
  }

}
