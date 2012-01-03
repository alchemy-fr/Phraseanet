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
 * Handle configuration mechanism
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Handler
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
  
  /**
   * The environnment selected 
   * @var string 
   */
  private $selectedEnvironnment;

  /**
   * Tell handler the configuration specification ans which parser to use
   * 
   * @param ConfigurationSpecification $configSpec
   * @param Parser\ParserInterface $parser 
   */
  public function __construct(Specification $configSpec, Parser $parser)
  {
    $this->confSpecification = $configSpec;
    $this->parser = $parser;
  }

  /**
   * Getter
   * @return Specification 
   */
  public function getSpecification()
  {
    return $this->confSpecification;
  }

  /**
   * Getter
   * @return Parser
   */
  public function getParser()
  {
    return $this->parser;
  }

  /**
   * Handle the configuration process and return the final configuration
   * 
   * @param strinig $name the name of the loaded environnement
   * @return Array
   */
  public function handle($selectedEnv = null)
  {
    //get the correspondant file
    $file = $this->confSpecification->getConfigurationFile();
    //parse
    $env = $this->parser->parse($file);

    //get selected env
    if (null === $selectedEnv)
    {
      $selectedEnv = $this->confSpecification->getSelectedEnv($env);
    }

    $this->selectedEnvironnment = $selectedEnv;
    
    if (!isset($env[$selectedEnv]))
    {
      throw new \Exception(sprintf('Undeclared environment %s', $selectedEnv));
    }

    return $env[$selectedEnv];
  }

  public function getSelectedEnvironnment()
  {
    return $this->selectedEnvironnment;
  }


}
