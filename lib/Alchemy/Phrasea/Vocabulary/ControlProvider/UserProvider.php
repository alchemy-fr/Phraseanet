<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Vocabulary\ControlProvider;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\Vocabulary\Term;

class UserProvider implements ControlProviderInterface
{

    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return string
     */
    public static function getType()
    {
        return 'User';
    }

    /**
     *
     * @return type
     */
    public function getName()
    {
        return $this->app['translator']->trans('Users');
    }

    /**
     *
     * @param  string                                       $query
     * @param  User                                         $for_user
     * @param  \databox                                     $on_databox
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function find($query, User $for_user,\databox $on_databox = null)
    {
        $user_query = $this->app['phraseanet.user-query'];

        $users = $user_query
                ->like(\User_Query::LIKE_EMAIL, $query)
                ->like(\User_Query::LIKE_NAME, $query)
                ->like(\User_Query::LIKE_LOGIN, $query)
                ->like_match(\User_Query::LIKE_MATCH_OR)
                ->include_phantoms(true)
                ->on_bases_where_i_am($this->app['acl']->get($for_user), ['canadmin'])
                ->limit(0, 50)
                ->execute()->get_results();

        $results = new ArrayCollection();

        foreach ($users as $user) {
            $results->add(
                new Term($user->getDisplayName(), '', $this, $user->getId())
            );
        }

        return $results;
    }

    /**
     *
     * @param  mixed   $id
     * @return boolean
     */
    public function validate($id)
    {
        return (Boolean) $this->app['repo.users']->find($id);
    }

    /**
     *
     * @param  mixed  $id
     * @return string
     */
    public function getValue($id)
    {
        $user = $this->app['repo.users']->find($id);

        if (null === $user) {
            throw new \Exception('User unknown');
        }

        return $user->getDisplayName();
    }

    /**
     *
     * @param  mixed  $id
     * @return string
     */
    public function getRessource($id)
    {
        return $this->app['repo.users']->find($id);
    }
}
