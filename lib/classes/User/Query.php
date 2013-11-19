<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\Common\Collections\ArrayCollection;

class User_Query implements User_QueryInterface
{
    /**
     *
     * @var Application
     */
    protected $app;

    /**
     *
     * @var Array
     */
    protected $results = [];

    /**
     *
     * @var Array
     */
    protected $sort = [];

    /**
     *
     * @var Array
     */
    protected $like_field = [];

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
    protected $active_bases = [];

    /**
     *
     * @var Array
     */
    protected $active_sbas = [];

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
     * @var boolean
     */
    protected $email_not_null = false;

    /**
     *
     * @var Array
     */
    protected $base_ids = [];

    /**
     *
     * @var Array
     */
    protected $sbas_ids = [];

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
    protected $last_model;

    /**
     *
     * @var int
     */
    protected $results_quantity;
    protected $include_phantoms = true;
    protected $include_special_users = false;
    protected $include_invite = false;
    protected $activities;
    protected $templates;
    protected $companies;
    protected $countries;
    protected $positions;
    protected $in_ids;

    const ORD_ASC = 'asc';
    const ORD_DESC = 'desc';
    const SORT_FIRSTNAME = 'first_name';
    const SORT_LASTNAME = 'last_name';
    const SORT_COMPANY = 'company';
    const SORT_LOGIN = 'login';
    const SORT_EMAIL = 'email';
    const SORT_ID = 'id';
    const SORT_CREATIONDATE = 'created';
    const SORT_COUNTRY = 'country';
    const SORT_LASTMODEL = 'last_model';
    const LIKE_FIRSTNAME = 'first_name';
    const LIKE_LASTNAME = 'last_name';
    const LIKE_NAME = 'name';
    const LIKE_COMPANY = 'company';
    const LIKE_LOGIN = 'login';
    const LIKE_EMAIL = 'email';
    const LIKE_COUNTRY = 'country';
    const LIKE_MATCH_AND = 'AND';
    const LIKE_MATCH_OR = 'OR';

