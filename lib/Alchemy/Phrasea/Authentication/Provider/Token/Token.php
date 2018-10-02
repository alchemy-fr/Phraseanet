<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Provider\Token;

use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;

class Token
{
    private $id;
    private $provider;

    public function __construct(ProviderInterface $provider, $id)
    {
        $this->id = $id;
        $this->provider = $provider;
    }

    /**
     * Returns the id related to the token
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the provider related to the token
     *
     * @return ProviderInterface
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Returns the identity related to the token
     *
     * @return Identity
     */
    public function getIdentity()
    {
        return $this->provider->getIdentity();
    }

    /**
     * Returns an array of templates related to the Identity
     *
     * @return array
     */
    public function getTemplates()
    {
        return $this->provider->getTemplates($this->provider->getIdentity());
    }
}
