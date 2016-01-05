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

use Alchemy\Phrasea\Security\Firewall;

trait FirewallAware
{
    private $firewall;

    /**
     * Set Firewall instance
     *
     * @param Firewall $firewall
     * @return $this
     */
    public function setFirewall(Firewall $firewall)
    {
        $this->firewall = $firewall;

        return $this;
    }

    /**
     * @return Firewall
     */
    public function getFirewall()
    {
        if (null === $this->firewall) {
            throw new \LogicException('Firewall was not set');
        }

        return $this->firewall;
    }

    /**
     * Ensure User has a specific right
     *
     * @param string $right
     */
    public function requireRight($right)
    {
        $this->getFirewall()->requireRight($right);
    }
}
