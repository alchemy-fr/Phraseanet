<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Core;

class LazyLocator
{
    /**
     * @var \Pimple
     */
    private $pimple;

    /**
     * @var string
     */
    private $serviceId;

    /**
     * @param \Pimple $pimple
     * @param string  $serviceId
     */
    public function __construct(\Pimple $pimple, $serviceId)
    {
        $this->pimple = $pimple;
        $this->serviceId = $serviceId;
    }

    /**
     * @return mixed
     */
    public function __invoke()
    {
        return $this->pimple->offsetGet($this->serviceId);
    }
}
