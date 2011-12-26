<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

use \Symfony\Component\Yaml\Yaml;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class EnvironnementHandler
{
  /**
   * Configuration file specification interface
   * @var ConfigurationSpecification 
   */
  protected $confSpecification;

  /**
   * A file parser interface 
   * @var Parser\ParserInterface
   */
  protected $parser;

  public function __construct(ConfigurationSpecification $configSpec, Parser\ParserInterface $parser)
  {
    $this->confSpecification = $configSpec;
    $this->parser = $parser;
  }

  /**
   * Stacks all envrironnement in $env that extends the loaded configuration file
   * 
   * @param SplFileObject $file File of the current loaded config file
   * @param array $envs A stack of conf environnments
   * @return array 
   */
  private function retrieveExtendedEnvFromFile(\SplFileObject $file, Array $envs = array())
  {
    $env = $this->parser->parse($file);

    //stack current env to allEnvs 
    $allEnvs[] = $env;

    //check if the loaded environnement extends another configuration file
    if ($this->confSpecification->isExtended($env))
    {
      try
      {
        //get extended environnement name
        $envName = $this->confSpecification->getExtendedEnvName($env);
        //get extended configuration file
        $file = $this->confSpecification->getConfFileFromEnvName($envName);
        //recurse
        $this->retrieveExtendedEnvFromFile($file, $allEnvs);
      }
      catch (\Exception $e)
      {
        throw \Exception(sprintf("filename %s not found", $file->getPathname()));
      }
    }

    return $allEnvs;
  }

  /**
   * Get the value of a specified data path 
   * 
   * @param array $data The array where the data are stored
   * @param array $path The Path as an  array example : array('path', 'to', 'my', 'value')
   * @return mixed 
   */
  private function getDataPath(Array $data, Array $path)
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

    return $found ? $data : null;
  }

  /**
   * Handle the configuration process and return the final configuration
   * 
   * @param strinig $name the name of the loaded environnement
   * @return Array
   */
  public function handle($name)
  {

    //get the corresepondant file
    $file = $this->confSpecification->getConfFileFromEnvName($name);

    //get all extended configuration from current env
    $allEnvs = $this->retrieveExtendedEnvFromFile($file);

    //Last env is the main one
    $mainEnv = array_pop($allEnvs);

    $excludedPath = $pathToprocess = $this->confSpecification->getNonExtendablePath();

    //at least 2 envs and one path to process
    if (count($allEnvs) >= 1 && count($excludedPath) >= 1)
    {
      foreach ($allEnvs as $currentEnv) // run trought environnements
      {
        foreach ($pathToprocess as $kpath => $processedPath) //run throught path
        {
          $valueToReplace = $this->getDataPath($currentEnv, $processedPath); //retrive the value to replace

          if (null !== $valueToReplace)
          {

            // reset current path
            $currentPath = array();

            //callback to iterate over the main conf environnement and replace value from extended file
            $map = function($item, $key) use (&$mainEnv, $valueToReplace, &$map, &$currentPath, $processedPath)
                    {
                      if (count(array_diff($processedPath, $currentPath)) === 0) // current path and processed path match
                      {
                        /**
                         * Replace current value of the $currentpath in $searchArray by $value
                         */
                        $replace = function(&$searchArray, $currentPath, $value) use (&$replace)
                                {
                                  foreach ($searchArray as $k => $v)
                                  {
                                    if ($k === $currentPath[0])
                                    {
                                      array_shift($currentPath);

                                      if (is_array($v) && count($currentPath) !== 0)
                                      {
                                        $replace(&$searchArray[$k], $currentPath, $value);
                                      }
                                      elseif (count($currentPath) === 0)
                                      {
                                        $searchArray[$k] = $value;
                                      }
                                    }
                                  }
                                };

                        $replace($mainEnv, $currentPath, $valueToReplace);
                      }
                      elseif (is_array($item)) // if current item is an array
                      {
                        $currentPath[] = $key; // add item's key to current path

                        array_walk($item, $map); // and dig into the current item
                      }
                      else //wrong path
                      {
                        $currentPath = array(); //reset
                      }
                    };

            //run trough the main conf environnement
            array_walk($mainEnv, $map);

            //once done
            //reduce the paths to process
            unset($pathToprocess[$kpath]);

            break;
          }
        }
      }
    }
    
    if(count($allEnvs) >= 1)
    {
      foreach($allEnvs as $extendedEnv)
       $mainEnv = array_replace_recursive($mainEnv, $extendedEnv);
    }

    return $mainEnv;
  }

}
