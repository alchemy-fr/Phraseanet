<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication;

use Entities\Session;

class Manager
{
    private $authenticator;
    private $providers;

    public function __construct(Authenticator $authenticator, ProvidersCollection $providers)
    {
        $this->authenticator = $authenticator;
        $this->providers = $providers;
    }

    /**
     *
     * @param \User_Adapter $user
     *
     * @return Session
     */
    public function openAccount(\User_Adapter $user)
    {
        return $this->authenticator->openAccount($user);
    }

    /**
     * Return a RedirectResponse
     */
    public function authenticate(array $parameters, $provider)
    {
        return $this->providers
            ->get($provider)
            ->authenticate($parameters);
    }

    public function getProviders()
    {
        return $this->providers;
    }
}
