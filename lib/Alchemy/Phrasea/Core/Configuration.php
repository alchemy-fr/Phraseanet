<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Configuration
{

  public $debug = false;
  
  protected $dbConf;
  protected $installed;
  

  public function __construct($envName)
  {
    $specifications = new Configuration\PhraseaConfiguration();
    $parser = new Configuration\Parser\Yaml();
    
    $handler = new Configuration\EnvironnementHandler($specifications, $parser);
    
    $configuration = $handler->handle($envName);
    
    var_dump($configuration);
    
    exit;
  }

  public function getMainConfigFileName()
  {
    return 'config';
  }

  /**
   *
   * @return \SplFileObject
   */
  public function getMainConfigFile()
  {
    return $this->mainConfigFile;
  }

  public function getDoctrineConf()
  {
    return (array) $this->dbConf;
  }

  public function isInstalled()
  {
    return $this->installed;
  }

  private function loadEnvironnments($env)
  {
    $filename = $this->loadFileName($env);

    if (!is_file($filename))
      throw new \InvalidArgumentException(sprintf('Config file %s do not exist', $filename));

    $envConf = \Symfony\Component\Yaml\Yaml::parse($filename);

    $this->envsConf[] = $envConf;

    if (isset($envConf["extends"]))
    {
      $this->loadEnvironnments($envConf["extends"]);
    }
    else
    {
      return;
    }
  }

  private function substituteEnvConfToMainConf(Array $mainConf)
  {
    foreach (array_reverse($this->envsConf) as $env)
    {
      $this->conf = array_replace_recursive($this->conf, $env);
    }

    $this->conf = array_replace_recursive($mainConf, $this->conf);
  }

  private function loadFileName($env = 'main')
  {
    if ('main' === $env)
    {
      return sprintf('%s/%s.yml', $this->configFilePath, $this->getMainConfigFileName());
    }
    else
    {
      return sprintf('%s/%s_%s.yml', $this->configFilePath, $this->getMainConfigFileName(), $env);
    }
  }

  private function getNoneReplacedPath()
  {
    return array(
        array('doctrine', 'dbal')
    );
  }

  private function process($env)
  {
    $mainConf = \Symfony\Component\Yaml\Yaml::parse($this->loadFileName());

    $this->loadEnvironnments($env);

    $this->substituteEnvConfToMainConf($mainConf);

    $path = array();

    $excludedPath = $pathToProcess = $this->getNoneReplacedPath();

    $confToChange = $this->conf;

    foreach (array_reverse($this->envsConf) as $conf)
    {
      foreach ($pathToProcess as $key => $thePath)
      {
        $replaceValue = $this->getDataPath($conf, $thePath);
        
        if (null !== $replaceValue)
        {
          $map = function($item, $key) use (&$confToChange, $replaceValue, &$map, &$path, $thePath)
                  {
                      if (count(array_diff($path, $thePath) === 0))
                      {
                        
                        $path[] = $key;
                        $replace = function(&$searchArray, $path, $value, $depth = 0) use (&$replace)
                                {
                                  foreach ($searchArray as $k => $v)
                                  {
                                    if ($k === $path[$depth])
                                    {
                                      array_shift($path);
                                      if (is_array($v) && count($path) !== 0)
                                      {
                                        $replace(&$searchArray[$k], $path, $value, $depth++);
                                      }
                                      else
                                      {
                                        $searchArray[$k] = $value;
                                      }
                                    }
                                  }
                                };
                        $replace($confToChange, $path, $replaceValue);
                      }
                      elseif (is_array($item))
                      {
                        $path[] = $key;
                        array_walk($item, $map);
                      }
                  };
           
          array_walk($this->conf, $map);
          unset($pathToProcess[$key]);
        }
      }
    }
  }

  public function getDataPath(Array $data, Array $path)
  {
    $found = true;

    for ($x = 0; ($x < count($path) && $found); $x++)
    {
      $key = $path[$x];

      if (isset($data[$key]))
      {
        $data = $data[$key];
      }
      else
      {
        $found = false;
      }
    }

    if ($found)
      return $data;
    else
      return null;
  }

}