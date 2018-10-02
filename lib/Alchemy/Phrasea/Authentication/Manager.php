<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Model\Entities\Session;
use Alchemy\Phrasea\Model\Entities\User;

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
     * @param User $user
     *
     * @return Session
     */
    public function openAccount(User $user)
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
