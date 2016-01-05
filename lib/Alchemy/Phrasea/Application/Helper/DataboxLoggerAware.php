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

trait DataboxLoggerAware
{
    private $locator;

    public function setDataboxLoggerLocator(callable $locator)
    {
        $this->locator = $locator;

        return $this;
    }

    /**
     * @param \databox $databox
     * @return \Session_Logger
     */
    public function getDataboxLogger(\databox $databox)
    {
        if (null === $this->locator) {
            throw new \LogicException('Databox logger locator should be set');
        }

        return call_user_func($this->locator, $databox);
    }
}
