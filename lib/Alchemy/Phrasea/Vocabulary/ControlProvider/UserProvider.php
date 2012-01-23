<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Vocabulary\ControlProvider;

use Doctrine\Common\Collections\ArrayCollection;

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
   * @param string $query
   * @param \User_Adapter $for_user
   * @param \databox $on_databox
   * @return \Doctrine\Common\Collections\ArrayCollection 
   */
  public static function find($query, \User_Adapter $for_user, \databox $on_databox)
  {
    $user_query = new \User_Query(appbox::get_instance());

    $users = $user_query
        ->like(\User_Query::LIKE_EMAIL, $query)
        ->like(\User_Query::LIKE_FIRSTNAME, $query)
        ->like(\User_Query::LIKE_LASTNAME, $query)
        ->like(\User_Query::LIKE_LOGIN, $query)
        ->like_match(\User_Query::LIKE_MATCH_OR)
        ->on_base_ids(array_keys($for_user->ACL()->get_granted_base(array('canadmin'))))
        ->limit(0, 50)
        ->execute()->get_results();

    $results = new ArrayCollection();

    foreach ($users as $user)
    {
      $results->add(
        new Term($user->get_display_name(), '', $this, $user->get_id())
      );
    }

    return $results;
  }

  /**
   *
   * @param mixed $id
   * @return boolean 
   */
  public static function validate($id)
  {
    try
    {
      \User_Adapter::getInstance($id, appbox::get_instance());
      return true;
    }
    catch (\Exception $e)
    {
      
    }

    return false;
  }

  /**
   *
   * @param mixed $id
   * @return string 
   */
  public static function getValue($id)
  {
    $user = \User_Adapter::getInstance($id, appbox::get_instance());

    return $user->get_display_name();
  }

}
