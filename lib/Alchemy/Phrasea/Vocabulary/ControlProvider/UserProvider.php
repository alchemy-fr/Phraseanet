<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Vocabulary\ControlProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\Vocabulary\Term;

/**
 * User Provider
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class UserProvider implements ControlProviderInterface
{

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
    public static function getName()
    {
        return _('Users');
    }

    /**
     *
     * @param  string                                       $query
     * @param  \User_Adapter                                $for_user
     * @param  \databox                                     $on_databox
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function find($query, \User_Adapter $for_user, \databox $on_databox = null)
    {
        $Core = \bootstrap::getCore();

        $user_query = new \User_Query(\appbox::get_instance($Core));

        $users = $user_query
                ->like(\User_Query::LIKE_EMAIL, $query)
                ->like(\User_Query::LIKE_NAME, $query)
                ->like(\User_Query::LIKE_LOGIN, $query)
                ->like_match(\User_Query::LIKE_MATCH_OR)
                ->include_phantoms(true)
                ->on_bases_where_i_am($for_user->ACL(), array('canadmin'))
                ->limit(0, 50)
                ->execute()->get_results();

        $results = new ArrayCollection();

        foreach ($users as $user) {
            $results->add(
                new Term($user->get_display_name(), '', $this, $user->get_id())
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
        $Core = \bootstrap::getCore();

        try {
            \User_Adapter::getInstance($id, \appbox::get_instance($Core));

            return true;
        } catch (\Exception $e) {

        }

        return false;
    }

    /**
     *
     * @param  mixed  $id
     * @return string
     */
    public function getValue($id)
    {
        $Core = \bootstrap::getCore();

        $user = \User_Adapter::getInstance($id, \appbox::get_instance($Core));

        return $user->get_display_name();
    }

    /**
     *
     * @param  mixed  $id
     * @return string
     */
    public function getRessource($id)
    {
        $Core = \bootstrap::getCore();

        return \User_Adapter::getInstance($id, \appbox::get_instance($Core));
    }
}
