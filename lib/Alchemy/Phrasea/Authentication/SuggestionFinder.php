<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Authentication\Provider\Token\Token;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use Alchemy\Phrasea\Model\Entities\User;

class SuggestionFinder
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Find a matching user given a token
     *
     * @param Token $token
     *
     * @return null|User
     *
     * @throws NotAuthenticatedException In case the token is not authenticated.
     */
    public function find(Token $token)
    {
        $infos = $token->getIdentity();

        if ($infos->has(Identity::PROPERTY_EMAIL)) {
           return $this->app['manipulator.user']->getRepository()->findByEmail($infos->get(Identity::PROPERTY_EMAIL));
        }

        return null;
    }
}
