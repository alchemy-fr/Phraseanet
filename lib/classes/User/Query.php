<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\Utilities\Countries;

class User_Query
{
    const ORD_ASC = 'ASC';
    const ORD_DESC = 'DESC';
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

    protected $app;
    protected $results = [];
    protected $sort = [];
    protected $like_field = [];
    protected $have_rights = null;
    protected $have_not_rights = null;
    protected $like_match = 'OR';
    protected $get_inactives = '';
    protected $total = 0;
    protected $active_bases = [];
    protected $active_sbas = [];
    protected $bases_restrictions = false;
    protected $sbas_restrictions = false;
    protected $include_templates = false;
    protected $only_templates = false;
    protected $email_not_null = false;
    protected $base_ids = [];
    protected $sbas_ids = [];
    protected $page = null;
    protected $offset_start = null;
    protected $last_model = null;
    protected $results_quantity = null;
    protected $include_phantoms = true;
    protected $include_special_users = false;
    protected $include_invite = false;
    protected $activities = null;
    protected $templates = null;
    protected $companies = null;
    protected $countries = null;
    protected $positions = null;
    protected $in_ids = null;
    protected $sqlFilters = [];
    protected $sql_params = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->setActiveBases();
    }

    /**
     * Return query results
     *
     * @return User[]|\Doctrine\Common\Collections\Collection
     */
    public function get_results()
    {
        return $this->results;
    }

    /**
     * Restrict users to the provided ones
     *
     * @param array $usr_ids
     *
     * @return $this
     */
    public function in(array $usr_ids)
    {
        $this->in_ids = array_unique(array_filter(array_map('intval', $usr_ids)));

        return $this;
    }

    public function addSqlFilter($sql, array $params = [])
    {
        $this->sqlFilters[] = ['sql' => $sql, 'params' => $params];
        return $this;
    }

    /**
     * Restrict user with the provided last model
     * 
     * @param User|int|null $login
     *
     * @return $this
     */
    public function last_model_is($login = null)
    {
        $this->last_model = $login instanceof User ? $login->getId() : $login;

        return $this;
    }

    /**
     * Include users with no rights in any base
     *
     * @param bool $boolean
     *
     * @return $this
     */
    public function include_phantoms($boolean = true)
    {
        $this->include_phantoms = !!$boolean;

        return $this;
    }

    /**
     * Include user such as 'guest' and 'autoregister'
     *
     * @param bool $boolean
     *
     * @return $this
     */
    public function include_special_users($boolean = false)
    {
        $this->include_special_users = !!$boolean;

        return $this;
    }

    /**
     * Include guest user
     *
     * @param bool $boolean
     *
     * @return $this
     */
    public function include_invite($boolean = false)
    {
        $this->include_invite = !!$boolean;

        return $this;
    }

    /**
     * Include user with provided rights
     *
     * @param array $rights
     *
     * @return $this
     */
    public function who_have_right(array $rights)
    {
        $this->have_rights = $rights;

        return $this;
    }

    /**
     * Include users who are in reality templates
     *
     * @param $boolean
     *
     * @return $this
     */
    public function include_templates($boolean)
    {
        $this->include_templates = !!$boolean;

        return $this;
    }

    /**
     * Restrict to templates
     *
     * @param $boolean
     *
     * @return $this
     */
    public function only_templates($boolean)
    {
        $this->only_templates = !!$boolean;

        return $this;
    }

    /**
     * Restrict to user with an email
     *
     * @param $boolean
     *
     * @return $this
     */
    public function email_not_null($boolean)
    {
        $this->email_not_null = !!$boolean;

        return $this;
    }

    /**
     * Restrict to users who have provided rights
     *
     * @param array $rights
     *
     * @return $this
     */
    public function who_have_not_right(array $rights)
    {
        $this->have_not_rights = $rights;

        return $this;
    }

    /**
     * Execute query
     *
     * @return $this
     */
    public function execute()
    {
        $conn = $this->app->getApplicationBox()->get_connection();
        list ($sql, $params) = $this->createSelectQuery();

        if (is_int($this->offset_start) && is_int($this->results_quantity)) {
            $sql .= sprintf(
                ' LIMIT %d, %d'
                , $this->offset_start
                , $this->results_quantity
            );
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $userIndexes = [];

        foreach ($rs as $index => $row) {
            $userIndexes[$row['id']] = $index;
        }

        $users = [];

        /** @var User $user */
        foreach ($this->app['repo.users']->findBy(['id' => array_keys($userIndexes)]) as $user) {
            $users[$userIndexes[$user->getId()]] = $user;
        }

        ksort($users);

        $this->results = new ArrayCollection($users);

        return $this;
    }

    /**
     * Get total of fetched users
     *
     * @return int
     */
    public function get_total()
    {
        if ($this->total) {
            return $this->total;
        }

        $conn = $this->app->getApplicationBox()->get_connection();

        $sql_count = 'SELECT COUNT(DISTINCT Users.id) as total ' . $this->generate_sql_constraints();

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
     * Get current page
     *
     * @return null|int
     */
    public function get_page()
    {
        $this->get_total();

        return $this->page;
    }

    /**
     * Get total page
     *
     * @return null|int
     */
    public function get_total_page()
    {
        $this->get_total();

        return $this->total_page;
    }

    /**
     * Restrict users on collection with provided rights
     *
     * @param ACL   $ACL
     * @param array $rights
     *
     * @return $this
     */
    public function on_bases_where_i_am(ACL $ACL, Array $rights)
    {
        $this->bases_restrictions = true;
        $collections = array_keys($ACL->get_granted_base($rights));

        if (count($this->base_ids) > 0) {
            $this->base_ids = array_intersect($this->base_ids, $collections);
        } else {
            $this->base_ids = $collections;
        }

        $this->total = $this->page = $this->total_page = null;

        return $this;
    }

    /**
     * Restrict users on database with provided rights
     *
     * @param ACL   $ACL
     * @param array $rights
     *
     * @return $this
     */
    public function on_sbas_where_i_am(ACL $ACL, Array $rights)
    {
        $this->sbas_restrictions = true;
        $databoxes = array_keys($ACL->get_granted_sbas($rights));

        if (count($this->sbas_ids) > 0)
            $this->sbas_ids = array_intersect($this->sbas_ids, $databoxes);
        else
            $this->sbas_ids = $databoxes;

        $this->total = $this->page = $this->total_page = null;

        return $this;
    }

    /**
     * Restrict to provided limits
     *
     * @param $offset_start
     * @param $results_quantity
     *
     * @return $this
     */
    public function limit($offset_start, $results_quantity)
    {
        $this->offset_start = (int) $offset_start;
        $this->results_quantity = (int) $results_quantity;

        return $this;
    }

    /**
     * Restrict on provided field with provided value
     *
     * @param $like_field
     * @param $like_value
     *
     * @return $this
     */
    public function like($like_field, $like_value)
    {
        $this->like_field[trim($like_field)] = trim($like_value);
        $this->total = $this->page = $this->total_page = null;

        return $this;
    }

    /**
     * Restrict on match
     *
     * @param $like_match
     *
     * @return $this
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
     * Restrict on collections
     *
     * @param array $base_ids
     *
     * @return $this
     */
    public function on_base_ids(array $base_ids = null)
    {
        if (! $base_ids) {
            return $this;
        }

        $this->bases_restrictions = true;

        $this->include_phantoms(false);

        if (count($this->base_ids) > 0) {
            $this->base_ids = array_intersect($this->base_ids, $base_ids);
        } else {
            $this->base_ids = $base_ids;
        }

        $this->total = $this->page = $this->total_page = null;

        return $this;
    }

    /**
     * Restrict on databoxes
     *
     * @param array $sbas_ids
     *
     * @return $this
     */
    public function on_sbas_ids(Array $sbas_ids = null)
    {
        if (! $sbas_ids) {
            return $this;
        }

        $this->sbas_restrictions = true;

        $this->include_phantoms(false);

        if (count($this->sbas_ids) > 0) {
            $this->sbas_ids = array_intersect($this->sbas_ids, $sbas_ids);
        } else {
            $this->sbas_ids = $sbas_ids;
        }

        $this->total = $this->page = $this->total_page = null;

        return $this;
    }

    /**
     * Sort by
     *
     * @param        $sort
     * @param string $ord
     *
     * @return $this
     */
    public function sort_by($sort, $ord = self::ORD_ASC)
    {
        $this->sort[$sort] = $ord;

        return $this;
    }

    /**
     * Restrict users with provided activities
     *
     * @param array $req_activities
     *
     * @return $this
     */
    public function haveActivities(array $req_activities)
    {
        $activities = new ArrayCollection();

        foreach ($req_activities as $activity) {
            if ($activity = trim($activity) === '') {
                continue;
            }

            if ($activities->contains($activity)) {
                continue;
            }

            $activities->add($activity);
        }

        if (!$activities->isEmpty()) {
            $this->activities = $activities;
        }

        return $this;
    }

    /**
     * Restrict users with provided jobs
     *
     * @param array $req_positions
     *
     * @return $this
     */
    public function havePositions(array $req_positions)
    {
        $positions = new ArrayCollection();

        foreach ($req_positions as $position) {
            if ($position = trim($position) === '') {
                continue;
            }
            if ($positions->contains($position)) {
                continue;
            }

            $positions->add($position);
        }

        if (!$positions->isEmpty()) {
            $this->positions = $positions;
        }

        return $this;
    }

    /**
     * Restrict users by countries
     *
     * @param array $req_countries
     *
     * @return $this
     */
    public function inCountries(array $req_countries)
    {
        $countries = new ArrayCollection();

        foreach ($req_countries as $country) {
            if ($country = trim($country) === '') {
                continue;
            }
            if ($countries->contains($country)) {
                continue;
            }

            $countries->add($country);
        }

        if (!$countries->isEmpty()) {
            $this->countries = $countries;
        }

        return $this;
    }

    /**
     * Restrict users by companies
     *
     * @param array $req_companies
     *
     * @return $this
     */
    public function inCompanies(array $req_companies)
    {
        $companies = new ArrayCollection();

        foreach ($req_companies as $company) {
            if ($company = trim($company) === '') {
                continue;
            }
            if ($companies->contains($company)) {
                continue;
            }
            $companies->add($company);
        }

        if (!$companies->isEmpty()) {
            $this->companies = $companies;
        }

        return $this;
    }

    /**
     * Restrict users with given templates
     *
     * @param array $req_templates
     *
     * @return $this
     */
    public function haveTemplate(array $req_templates)
    {
        $templates = new ArrayCollection();

        foreach ($req_templates as $template) {
            if ($template = trim($template) === '') {
                continue;
            }
            if ($templates->contains($template)) {
                continue;
            }
            $templates->add($template);
        }

        if (!$templates->isEmpty()) {
            $this->templates = $templates;
        }

        return $this;
    }

    /**
     * Retrieve inactive use
     * (inactive users do not have the "access" right)
     *
     * @param bool $boolean
     *
     * @return $this
     */
    public function get_inactives($boolean = true)
    {
        $this->get_inactives = !!$boolean;

        return $this;
    }

    /**
     * Get users activities
     *
     * @return array
     */
    public function getRelatedActivities()
    {
        $conn = $this->app->getApplicationBox()->get_connection();

        $sql = 'SELECT DISTINCT Users.activity ' . $this->generate_sql_constraints(). ' ORDER BY Users.activity';

        $stmt = $conn->prepare($sql);
        $stmt->execute($this->sql_params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $activities = [];
        foreach ($rs as $row) {
            if (trim($row['activity']) === '') {
                continue;
            }
            $activities[] = $row['activite'];
        }

        return $activities;
    }

    /**
     * Get users jobs
     *
     * @return array
     */
    public function getRelatedPositions()
    {
        $conn = $this->app->getApplicationBox()->get_connection();

        $sql = 'SELECT DISTINCT Users.job ' . $this->generate_sql_constraints() . ' ORDER BY Users.job';

        $stmt = $conn->prepare($sql);
        $stmt->execute($this->sql_params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $jobs = [];
        foreach ($rs as $row) {
            if (trim($row['job']) === '') {
                continue;
            }
            $jobs[] = $row['job'];
        }

        return $jobs;
    }

    /**
     * Get user countries
     *
     * @return array
     */
    public function getRelatedCountries()
    {
        $conn = $this->app->getApplicationBox()->get_connection();

        $sql = 'SELECT DISTINCT Users.country ' . $this->generate_sql_constraints() . ' ORDER BY Users.country';

        $stmt = $conn->prepare($sql);
        $stmt->execute($this->sql_params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $countries = [];
        $listCountry = Countries::getCountries($this->app['locale']);
        foreach ($rs as $row) {
            if (trim($row['country']) === '') {
                continue;
            }

            if (isset($listCountry[$row['country']])) {
                $countries[$row['country']] = $listCountry[$row['country']];
            }
        }

        return $countries;
    }

    /**
     * Get users companies
     *
     * @return array
     */
    public function getRelatedCompanies()
    {
        $conn = $this->app->getApplicationBox()->get_connection();

        $sql = 'SELECT DISTINCT Users.company ' . $this->generate_sql_constraints() . ' ORDER BY Users.company';

        $stmt = $conn->prepare($sql);
        $stmt->execute($this->sql_params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $companies = [];
        foreach ($rs as $row) {
            if (trim($row['company']) === '') {
                continue;
            }
            $companies[] = $row['company'];
        }

        return $companies;
    }

    /**
     * Get users templates
     *
     * @return array
     */
    public function getRelatedTemplates()
    {
        $conn = $this->app->getApplicationBox()->get_connection();

        $sql = 'SELECT DISTINCT Users.last_model ' . $this->generate_sql_constraints() . ' ORDER BY Users.last_model';

        $stmt = $conn->prepare($sql);
        $stmt->execute($this->sql_params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $lastModel = [];
        foreach ($rs as $row) {
            if (trim($row['last_model']) === '') {
                continue;
            }

            $lastModel[] = $row['last_model'];
        }

        return $lastModel;
    }

    protected function generate_sql_constraints()
    {
        $this->sql_params = [];

        $sql = '
                FROM Users LEFT JOIN basusr ON (Users.id = basusr.usr_id)
                LEFT JOIN sbasusr ON (Users.id = sbasusr.usr_id)
                WHERE 1 ';

        if (! $this->include_special_users) {
            $sql .= ' AND Users.login != "autoregister" AND Users.login != "invite" ';
        }

        $sql .= ' AND Users.deleted="0" ';

        if (! $this->include_invite) {
            $sql .= ' AND Users.guest="0" ';
        }

        if ($this->email_not_null) {
            $sql .= ' AND Users.email IS NOT NULL ';
        }

        if ($this->only_templates === true) {
            if (!$this->app->getAuthenticatedUser()) {
                throw new InvalidArgumentException('Unable to load templates while disconnected');
            }
            $sql .= ' AND model_of = ' . $this->app->getAuthenticatedUser()->getId();
        } elseif ($this->include_templates === false) {
            $sql .= ' AND model_of IS NULL';
        } elseif ($this->app->getAuthenticatedUser()) {
            $sql .= ' AND (model_of IS NULL OR model_of = ' . $this->app->getAuthenticatedUser()->getId() . ' ) ';
        } else {
            $sql .= ' AND model_of IS NULL';
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
            $sql .= $this->generate_field_constraints('last_model', $this->templates);
        }

        if (count($this->base_ids) == 0) {
            if ($this->bases_restrictions) {
                throw new Exception('No base available for you, not enough rights');
            }
        } else {
            $extra = $this->include_phantoms ? ' OR base_id IS NULL ' : '';

            $not_base_id = array_diff($this->active_bases, $this->base_ids);

            if (count($not_base_id) > 0 && count($not_base_id) < count($this->base_ids)) {
                $sql .= sprintf('  AND ((base_id != %s ) ' . $extra . ')', implode(' AND base_id != ', $not_base_id));
            } else {
                $sql .= sprintf(' AND (base_id = %s  ' . $extra . ') ', implode(' OR base_id = ', $this->base_ids));
            }
        }

        if (count($this->sbas_ids) == 0) {
            if ($this->sbas_restrictions) {
                throw new Exception('No base available for you, not enough rights');
            }
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
            $sql .= ' AND (Users.id = ' . implode(' OR Users.id = ', $this->in_ids) . ')';
        }
        if ($this->sqlFilters) {
            foreach ($this->sqlFilters as $sqlFilter) {
                $sql .= ' AND (' . $sqlFilter['sql'] . ')';
                $this->sql_params = array_merge($this->sql_params, $sqlFilter['params']);
            }
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
            $sql .= ' AND Users.last_model = ' . $this->app->getApplicationBox()->get_connection()->quote($this->last_model) . ' ';
        }

        $sql_like = [];

        foreach ($this->like_field as $like_field => $like_value) {
            switch ($like_field) {
                case self::LIKE_NAME:
                    $queries = [];
                    foreach (explode(' ', $like_value) as $like_val) {
                        if (trim($like_val) === '')
                            continue;

                        $queries[] = sprintf(
                            ' (Users.`%s` LIKE "%s%%"  COLLATE utf8_unicode_ci OR Users.`%s` LIKE "%s%%"  COLLATE utf8_unicode_ci)  '
                            , self::LIKE_FIRSTNAME
                            , str_replace(['"', '%'], ['\"', '\%'], $like_val)
                            , self::LIKE_LASTNAME
                            , str_replace(['"', '%'], ['\"', '\%'], $like_val)
                        );
                    }

                    if (count($queries) > 0) {
                        $sql_like[] = ' (' . implode(' AND ', $queries) . ') ';
                    }
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

        if (count($sql_like) > 0) {
            $sql .= sprintf(' AND (%s) ', implode($this->like_match, $sql_like));
        }

        return $sql;
    }

    protected function generate_field_constraints($fieldName, ArrayCollection $fields)
    {
        $n = 0;
        $constraints = [];

        foreach ($fields as $field) {
            $constraints[':' . $fieldName . $n ++] = $field;
        }
        $sql = ' AND (' . $fieldName . ' = ' . implode(' OR ' . $fieldName . ' = ', array_keys($constraints)) . ') ';

        $this->sql_params = array_merge($this->sql_params, $constraints);

        return $sql;
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

            if (!isset($sorter[$k]))
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

    private function setActiveBases()
    {
        foreach ($this->app->getDataboxes() as $databox) {
            $this->active_sbas[] = $databox->get_sbas_id();
            foreach ($databox->get_collections() as $collection) {
                $this->active_bases[] = $collection->get_base_id();
            }
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    private function createSelectQuery()
    {
        $sql = 'SELECT DISTINCT Users.id ' . $this->generate_sql_constraints();

        if ('' !== $sorter = $this->generate_sort_constraint()) {
            $sql .= ' ORDER BY ' . $sorter;
        }
        return [$sql, $this->sql_params];
    }
}