    /**
     *
     * @return User_Query
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
            $this->active_sbas[] = $databox->get_sbas_id();
            foreach ($databox->get_collections() as $collection) {
                $this->active_bases[] = $collection->get_base_id();
            }
        }

        return $this;
    }
    protected $sql_params;

    /**
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
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
        $this->sql_params = [];

        $sql = '
      FROM Users LEFT JOIN basusr ON (Users.id = basusr.usr_id)
       LEFT JOIN sbasusr ON (Users.id = sbasusr.usr_id)
      WHERE 1 ';

        if (! $this->include_special_users) {
            $sql .= ' AND Users.login != "autoregister"
              AND Users.login != "invite" ';
        }

        $sql .= ' AND Users.deleted="0" ';

        if (! $this->include_invite) {
            $sql .= ' AND Users.guest="0" ';
        }

        if ($this->email_not_null) {
            $sql .= ' AND Users.email IS NOT NULL ';
        }

        if ($this->only_templates === true) {
            if (!$this->app['authentication']->getUser()) {
                throw new InvalidArgumentException('Unable to load templates while disconnected');
            }
            $sql .= ' AND model_of = ' . $this->app['authentication']->getUser()->getId();
        } elseif ($this->include_templates === false) {
            $sql .= ' AND model_of=0';
        } elseif ($this->app['authentication']->getUser()) {
            $sql .= ' AND (model_of=0 OR model_of = ' . $this->app['authentication']->getUser()->getId() . ' ) ';
        } else {
            $sql .= ' AND model_of=0';
        }

        if ($this->activities) {
            $sql .= $this->generate_field_constraints('activite', $this->activities);
        }

        if ($this->positions) {
            $sql .= $this->generate_field_constraints('fonction', $this->positions);
        }

        if ($this->countries) {
            $sql .= $this->generate_field_constraints('pays', $this->countries);
        }

        if ($this->companies) {
            $sql .= $this->generate_field_constraints('societe', $this->companies);
        }

        if ($this->templates) {
            $sql .= $this->generate_field_constraints('lastModel', $this->templates);
        }

        if (count($this->base_ids) == 0) {
            if ($this->bases_restrictions)
                throw new Exception('No base available for you, not enough rights');
        } else {
            $extra = $this->include_phantoms ? ' OR base_id IS NULL ' : '';

            $not_base_id = array_diff($this->active_bases, $this->base_ids);

            if (count($not_base_id) > 0 && count($not_base_id) < count($this->base_ids)) {
                $sql .= sprintf('  AND ((base_id != %s ) ' . $extra . ')'
                    , implode(' AND base_id != ', $not_base_id)
                );
            } else {
                $sql .= sprintf(' AND (base_id = %s  ' . $extra . ') '
                    , implode(' OR base_id = ', $this->base_ids)
                );
            }
        }

        if (count($this->sbas_ids) == 0) {
            if ($this->sbas_restrictions)
                throw new Exception('No base available for you, not enough rights');
        } else {
            $extra = $this->include_phantoms ? ' OR sbas_id IS NULL ' : '';

            $not_sbas_id = array_diff($this->active_sbas, $this->sbas_ids);

            if (count($not_sbas_id) > 0 && count($not_sbas_id) < count($this->sbas_ids)) {
                $sql .= sprintf('  AND ((sbas_id != %s ) ' . $extra . ')'
                    , implode(' AND sbas_id != ', $not_sbas_id)
                );
            } else {
                $sql .= sprintf(' AND (sbas_id = %s  ' . $extra . ') '
                    , implode(' OR sbas_id = ', $this->sbas_ids)
                );
            }
        }

        if ($this->in_ids) {
            $sql .= 'AND (Users.id = ' . implode(' OR Users.id = ', $this->in_ids) . ')';
        }

        if ($this->have_rights) {
            foreach ($this->have_rights as $right) {
                $sql .= ' AND basusr.`' . $right . '` = 1 ';
            }
        }

        if ($this->have_not_rights) {
            foreach ($this->have_not_rights as $right) {
                $sql .= ' AND basusr.`' . $right . '` = 0 ';
            }
        }

        if ($this->last_model) {
            $sql .= ' AND Users.lastModel = ' . $this->app['phraseanet.appbox']->get_connection()->quote($this->last_model) . ' ';
        }

        $sql_like = [];

        foreach ($this->like_field as $like_field => $like_value) {
            switch ($like_field) {
                case self::LIKE_NAME:
                    $qrys = [];
                    foreach (explode(' ', $like_value) as $like_val) {
                        if (trim($like_val) === '')
                            continue;

                        $qrys[] = sprintf(
                            ' (Users.`%s` LIKE "%s%%"  COLLATE utf8_unicode_ci
                OR Users.`%s` LIKE "%s%%"  COLLATE utf8_unicode_ci)  '
                            , self::LIKE_FIRSTNAME
                            , str_replace(['"', '%'], ['\"', '\%'], $like_val)
                            , self::LIKE_LASTNAME
                            , str_replace(['"', '%'], ['\"', '\%'], $like_val)
                        );
                    }

                    if (count($qrys) > 0)
                        $sql_like[] = ' (' . implode(' AND ', $qrys) . ') ';

                    break;
                case self::LIKE_FIRSTNAME:
                case self::LIKE_LASTNAME:
                case self::LIKE_COMPANY:
                case self::LIKE_EMAIL:
                case self::LIKE_LOGIN:
                case self::LIKE_COUNTRY:
                    $sql_like[] = sprintf(
                        ' Users.`%s` LIKE "%s%%"  COLLATE utf8_unicode_ci '
                        , $like_field
                        , str_replace(['"', '%'], ['\"', '\%'], $like_value)
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

    protected function generate_field_constraints($fieldName, ArrayCollection $fields)
    {
        $n = 0;
        $constraints = [];

        foreach ($fields as $field) {
            $constraints[':' . $fieldName . $n ++] = $field;
        }
        $sql = ' AND (' . $fieldName . ' = '
            . implode(' OR ' . $fieldName . ' = ', array_keys($constraints)) . ') ';

        $this->sql_params = array_merge($this->sql_params, $constraints);

        return $sql;
    }

    public function in(array $usr_ids)
    {
        $tmp_usr_ids = [];

        foreach ($usr_ids as $usr_id) {
            $tmp_usr_ids[] = (int) $usr_id;
        }

        $this->in_ids = array_unique($tmp_usr_ids);

        return $this;
    }

    public function last_model_is($login = null)
    {
        $this->last_model = $login instanceof User ? $login->getLogin() : $login;

        return $this;
    }

    public function include_phantoms($boolean = true)
    {
        $this->include_phantoms = ! ! $boolean;

        return $this;
    }

    public function include_special_users($boolean = false)
    {
        $this->include_special_users = ! ! $boolean;

        return $this;
    }

    public function include_invite($boolean = false)
    {
        $this->include_invite = ! ! $boolean;

        return $this;
    }

    /**
     *
     * @param  array      $rights
     * @return User_Query
     */
    public function who_have_right(Array $rights)
    {
        $this->have_rights = $rights;

        return $this;
    }

