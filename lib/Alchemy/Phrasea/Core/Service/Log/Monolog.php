<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\Log;

use Alchemy\Phrasea\Core,
    Alchemy\Phrasea\Core\Service,
    Alchemy\Phrasea\Core\Service\ServiceAbstract,
    Alchemy\Phrasea\Core\Service\ServiceInterface;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Monolog extends ServiceAbstract implements ServiceInterface
{
  const DEFAULT_MAX_DAY = 10;
  
  protected $handlers = array(
      'rotate' => 'RotatingFile'
      ,'stream' => 'Stream'
  );
  
  /**
   *
   * @var \Monolog\Logger
   */
  protected $monolog;
  
  public function __construct($name, Array $options)
  {
    parent::__construct($name, $options);

    //defaut to main handler
    $handler = isset($options["handler"]) ? $options["handler"] : false;

    if (!$handler)
    {
      throw new \Exception("You must specify at least one monolog handler");
    }

    if (!array_key_exists($handler, $this->handlers))
    {
      throw new \Exception(sprintf('Unknow monolog handler %s'), $handlerType);
    }

    $handlerName = $this->handlers[$handler];

    $handlerClassName = sprintf('\Monolog\Handler\%sHandler', $handlerName);

    if (!class_exists($handlerClassName))
    {
      throw new \Exception(sprintf('Unknow monolog handler class %s', $handlerClassName));
    }

    if (!isset($options["filename"]))
    {
      throw new \Exception('you must specify a file to write "filename: my_filename"');
    }

    $logPath = __DIR__ . '/../../../../logs';
    
    $file = sprintf('%s/%s', $logPath, $options["filename"]);

    if ($handler == 'rotate')
    {
      $maxDay = isset($options["max_day"]) ? (int) $options["max_day"] : self::DEFAULT_MAX_DAY;

      $handlerInstance = new $handlerClassName($file, $maxDay);
    }
    else
    {
      $handlerInstance = new $handlerClassName($file);
    }
    
    $channel = isset($options["channel"]) ? $options["channel"] : false;
    
    $monologLogger = new \Monolog\Logger($channel);
    
    $monologLogger->pushHandler($handlerInstance);

    $this->monolog = $monologLogger;
  }
  
  public function getService()
  {
    return $this->monolog;
  }
  
  public function getType()
  {
    return 'monolog';
  }
  
  public function getScope()
  {
    return 'log';
  }

}

