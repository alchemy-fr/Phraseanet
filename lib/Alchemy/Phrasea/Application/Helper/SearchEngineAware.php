<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Application\Helper;

use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineLogger;

trait SearchEngineAware
{
    private $searchEngine;
    private $searchEngineLogger;

    /**
     * Set Locator to use to locate SearchEngine
     *
     * @param callable $locator
     * @return $this
     */
    public function setSearchEngineLocator(callable $locator)
    {
        $this->searchEngine = $locator;

        return $this;
    }

    /**
     * @return SearchEngineInterface
     */
    public function getSearchEngine()
    {
        if ($this->searchEngine instanceof SearchEngineInterface) {
            return $this->searchEngine;
        }

        if (null === $this->searchEngine) {
            throw new \LogicException('Search Engine locator was not set');
        }

        $instance = call_user_func($this->searchEngine);
        if (!$instance instanceof SearchEngineInterface) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                SearchEngineInterface::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->searchEngine = $instance;

        return $this->searchEngine;
    }

    /**
     * Set Locator to use to locate SearchEngine
     *
     * @param callable $locator
     * @return $this
     */
    public function setSearchEngineLoggerLocator(callable $locator)
    {
        $this->searchEngineLogger = $locator;

        return $this;
    }

    /**
     * @return SearchEngineLogger
     */
    public function getSearchEngineLogger()
    {
        if ($this->searchEngineLogger instanceof SearchEngineLogger) {
            return $this->searchEngineLogger;
        }

        if (null === $this->searchEngineLogger) {
            throw new \LogicException('Search Engine logger locator was not set');
        }

        $instance = call_user_func($this->searchEngineLogger);
        if (!$instance instanceof SearchEngineLogger) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                SearchEngineLogger::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->searchEngineLogger = $instance;

        return $this->searchEngineLogger;
    }
}
