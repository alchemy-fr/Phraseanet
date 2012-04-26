<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Monolog extends ServiceAbstract
{
    const DEFAULT_MAX_DAY = 10;

    protected $handlers = array(
        'rotate' => 'RotatingFile'
        , 'stream' => 'Stream'
    );

    /**
     *
     * @var \Monolog\Logger
     */
    protected $monolog;

    protected function init()
    {
        $options = $this->getOptions();

        if (empty($options)) {
            throw new \Exception(sprintf("'%s' service options can not be empty", $this->name));
        }

        //defaut to main handler
        $handler = isset($options["handler"]) ? $options["handler"] : false;

        if ( ! $handler) {
            throw new \Exception(sprintf(
                    "You must specify at least one handler for '%s' service"
                    , __CLASS__
                )
            );
        }

        if ( ! array_key_exists($handler, $this->handlers)) {
            throw new \Exception(sprintf(
                    "The handler type '%s' declared in %s %s service is not valid.
          Available types are %s."
                    , $handler
                    , __CLASS__
                    , implode(", ", $this->handlers)
                )
            );
        }

        $handlerName = $this->handlers[$handler];

        $handlerClassName = sprintf('\Monolog\Handler\%sHandler', $handlerName);

        if ( ! class_exists($handlerClassName)) {
            throw new \Exception(sprintf(
                    'Unable to log monolog handler %s looked for class %s'
                    , $handlerName
                    , $handlerClassName)
            );
        }

        if ( ! isset($options["filename"])) {
            throw new \Exception(sprintf(
                    "Missing filename option in '%s' service"
                    , __CLASS__
                )
            );
        }

        $logPath = __DIR__ . '/../../../../../../logs';

        $file = sprintf('%s/%s', $logPath, $options["filename"]);

        if ($handler == 'rotate') {
            $maxDay = isset($options["max_day"]) ?
                (int) $options["max_day"] :
                self::DEFAULT_MAX_DAY;

            $handlerInstance = new $handlerClassName($file, $maxDay);
        } else {
            $handlerInstance = new $handlerClassName($file);
        }

        $channel = isset($options["channel"]) ? $options["channel"] : "monolog";

        $monologLogger = new \Monolog\Logger($channel);

        $monologLogger->pushHandler($handlerInstance);

        $this->monolog = $monologLogger;
    }

    public function getDriver()
    {
        return $this->monolog;
    }

    public function getType()
    {
        return 'monolog';
    }

    public function getMandatoryOptions()
    {
        return array('channel', 'handler', 'filename');
    }
}