    /**
     *
     * @param  boolean    $boolean
     * @return User_Query
     */
    public function include_templates($boolean)
    {
        $this->include_templates = ! ! $boolean;

        return $this;
    }

    /**
     *
     * @param  boolean    $boolean
     * @return User_Query
     */
    public function only_templates($boolean)
    {
        $this->only_templates = ! ! $boolean;

        return $this;
    }

    /**
     *
     * @param  boolean    $boolean
     * @return User_Query
     */
    public function email_not_null($boolean)
    {
        $this->email_not_null = ! ! $boolean;

        return $this;
    }

    /**
     *
     * @param  array      $rights
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
        $conn = $this->app['phraseanet.appbox']->get_connection();
        $sql = 'SELECT DISTINCT Users.id ' . $this->generate_sql_constraints();

        if ('' !== $sorter = $this->generate_sort_constraint()) {
            $sql .= ' ORDER BY ' . $sorter;
        }

        if (is_int($this->offset_start) && is_int($this->results_quantity)) {
            $sql .= sprintf(
                ' LIMIT %d, %d'
                , $this->offset_start
                , $this->results_quantity
            );
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($this->sql_params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $users = new ArrayCollection();

        foreach ($rs as $row) {
            $users[] = $this->app['manipulator.user']->getRepository()->find($row['usr_id']);
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
        if ($this->total) {
            return $this->total;
        }

        $conn = $this->app['phraseanet.appbox']->get_connection();

        $sql_count = 'SELECT COUNT(DISTINCT Users.id) as total '
            . $this->generate_sql_constraints();

        $stmt = $conn->prepare($sql_count);
        $stmt->execute($this->sql_params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->total = $row['total'];

        $this->page = 1;
        if ($this->total > 0 && is_int($this->offset_start) && is_int($this->results_quantity)) {
            $this->page = floor($this->offset_start / $this->results_quantity) + 1;
            $this->total_page = floor($this->total / $this->results_quantity) + 1;
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
     * @return int
     */
    public function get_total_page()
    {
        $this->get_total();

        return $this->total_page;
    }

    /**
     *
     * @param  ACL        $ACL    User's ACLs
     * @param  array      $rights An array of base rights you need
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

        $this->total = $this->page = $this->total_page = null;

        return $this;
    }

    /**
     *
     * @param  ACL        $ACL
     * @param  array      $rights An array of sbas rights you need
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

        $this->total = $this->page = $this->total_page = null;

        return $this;
    }

    /**
     *
     * @param  int        $offset_start
     * @param  int        $results_quantity
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
     * @param  const      $like_field
     * @param  string     $like_value
     * @return User_Query
     */
    public function like($like_field, $like_value)
    {
        $this->like_field[trim($like_field)] = trim($like_value);
        $this->total = $this->page = $this->total_page = null;

        return $this;
    }

