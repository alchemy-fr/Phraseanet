<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\H264PseudoStreaming;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

class H264Factory
{
    private $enabled;
    private $logger;
    private $type;
    private $mapping;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param boolean         $enabled
     * @param string          $type
     * @param array           $mapping
     */
    public function __construct(LoggerInterface $logger, $enabled, $type, array $mapping)
    {
        $this->logger = $logger;
        $this->enabled = (Boolean) $enabled;
        $this->type = strtolower($type);
        $this->mapping = $mapping;
    }

    /**
     * Returns a new instance of H264Interface.
     *
     * @return H264Interface
     *
     * @throws InvalidArgumentException if mode type is unknown
     */
    public function createMode($throwException = false, $forceMode = false)
    {
        if (false === $this->enabled && true !== $forceMode) {
            return new NullMode();
        }

        switch ($this->type) {
            case 'apache':
            case 'apache2':
                return new Apache($this->mapping);
            case 'nginx':
                return new Nginx($this->mapping);
            default:
                $this->logger->error('Invalid h264 pseudo streaming configuration.');
                if ($throwException) {
                    throw new InvalidArgumentException(sprintf('Invalid h264 pseudo streaming configuration width type "%s"', $this->type));
                }

                return new NullMode();
        }
    }

    /**
     * Creates a new instance of H264 Factory given a configuration.
     *
     * @param Application $app
     *
     * @return H264Factory
     */
    public static function create(Application $app)
    {
        $conf = $app['phraseanet.configuration']['h264-pseudo-streaming'];

        $mapping = [];

        if (isset($conf['mapping'])) {
            $mapping = $conf['mapping'];
        }

        return new self($app['monolog'], $conf['enabled'], $conf['type'], $mapping);
    }

    /**
     * @return Boolean
     */
    public function isH264Enabled()
    {
        return $this->enabled;
    }
}
