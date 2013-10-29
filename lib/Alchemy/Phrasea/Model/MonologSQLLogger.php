<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model;

use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerInterface;

/**
 * Log doctrine sql request with monolog
 *
 * Please move this to a service provider as follow
 * http://srcmvn.com/blog/2011/11/10/doctrine-dbal-query-logging-with-monolog-in-silex/
 */
class MonologSQLLogger implements SQLLogger
{
    const JSON = 'json';
    const YAML = 'yaml';
    const VDUMP = 'vdump';

    /**
     * @var LoggerInterface
     */
    private $logger;
    private $start;
    private $output = array();
    private $outputType;

    /**
     * Tell which monolog user to use and which format to output
     *
     * @param LoggerInterface   $logger A monolog logger instance
     * @param string            $type   the output format
     */
    public function __construct(LoggerInterface $logger, $type = self::YAML)
    {
        $this->logger = $logger;
        $this->outputType = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->start = microtime(true);

        $this->output["sql"] = $sql;

        if ($params) {
            $this->output["params"] = $params;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        $mstime = microtime(true) - $this->start;

        $this->output["times"] = $mstime . " seconds";
        if ($this->outputType == self::JSON) {
            $this->log(json_encode($this->output));
        } elseif ($this->outputType == self::YAML) {
            $this->log(\Symfony\Component\Yaml\Yaml::dump($this->output));
        } else {
            $this->log(var_export($this->output, true));
        }
    }

    protected function log($message)
    {
        $this->logger->debug($message);
    }
}
