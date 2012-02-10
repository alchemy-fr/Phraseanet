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
class module_console_fileEnsureProductionSetting extends Command
{
  protected $hasErrors = false;
  
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

    $output->writeln("");
    $output->writeln(sprintf("Checking for PROD settings"));
    $output->writeln("=============================");
    $output->writeln("");

    $this->initTests();

    $this->prepareTests($output);

    $this->runTests($output);

    exit(1);
  }

  private function initTests()
  {
    $spec = new Core\Configuration\Application();
    $parser = new Core\Configuration\Parser\Yaml();
    $handler = new Core\Configuration\Handler($spec, $parser);

    $this->configuration = new Core\Configuration($handler, 'prod');

    if (!$this->configuration->isInstalled())
    {
      exit(sprintf("\nPhraseanet is not installed\n"));
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
      exit(sprintf("\nCheck test suite can not continue please correct FATAL error and relaunch.\n"));
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
        $output->writeln("");
      }
    }
    if(!$nbErrors)
    {
       $output->writeln("<info>Your production settings are setted correctly !</info>");
       $output->writeln("");
    }
  }

  private function checkParse(OutputInterface $output)
  {
    $parser = $this
            ->configuration
            ->getConfigurationHandler()
            ->getParser();
    $fileConfig = $this
            ->configuration
            ->getConfigurationHandler()
            ->getSpecification()
            ->getConfigurationFile();
    $fileService = $this
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
      $e = new \Exception($message);
      throw new \Exception(sprintf("Check parsing file\n"), null, $e);
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
      throw new \Exception(sprintf("Check get selected environment\n"), null, $e);
    }

    return;
  }

  private function checkGetSelectedEnvironementFromFile(OutputInterface $output)
  {
    $spec = new Core\Configuration\Application();
    $parser = new Core\Configuration\Parser\Yaml();
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

    return;
  }

  private function checkPhraseanetScope(OutputInterface $output)
  {
    try
    {
      $phraseanet = $this->configuration->getPhraseanet();

      $url = $phraseanet->get("servername");

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

    return;
  }

  private function checkDatabaseScope(OutputInterface $output)
  {
    try
    {
      $connexionName = $this->configuration->getPhraseanet()->get('database');
      $connexion = $this->configuration->getConnexion($connexionName);

      if ($connexion->get("driver") === "pdo_sqlite")
      {
        throw new \Exception("A sqlite database is not recommanded for production environment");
      }

      try
      {
        $config = new \Doctrine\DBAL\Configuration();
        $conn = \Doctrine\DBAL\DriverManager::getConnection(
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
                "%s called from %s in %s:template_engine scope"
                , $e->getMessage()
                , $this->configuration->getFile()->getFilename()
                , "PROD"
                , $templateEngineName
        );
        $e = new \Exception($message);
        throw $e;
      }


      $serviceBuilder = new Core\ServiceBuilder\TemplateEngine(
                      $templateEngineName
                      , $configuration
      );

      $service = $serviceBuilder->buildService();

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
                "%s called from %s in %s scope"
                , $e->getMessage()
                , $this->configuration->getFile()->getFilename()
                , $ormName
        );
        $e = new \Exception($message);
        throw $e;
      }

      $registry = \registry::get_instance();

      $serviceBuilder = new Core\ServiceBuilder\Orm(
                      $ormName
                      , $configuration
                      , array('registry' => $registry)
      );

      $service = $serviceBuilder->buildService();

      if ($service->getType() === 'doctrine')
      {
        $caches = $service->getCacheServices();

        if ($service->isDebug())
        {
          throw new \Exception(sprintf(
                          "%s service should not be in debug mode"
                          , $service->getName()
                  )
          );
        }

        foreach ($caches->all() as $key => $cache)
        {
          if ($cache->getType() === 'array')
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

    return;
  }

}
