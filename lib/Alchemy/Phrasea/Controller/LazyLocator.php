<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller;

class LazyLocator
{
    /** @var \Pimple */
    private $pimple;
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

    public function __invoke()
    {
        return $this->pimple->offsetGet($this->serviceId);
    }
}
