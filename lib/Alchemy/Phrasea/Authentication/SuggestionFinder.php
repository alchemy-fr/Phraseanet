<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Authentication\Provider\Token\Token;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;

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
     * @return null|\User_Adapter
     *
     * @throws NotAuthenticatedException In case the token is not authenticated.
     */
    public function find(Token $token)
    {
        $infos = $token->getIdentity();

        if ($infos->has(Identity::PROPERTY_EMAIL)) {

            $sql = 'SELECT usr_id FROM usr WHERE usr_mail = :email';
            $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute(array(':email' => $infos->get(Identity::PROPERTY_EMAIL)));
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($row) {
                return \User_Adapter::getInstance($row['usr_id'], $this->app);
            }
        }

        return null;
    }
}