    /**
     * Choose whether multiple like will be treated as AND or OR
     *
     * @param  type       $like_match
     * @return User_Query
     */
    public function like_match($like_match)
    {
        switch ($like_match) {
            case self::LIKE_MATCH_AND:
            case self::LIKE_MATCH_OR:
                $this->like_match = $like_match;
                break;
            default:
                break;
        }
        $this->total = $this->page = $this->total_page = null;

        return $this;
    }

    /**
     * Restrict User search on base_ids
     *
     * @param  array      $base_ids
     * @return User_Query
     */
    public function on_base_ids(Array $base_ids = null)
    {
        if (! $base_ids) {
            return $this;
        }

        $this->bases_restrictions = true;

        $this->include_phantoms(false);

        if (count($this->base_ids) > 0)
            $this->base_ids = array_intersect($this->base_ids, $base_ids);
        else
            $this->base_ids = $base_ids;

        $this->total = $this->page = $this->total_page = null;

        return $this;
    }

    /**
     *
     * @param  array      $sbas_ids
     * @return User_Query
     */
    public function on_sbas_ids(Array $sbas_ids = null)
    {
        if (! $sbas_ids) {
            return $this;
        }

        $this->sbas_restrictions = true;

        $this->include_phantoms(false);

        if (count($this->sbas_ids) > 0)
            $this->sbas_ids = array_intersect($this->sbas_ids, $sbas_ids);
        else
            $this->sbas_ids = $sbas_ids;

        $this->total = $this->page = $this->total_page = null;

        return $this;
    }

    /**
     * Sort results. Sort field and sort order are defined as constants
     * of this object
     *
     * @param  const      $sort
     * @param  const      $ord
     * @return User_Query
     */
    public function sort_by($sort, $ord = 'asc')
    {
        $this->sort[$sort] = $ord;

        return $this;
    }

    public function haveActivities(array $req_activities)
    {
        $Activities = new \Doctrine\Common\Collections\ArrayCollection();

        foreach ($req_activities as $activity) {
            $activity = trim($activity);

            if ($activity === '')
                continue;

            if ($Activities->contains($activity))
                continue;

            $Activities->add($activity);
        }

        if ( ! $Activities->isEmpty()) {
            $this->activities = $Activities;
        }

        return $this;
    }

    public function havePositions(array $req_positions)
    {
        $Positions = new \Doctrine\Common\Collections\ArrayCollection();

        foreach ($req_positions as $Position) {
            $Position = trim($Position);

            if ($Position === '')
                continue;

            if ($Positions->contains($Position))
                continue;

            $Positions->add($Position);
        }

        if ( ! $Positions->isEmpty()) {
            $this->positions = $Positions;
        }

        return $this;
    }

    public function inCountries(array $req_countries)
    {
        $Countries = new \Doctrine\Common\Collections\ArrayCollection();

        foreach ($req_countries as $Country) {
            $Country = trim($Country);

            if ($Country === '')
                continue;

            if ($Countries->contains($Country))
                continue;

            $Countries->add($Country);
        }

        if ( ! $Countries->isEmpty()) {
            $this->countries = $Countries;
        }

        return $this;
    }

    public function inCompanies(array $req_companies)
    {
        $Companies = new \Doctrine\Common\Collections\ArrayCollection();

        foreach ($req_companies as $Company) {
            $Company = trim($Company);

            if ($Company === '')
                continue;

            if ($Companies->contains($Company))
                continue;

            $Companies->add($Company);
        }

        if ( ! $Companies->isEmpty()) {
            $this->companies = $Companies;
        }

        return $this;
    }

    public function haveTemplate(array $req_templates)
    {
        $Templates = new \Doctrine\Common\Collections\ArrayCollection();

        foreach ($req_templates as $Template) {
            $Template = trim($Template);

            if ($Template === '')
                continue;

            if ($Templates->contains($Template))
                continue;

            $Templates->add($Template);
        }

        if ( ! $Templates->isEmpty()) {
            $this->templates = $Templates;
        }

        return $this;
    }

    /**
     * Wheter or not retrieve inactive users
     * (inactive users do not have the "access" right)
     *
     * @param  boolean    $boolean
     * @return User_Query
     */
    public function get_inactives($boolean = true)
    {
        $this->get_inactives = ! ! $boolean;

        return $this;
    }

