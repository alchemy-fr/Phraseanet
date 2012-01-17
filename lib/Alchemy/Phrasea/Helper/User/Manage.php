<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\User;

use Alchemy\Phrasea\Core;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Manage extends \Alchemy\Phrasea\Helper\Helper
{

  /**
   *
   * @var array
   */
  protected $results;
  /**
   *
   * @var array
   */
  protected $query_parms;
  /**
   *
   * @var int
   */
  protected $usr_id;

  public function __construct(Symfony\Component\HttpFoundation\Request $request)
  {
    $this->request = $request;


    return $this;
  }
  
  public function export(Symfony\Component\HttpFoundation\Request $request)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();

    $offset_start = (int) $request->get('offset_start');
    $offset_start = $offset_start < 0 ? 0 : $offset_start;

    $this->query_parms = array(
        'inactives' => $request->get('inactives')
        , 'like_field' => $request->get('like_field')
        , 'like_value' => $request->get('like_value')
        , 'sbas_id' => $request->get('sbas_id')
        , 'base_id' => $request->get('base_id')
        , 'srt' => $request->get("srt", User_Query::SORT_CREATIONDATE)
        , 'ord' => $request->get("ord", User_Query::ORD_DESC)
        , 'offset_start' => 0
    );

    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
    $query = new User_Query($appbox);

    if (is_array($this->query_parms['base_id']))
      $query->on_base_ids($this->query_parms['base_id']);
    elseif (is_array($this->query_parms['sbas_id']))
      $query->on_sbas_ids($this->query_parms['sbas_id']);

    $this->results = $query->sort_by($this->query_parms["srt"], $this->query_parms["ord"])
            ->like($this->query_parms['like_field'], $this->query_parms['like_value'])
            ->get_inactives($this->query_parms['inactives'])
            ->include_templates(false)
            ->on_bases_where_i_am($user->ACL(), array('canadmin'))
            ->execute();

    return $this->results->get_results();
  }

  public function search(Symfony\Component\HttpFoundation\Request $request)
  {
    $appbox = \appbox::get_instance();

    $offset_start = (int) $this->request->get('offset_start');
    $offset_start = $offset_start < 0 ? 0 : $offset_start;
    $results_quantity = (int) $this->request->get('per_page');
    $results_quantity = ($results_quantity < 10 || $results_quantity > 50) ? 20 : $results_quantity;

    $this->query_parms = array(
        'inactives' => $this->request->get('inactives')
        , 'like_field' => $this->request->get('like_field')
        , 'like_value' => $this->request->get('like_value')
        , 'sbas_id' => $this->request->get('sbas_id')
        , 'base_id' => $this->request->get('base_id')
        , 'srt' => $this->request->get("srt", \User_Query::SORT_CREATIONDATE)
        , 'ord' => $this->request->get("ord", \User_Query::ORD_DESC)
        , 'per_page' => $results_quantity
        , 'offset_start' => $offset_start
    );

    $user = $this->getCore()->getAuthenticatedUser();
    $query = new \User_Query($appbox);

    if (is_array($this->query_parms['base_id']))
      $query->on_base_ids($this->query_parms['base_id']);
    elseif (is_array($this->query_parms['sbas_id']))
      $query->on_sbas_ids($this->query_parms['sbas_id']);

    $this->results = $query->sort_by($this->query_parms["srt"], $this->query_parms["ord"])
            ->like($this->query_parms['like_field'], $this->query_parms['like_value'])
            ->get_inactives($this->query_parms['inactives'])
            ->include_templates(true)
            ->on_bases_where_i_am($user->ACL(), array('canadmin'))
            ->limit($offset_start, $results_quantity)
            ->execute();

    try
    {
      $invite_id = \User_Adapter::get_usr_id_from_login('invite');
      $invite = \User_Adapter::getInstance($invite_id, $appbox);
    }
    catch (\Exception $e)
    {
      $invite = \User_Adapter::create($appbox, 'invite', 'invite', '', false);
    }

    try
    {
      $autoregister_id = \User_Adapter::get_usr_id_from_login('autoregister');
      $autoregister = \User_Adapter::getInstance($autoregister_id, $appbox);
    }
    catch (Exception $e)
    {
      $autoregister = \User_Adapter::create($appbox, 'autoregister', 'autoregister', '', false);
    }

    foreach ($this->query_parms as $k => $v)
    {
      if (is_null($v))
        $this->query_parms[$k] = false;
    }
    
    
    $query = new \User_Query($appbox);
    $templates = $query
            ->only_templates(true)
            ->execute()->get_results();

    return array(
        'users' => $this->results,
        'parm' => $this->query_parms,
        'invite_user' => $invite,
        'autoregister_user' => $autoregister,
        'templates' => $templates
    );
  }

  public function create_newuser()
  {
    $email = $this->request->get('value');

    if(!\mail::validateEmail($email))
    {
      throw new \Exception_InvalidArgument(_('Invalid mail address'));
    }

    $appbox = \appbox::get_instance();

    $conn = $appbox->get_connection();
    $sql = 'SELECT usr_id FROM usr WHERE usr_mail = :email';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':email' => $email));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = count($row);

    if (!is_array($row) || $count == 0)
    {
      $created_user = \User_Adapter::create($appbox, $email, \random::generatePassword(16), $email, false, false);
      $this->usr_id = $created_user->get_id();
    }
    else
    {
      $this->usr_id = $row['usr_id'];
      $created_user = \User_Adapter::getInstance($this->usr_id, $appbox);
    }

    return $created_user;
  }

  public function create_template()
  {
    $name = $this->request->get('value');

    if(trim($name) === '')
    {
      throw new \Exception_InvalidArgument(_('Invalid template name'));
    }

    $appbox = \appbox::get_instance();
    $user = $this->getCore()->getAuthenticatedUser();

    $created_user = \User_Adapter::create($appbox, $name, \random::generatePassword(16), null, false, false);
    $created_user->set_template($user);
    $this->usr_id = $user->get_id();

    return $created_user;
  }

}
