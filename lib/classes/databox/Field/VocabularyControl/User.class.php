<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     Databox DCES
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_Field_VocabularyControl_User implements databox_Field_VocabularyControl_Interface
{

  public function __construct()
  {

    return $this;
  }

  public static function getType()
  {
    return 'User';
  }

  public static function getName()
  {
    return _('Users');
  }

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

    $results = array();

    foreach ($users as $user)
    {
      $results[] = array(
        'id'    => $user->get_id(),
        'label' => $user->get_display_name(),
      );
    }

    return $results;
  }

}