    public function getRelatedActivities()
    {
        $conn = $this->app['phraseanet.appbox']->get_connection();

        $sql = 'SELECT DISTINCT Users.activity ' . $this->generate_sql_constraints();

        $sql .= ' ORDER BY Users.activity';

        $stmt = $conn->prepare($sql);
        $stmt->execute($this->sql_params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $activities = [];

        foreach ($rs as $row) {
            if (trim($row['activity']) === '')
                continue;

            $activities[] = $row['activite'];
        }

        return $activities;
    }

    public function getRelatedPositions()
    {
        $conn = $this->app['phraseanet.appbox']->get_connection();

        $sql = 'SELECT DISTINCT Users.job ' . $this->generate_sql_constraints();

        $sql .= ' ORDER BY Users.job';

        $stmt = $conn->prepare($sql);
        $stmt->execute($this->sql_params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $fonction = [];

        foreach ($rs as $row) {
            if (trim($row['job']) === '')
                continue;

            $fonction[] = $row['job'];
        }

        return $fonction;
    }

    public function getRelatedCountries()
    {
        require_once __DIR__ . '/../../classes/deprecated/countries.php';

        $conn = $this->app['phraseanet.appbox']->get_connection();

        $sql = 'SELECT DISTINCT Users.country ' . $this->generate_sql_constraints();

        $sql .= ' ORDER BY Users.country';

        $stmt = $conn->prepare($sql);
        $stmt->execute($this->sql_params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $pays = [];

        $ctry = \getCountries($this->app['locale']);

        foreach ($rs as $row) {
            if (trim($row['country']) === '')
                continue;

            if (isset($ctry[$row['country']]))
                $pays[$row['country']] = $ctry[$row['country']];
        }

        return $pays;
    }

    public function getRelatedCompanies()
    {
        $conn = $this->app['phraseanet.appbox']->get_connection();

        $sql = 'SELECT DISTINCT Users.company ' . $this->generate_sql_constraints();

        $sql .= ' ORDER BY Users.company';

        $stmt = $conn->prepare($sql);
        $stmt->execute($this->sql_params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $societe = [];

        foreach ($rs as $row) {
            if (trim($row['company']) === '')
                continue;

            $societe[] = $row['company'];
        }

        return $societe;
    }

    public function getRelatedTemplates()
    {
        $conn = $this->app['phraseanet.appbox']->get_connection();

        $sql = 'SELECT DISTINCT Users.last_model ' . $this->generate_sql_constraints();

        $sql .= ' ORDER BY Users.last_model';

        $stmt = $conn->prepare($sql);
        $stmt->execute($this->sql_params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $lastModel = [];

        foreach ($rs as $row) {
            if (trim($row['last_model']) === '')
                continue;

            $lastModel[] = $row['last_model'];
        }

        return $lastModel;
    }

    private function generate_sort_constraint()
    {
        $sorter = [];

        foreach ($this->sort as $sort => $ord) {

            $k = count($sorter);

            switch ($sort) {
                case self::SORT_FIRSTNAME:
                case self::SORT_LASTNAME:
                case self::SORT_COMPANY:
                case self::SORT_LOGIN:
                case self::SORT_EMAIL:
                    $sorter[$k] = ' Users.`' . $sort . '` COLLATE utf8_unicode_ci ';
                    break;
                case self::SORT_ID:
                case self::SORT_CREATIONDATE:
                case self::SORT_COUNTRY:
                case self::SORT_LASTMODEL:
                    $sorter[$k] = ' Users.`' . $sort . '` ';
                    break;
                default:
                    break;
            }

            if ( ! isset($sorter[$k]))
                continue;

            switch ($ord) {
                case self::ORD_ASC:
                default:
                    $sorter[$k] .= ' ASC ';
                    break;
                case self::ORD_DESC:
                    $sorter[$k] .= ' DESC ';
                    break;
            }
        }

        return implode(', ', $sorter);
    }
}
