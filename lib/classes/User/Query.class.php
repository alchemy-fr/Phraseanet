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
 * @package     User
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class User_Query implements User_QueryInterface
{

  /**
   *
   * @var appbox
   */
  protected $appbox;
  /**
   *
   * @var Array
   */
  protected $results = array();
  /**
   *
   * @var Array
   */
  protected $sort = array();
  /**
   *
   * @var Array
   */
  protected $like_field = array();
  /**
   *
   * @var Array
   */
  protected $have_rights;
  /**
   *
   * @var Array
   */
  protected $have_not_rights;
  /**
   *
   * @var string
   */
  protected $like_match = 'OR';
  /**
   *
   * @var string
   */
  protected $get_inactives = '';
  /**
   *
   * @var int
   */
  protected $total = 0;
  /**
   *
   * @var Array
   */
  protected $active_bases = array();
  /**
   *
   * @var Array
   */
  protected $active_sbas = array();
  /**
   *
   * @var boolean
   */
  protected $bases_restrictions = false;
  /**
   *
   * @var boolean
   */
  protected $sbas_restrictions = false;
  /**
   *
   * @var boolean
   */
  protected $include_templates = false;
  /**
   *
   * @var boolean
   */
  protected $only_templates = false;
  /**
   *
   * @var Array
   */
  protected $base_ids = array();
  /**
   *
   * @var Array
   */
  protected $sbas_ids = array();
  /**
   *
   * @var int
   */
  protected $page;
  /**
   *
   * @var int
   */
  protected $offset_start;
  /**
   *
   * @var int
   */
  protected $results_quantity;
  protected $include_phantoms = true;
  protected $include_special_users = false;
  protected $include_invite = false;

  const ORD_ASC = 'asc';
  const ORD_DESC = 'desc';

  const SORT_FIRSTNAME= 'usr_prenom';
  const SORT_LASTNAME= 'usr_nom';
  const SORT_COMPANY = 'societe';
  const SORT_LOGIN = 'usr_login';
  const SORT_EMAIL = 'usr_mail';
  const SORT_ID = 'usr_id';
  const SORT_CREATIONDATE = 'usr_creationdate';
  const SORT_COUNTRY = 'pays';
  const SORT_LASTMODEL = 'lastModel';

  const LIKE_FIRSTNAME= 'usr_prenom';
  const LIKE_LASTNAME= 'usr_nom';
  const LIKE_NAME= 'name';
  const LIKE_COMPANY = 'societe';
  const LIKE_LOGIN = 'usr_login';
  const LIKE_EMAIL = 'usr_mail';
  const LIKE_COUNTRY = 'pays';

  const LIKE_MATCH_AND = 'AND';
  const LIKE_MATCH_OR = 'OR';

  /**
   *
   * @return User_Query
   */
  public function __construct(appbox &$appbox)
  {
    $this->appbox = $appbox;

    foreach ($appbox->get_databoxes() as $databox)
    {
      $this->active_sbas[] = $databox->get_sbas_id();
      foreach ($databox->get_collections() as $collection)
      {
        $this->active_bases[] = $collection->get_base_id();
      }
    }

    return $this;
  }

  /**
   *
   * @return Array
   */
  public function get_results()
  {
    return $this->results;
  }

  /**
   *
   * @return string
   */
  protected function generate_sql_constraints()
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();

    $sql = '
      FROM usr LEFT JOIN basusr ON (usr.usr_id = basusr.usr_id)
       LEFT JOIN sbasusr ON (usr.usr_id = sbasusr.usr_id)
      WHERE 1 ';

    if (!$this->include_special_users)
    {
      $sql .= ' AND usr_login != "autoregister"
              AND usr_login != "invite" ';
    }

    $sql .= ' AND usr_login NOT LIKE "(#deleted_%" ';

    if (!$this->include_invite)
    {
      $sql .= ' AND usr.invite=0 ';
    }

    if ($this->only_templates === true)
    {
      $sql .= ' AND model_of = ' . $session->get_usr_id();
    }
    elseif ($this->include_templates === false)
    {
      $sql .= ' AND model_of=0';
    }
    else
    {
      $sql .= ' AND (model_of=0 OR model_of = ' . $session->get_usr_id() . ' ) ';
    }

    $baslist = array();

    if (count($this->base_ids) == 0)
    {
      if ($this->bases_restrictions)
        throw new Exception('No base available for you, not enough rights');
    }
    else
    {
      $extra = $this->include_phantoms ? ' OR base_id IS NULL ' : '';
      if (count($this->active_bases) > count($this->base_ids))
      {
        $sql .= sprintf('  AND ((base_id != %s ) ' . $extra . ')'
                , implode(
                        ' AND base_id != '
                        , array_diff(
                                $this->active_bases
                                , $this->base_ids
                        )
                )
        );
      }
      else
      {
        $sql .= sprintf(' AND (base_id = %s  ' . $extra . ') '
                , implode(' OR base_id = ', $this->base_ids)
        );
      }
    }



    if (count($this->sbas_ids) == 0)
    {
      if ($this->sbas_restrictions)
        throw new Exception('No base available for you, not enough rights');
    }
    else
    {
      $extra = $this->include_phantoms ? ' OR sbas_id IS NULL ' : '';
      if (count($this->active_sbas) > count($this->sbas_ids))
      {
        $sql .= sprintf('  AND ((sbas_id != %s ) ' . $extra . ')'
                , implode(
                        ' AND sbas_id != '
                        , array_diff(
                                $this->active_sbas
                                , $this->sbas_ids
                        )
                )
        );
      }
      else
      {
        $sql .= sprintf(' AND (sbas_id = %s  ' . $extra . ') '
                , implode(' OR sbas_id = ', $this->sbas_ids)
        );
      }
    }


    if ($this->have_rights)
    {
      foreach ($this->have_rights as $right)
      {
        $sql .= ' AND basusr.`' . $right . '` = 1 ';
      }
    }

    if ($this->have_not_rights)
    {
      foreach ($this->have_not_rights as $right)
      {
        $sql .= ' AND basusr.`' . $right . '` = 0 ';
      }
    }

    $sql_like = array();
    
    foreach ($this->like_field as $like_field => $like_value)
    {
      switch ($like_field)
      {
        case self::LIKE_FIRSTNAME:
        case self::LIKE_LASTNAME:
        case self::LIKE_COMPANY:
        case self::LIKE_EMAIL:
        case self::LIKE_LOGIN:
        case self::LIKE_COUNTRY:
          $sql_like[] = sprintf(
                  ' usr.`%s` LIKE "%s%%"  COLLATE utf8_unicode_ci '
                  , $like_field
                  , str_replace(array('"', '%'), array('\"', '\%'), $like_value)
          );
          break;
        default;
          break;
      }
    }


    if (count($sql_like) > 0)
      $sql .= sprintf(' AND (%s) ', implode($this->like_match, $sql_like));

    return $sql;
  }

  public function include_phantoms($boolean = true)
  {
    $this->include_phantoms = !!$boolean;

    return $this;
  }

  public function include_special_users($boolean = false)
  {
    $this->include_special_users = !!$boolean;

    return $this;
  }

  public function include_invite($boolean = false)
  {
    $this->include_invite = !!$boolean;

    return $this;
  }

  /**
   *
   * @param array $rights
   * @return User_Query
   */
  public function who_have_right(Array $rights)
  {
    $this->have_rights = $rights;

    return $this;
  }

  /**
   *
   * @param boolean $boolean
   * @return User_Query
   */
  public function include_templates($boolean)
  {
    $this->include_templates = !!$boolean;

    return $this;
  }
  /**
   *
   * @param boolean $boolean
   * @return User_Query
   */
  public function only_templates($boolean)
  {
    $this->only_templates = !!$boolean;

    return $this;
  }

  /**
   *
   * @param array $rights
   * @return User_Query
   */
  public function who_have_not_right(Array $rights)
  {
    $this->have_not_rights = $rights;

    return $this;
  }

  /**
   *
   * @return User_Query
   */
  public function execute()
  {
    $conn = $this->appbox->get_connection();

    $sorter = array();

    foreach ($this->sort as $sort => $ord)
    {

      $k = count($sorter);

      switch ($sort)
      {
        case self::SORT_FIRSTNAME:
        case self::SORT_LASTNAME:
        case self::SORT_COMPANY:
        case self::SORT_LOGIN:
        case self::SORT_EMAIL:
          $sorter[$k] = ' usr.`' . $sort . '` COLLATE utf8_unicode_ci ';
          break;
        case self::SORT_ID:
        case self::SORT_CREATIONDATE:
        case self::SORT_COUNTRY:
        case self::SORT_LASTMODEL:
          $sorter[$k] = ' usr.`' . $sort . '` ';
          break;
        default:
          break;
      }

      if (!isset($sorter[$k]))
        continue;

      switch ($ord)
      {
        case self::ORD_ASC:
        default:
          $sorter[$k] .= ' ASC ';
          break;
        case self::ORD_DESC:
          $sorter[$k] .= ' DESC ';
          break;
      }
    }

    $sql = 'SELECT DISTINCT usr.usr_id ' . $this->generate_sql_constraints();

    $sorter = implode(', ', $sorter);

    if (trim($sorter) != '')
      $sql .= ' ORDER BY ' . $sorter;

    if (is_int($this->offset_start) && is_int($this->results_quantity))
    {
      $sql .= sprintf(
              ' LIMIT %d, %d'
              , $this->offset_start
              , $this->results_quantity
      );
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $users = array();

    foreach ($rs as $row)
    {
      $users[] = User_Adapter::getInstance($row['usr_id'], $this->appbox);
    }

    $this->results = $users;

    return $this;
  }

  /**
   *
   * @return int
   */
  public function get_total()
  {
    if ($this->total)

      return $this->total;

    $conn = $this->appbox->get_connection();

    $sql_count = 'SELECT COUNT(DISTINCT usr.usr_id) as total '
            . $this->generate_sql_constraints();

    $stmt = $conn->prepare($sql_count);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();


    $this->total = $row['total'];

    $this->page = 1;
    if ($this->total > 0 && is_int($this->offset_start) && is_int($this->results_quantity))
    {
      $this->page = floor($this->offset_start / $this->results_quantity) + 1;
    }

    return $this->total;
  }

  /**
   *
   * @return int
   */
  public function get_page()
  {
    $this->get_total();

    return $this->page;
  }

  /**
   *
   * @param ACL $ACL User's ACLs
   * @param array $rights An array of base rights you need
   * @return User_Query
   */
  public function on_bases_where_i_am(ACL $ACL, Array $rights)
  {
    $this->bases_restrictions = true;
    $baslist = array_keys($ACL->get_granted_base($rights));

    if (count($this->base_ids) > 0)
      $this->base_ids = array_intersect($this->base_ids, $baslist);
    else
      $this->base_ids = $baslist;

    $this->total = $this->page = null;

    return $this;
  }

  /**
   *
   * @param ACL $ACL
   * @param array $rights An array of sbas rights you need
   * @return User_Query
   */
  public function on_sbas_where_i_am(ACL $ACL, Array $rights)
  {
    $this->sbas_restrictions = true;
    $sbaslist = array_keys($ACL->get_granted_sbas($rights));

    if (count($this->sbas_ids) > 0)
      $this->sbas_ids = array_intersect($this->sbas_ids, $sbaslist);
    else
      $this->sbas_ids = $sbaslist;

    $this->total = $this->page = null;

    return $this;
  }

  /**
   *
   * @param int $offset_start
   * @param int $results_quantity
   * @return User_Query
   */
  public function limit($offset_start, $results_quantity)
  {
    $this->offset_start = (int) $offset_start;
    $this->results_quantity = (int) $results_quantity;

    return $this;
  }

  /**
   * Query width a like field
   * like fields are defined as constants of the object
   *
   * @param const $like_field
   * @param string $like_value
   * @return User_Query
   */
  public function like($like_field, $like_value)
  {
    
    if($like_field == self::LIKE_NAME)
    {
      $this->like_field[self::LIKE_FIRSTNAME] = trim($like_value);
      $this->like_field[self::LIKE_LASTNAME] = trim($like_value);
    }
    else
    {
      $this->like_field[trim($like_field)] = trim($like_value);
    }
    
    $this->total = $this->page = null;

    return $this;
  }

  /**
   * Choose whether multiple like will be treated as AND or OR
   *
   * @param type $like_match
   * @return User_Query
   */
  public function like_match($like_match)
  {
    switch ($like_match)
    {
      case self::LIKE_MATCH_AND:
      case self::LIKE_MATCH_OR:
        $this->like_match = $like_match;
        break;
      default:
        break;
    }
    $this->total = $this->page = null;

    return $this;
  }

  /**
   * Restrict User search on base_ids
   *
   * @param array $base_ids
   * @return User_Query
   */
  public function on_base_ids(Array $base_ids = null)
  {
    if (!$base_ids)

      return $this;

    $this->bases_restrictions = true;

    $this->include_phantoms(false);

    if (count($this->base_ids) > 0)
      $this->base_ids = array_intersect($this->base_ids, $base_ids);
    else
      $this->base_ids = $base_ids;

    $this->total = $this->page = null;

    return $this;
  }

  /**
   *
   * @param array $sbas_ids
   * @return User_Query
   */
  public function on_sbas_ids(Array $sbas_ids = null)
  {
    if (!$sbas_ids)

      return $this;

    $this->sbas_restrictions = true;

    $this->include_phantoms(false);
    
    if (count($this->sbas_ids) > 0)
      $this->sbas_ids = array_intersect($this->sbas_ids, $sbas_ids);
    else
      $this->sbas_ids = $sbas_ids;

    $this->total = $this->page = null;

    return $this;
  }

  /**
   * Sort results. Sort field and sort order are defined as constants
   * of this object
   *
   * @param const $sort
   * @param const $ord
   * @return User_Query
   */
  public function sort_by($sort, $ord = 'asc')
  {
    $this->sort[$sort] = $ord;

    return $this;
  }

  /**
   * Wheter or not retrieve inactive users
   * (inactive users do not have the "access" right)
   *
   * @param boolean $boolean
   * @return User_Query
   */
  public function get_inactives($boolean = true)
  {
    $this->get_inactives = !!$boolean;

    return $this;
  }

}
