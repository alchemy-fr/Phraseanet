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
use Doctrine\DBAL\DBALException;

class module_report
{
    /**
     * Start date of the report
     * @var string - timestamp
     */
    protected $dmin;

    /**
     * End date of the report
     * @var string - timestamp
     */
    protected $dmax;

    /**
     * Id of the base we want to connect
     * @var int
     */
    protected $sbas_id;

    /**
     * Id of the current app's box user
     * @var int
     */
    protected $user_id;

    /**
     * The result of the report
     * @var array
     */
    public $report = [];

    /**
     * The title of the report
     * @var string
     */
    protected $title = '';

    /**
     * default displayed value in the formated tab
     * @var array
     */
    protected $display = [];

    /**
     * ?
     * @var <array>
     */
    protected $default_display = [];

    /**
     * Contain all the field from the sql request
     * @var array
     */
    protected $champ = [];

    /**
     * result of the report
     * @var array
     */
    protected $result = [];

    /**
     * The id of all collections from a databox
     * @var string
     */
    protected $list_coll_id = '';

    /**
     * The number of record displayed by page if enable limit is false
     * @var int
     */
    protected $nb_record = 30;

    /**
     * The current number of the page where are displaying the results
     * @var int
     */
    protected $nb_page = 1;

    /**
     * check if there is a previous page
     * @var <bool>
     */
    protected $previous_page = false;

    /**
     * check if there is a next page
     * @var <bool>
     */
    protected $next_page = false;

    /**
     *
     * @var int total of result
     */
    protected $total = 0;

    /**
     * the request executed
     */
    protected $req = '';

    /**
     * the request executed
     */
    protected $params = [];

    /**
     * do we display  next and previous button
     * @var bool
     */
    protected $display_nav = false;

    /**
     * do we display the configuration button
     * @var <bool>
     */
    protected $config = true;

    /**
     * gettext tags for days
     * @var <array>
     */
    protected $jour;

    /**
     * gettext tags for month
     * @var <array>
     */
    protected $month;

    /**
     * The name of the database
     * @var string
     */
    protected $dbname;

    /**
     * The periode displayed in a string of the report
     * @var string
     */
    protected $periode;

    /**
     * filter executed on report choose by the user
     * @var array;
     */
    protected $tab_filter = [];

    /**
     * column displayed in the report choose by the user
     * @var <array>
     */
    protected $active_column = [];

    /**
     * array that contains the string displayed
     * foreach filters
     * @var <array>
     */
    protected $posting_filter = [];

    /**
     * The ORDER BY filters of the query
     * by default is empty
     * @var array
     */
    protected $tab_order = [];

    /**
     * define columns that are boundable
     * @var <array>
     */
    protected $bound = [];

    /**
     * do we display print button
     * @var <bool>
     */
    protected $print = true;

    /**
     * do we display csv button
     * @var <bool>
     */
    protected $csv = true;

    /**
     * do we enable limit filter for the report
     * @var bool
     */
    protected $enable_limit = true;

    /**
     * gettext correspondance for all available columns in report
     * @var array
     */
    protected $cor = [];

    /**
     * group result of a report this is the name ogf the grouped column
     * @var string
     */
    protected $groupby;

    /**
     * disbale or enable pretty string useful for export in csv
     * @var boolean
     */
    protected $pretty_string = true;

    /**
     *
     * @var Application
     */
    protected $app;

    /**
     *
     */
    protected $cor_query = [];
    protected $dateField = 'log.date';
    protected $isInformative;

    /**
     * Constructor
     *
     * @param Application $app
     * @param string      $d1      the minimal date of the report
     * @param string      $d2      the maximal date of the report
     * @param integer     $sbas_id the id of the base where we want to connect
     * @param string      $collist
     */
    public function __construct(Application $app, $d1, $d2, $sbas_id, $collist)
    {
        $this->app = $app;
        $this->dmin = $d1;
        $this->dmax = $d2;
        $this->sbas_id = $sbas_id;
        $this->list_coll_id = $collist;
        $this->user_id = $this->app->getAuthenticatedUser()->getId();
        $this->periode = sprintf(
            '%s - %s ',
            $this->app['date-formatter']->getPrettyString(new \DateTime($d1)),
            $this->app['date-formatter']->getPrettyString(new \DateTime($d2))
        );
        $this->dbname = phrasea::sbas_labels($sbas_id, $app);
        $this->cor = $this->setCor();
        $this->jour = $this->setDay();
        $this->month = $this->setMonth();
    }

    public function IsInformative()
    {
        return $this->isInformative;
    }

    public function setIsInformative($isInformative)
    {
        $this->isInformative = $isInformative;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setUser_id($user_id)
    {
        $this->user_id = $user_id;
    }

    public function getSbas_id()
    {
        return $this->sbas_id;
    }

    public function setSbas_id($sbas_id)
    {
        $this->sbas_id = $sbas_id;
    }

    public function setPrettyString($bool)
    {
        $this->pretty_string = $bool;
    }

    public function getPrettyString()
    {
        return $this->pretty_string;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function setCsv($bool)
    {
        $this->csv = $bool;

        return $this;
    }

    public function getCsv()
    {
        return $this->csv;
    }

    public function setFilter(array $filter)
    {
        $this->tab_filter = $filter;

        return $this;
    }

    public function setPeriode($periode)
    {
        $this->periode = $periode;

        return $this;
    }

    public function setRequest($sql)
    {
        $this->req = $sql;
    }

    public function getPeriode()
    {
        return $this->periode;
    }

    public function setpostingFilter($filter)
    {
        $this->posting_filter = $filter;

        return $this;
    }

    public function getPostingFilter()
    {
        return $this->posting_filter;
    }

    public function setLimit($page, $limit)
    {
        $this->nb_page = $page;
        $this->nb_record = $limit;

        return $this;
    }

    public function getGroupBy()
    {
        return $this->groupby;
    }

    public function setGroupBy($groupby)
    {
        $this->groupby = $groupby;

        return $this;
    }

    public function setActiveColumn(array $active_column)
    {
        $this->active_column = $active_column;

        return $this;
    }

    public function getActiveColumn()
    {
        return $this->active_column;
    }

    public function setConfig($bool)
    {
        $this->config = $bool;

        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setPrint($bool)
    {
        $this->print = $bool;

        return $this;
    }

    public function getPrint()
    {
        return $this->print;
    }

    public function setHasLimit($bool)
    {
        $this->enable_limit = $bool;

        return $this;
    }

    public function getHasLimit()
    {
        return $this->enable_limit;
    }

    public function setBound($column, $bool)
    {
        if ($bool) {
            $this->bound[$column] = 1;
        } else {
            $this->bound[$column] = 0;
        }

        return $this;
    }

    public function getBound()
    {
        return $this->bound;
    }

    public function setOrder($champ, $order)
    {
        $this->tab_order['champ'] = $champ;
        $this->tab_order['order'] = $order;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDisplay()
    {
        return $this->display;
    }

    public function getEnableLimit()
    {
        return $this->enable_limit;
    }

    public function getSbasId()
    {
        return $this->sbas_id;
    }

    public function getDmin()
    {
        return $this->dmin;
    }

    public function getDmax()
    {
        return $this->dmax;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setResult(array $rs)
    {
        $this->result = $rs;

        return $this;
    }

    public function getOrder($k = false)
    {
        if ($k === false) {
            return $this->tab_order;
        }

        return $this->tab_order[$k];
    }

    public function getNbPage()
    {
        return $this->nb_page;
    }

    public function getNbRecord()
    {
        return $this->nb_record;
    }

    public function getTabFilter()
    {
        return $this->tab_filter;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getListCollId()
    {
        return $this->list_coll_id;
    }

    public function getReq()
    {
        return $this->req;
    }

    public function getTransQueryString()
    {
        return $this->cor_query;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function setTotal($total)
    {
        $this->total = $total;
    }

    public function getDefault_display()
    {
        return $this->default_display;
    }

    public function setDefault_display($default_display)
    {
        $this->default_display = $default_display;
    }

    public function getChamps()
    {
        return $this->champ;
    }

    /**
     * Retourne un objet qui genere la requete selon le type de report
     * @param  string                     $domain
     * @return module_report_sqlconnexion
     */
    public function sqlBuilder($domain)
    {
        switch ($domain) {
            case 'connexion' :
                return new module_report_sqlconnexion($this->app, $this);
                break;
            case 'download' :
  // no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);
                return new module_report_sqldownload($this->app, $this);
                break;
            case 'question' :
                return new module_report_sqlquestion($this->app, $this);
                break;
            case 'action' :
                return new module_report_sqlaction($this->app, $this);
                break;
            default:
                return $this->req;
                break;
        }
    }

    protected function setChamp($rs)
    {
        $row = array_shift($rs);

        $this->champ = is_array($row) ? array_keys($row) : [];
        $this->default_display = is_array($row) ? array_keys($row) : [];

        return;
    }

    /**
     * initialise les configuration des columns ex : boundable, linkable orderable
     *  etc .. par defaut ou celle passe en parametre par $tab
     * @param  array  $tab
     * @param  string $groupby
     * @return void
     */
    protected function setDisplay($tab, $groupby = false)
    {
        if ($tab == false && $groupby == false)
            $this->initDefaultConfigColumn($this->default_display);
        elseif ($groupby != false && $tab == false)
            $this->initDefaultConfigColumn($this->champ);
        elseif ($tab != false)
            $this->setConfigColumn($tab);

        return;
    }

    /**
     *
     */
    protected function setReport()
    {
        $this->report['dbid'] = $this->sbas_id;
        $this->report['periode'] = $this->periode;
        $this->report['dbname'] = $this->dbname;
        $this->report['dmin'] = $this->dmin;
        $this->report['dmax'] = $this->dmax;
        $this->report['server'] = $this->app['conf']->get('servername');
        $this->report['filter'] = $this->tab_filter;
        $this->report['posting_filter'] = $this->posting_filter;
        $this->report['active_column'] = $this->active_column;
        $this->report['default_display'] = $this->default_display;
        $this->report['config'] = $this->config;
        $this->report['total'] = $this->total;
        $this->report['display_nav'] = $this->display_nav;
        $this->report['nb_record'] = $this->nb_record;
        $this->report['page'] = $this->nb_page;
        $this->report['previous_page'] = $this->previous_page;
        $this->report['next_page'] = $this->next_page;
        $this->report['title'] = $this->title;
        $this->report['allChamps'] = $this->champ;
        $this->report['display'] = $this->display;
        $this->report['result'] = $this->result;
        $this->report['print'] = $this->print;
        $this->report['csv'] = $this->csv;
    }

    /**
     *   etablis les correspondance entre gettext et le nom des columns du report
     * @return array
     */
    private function setCor()
    {
        return [
            'user'           => $this->app->trans('report:: utilisateur'),
            'coll_id'        => $this->app->trans('report:: collections'),
            'connexion'      => $this->app->trans('report:: Connexion'),
            'comment'        => $this->app->trans('report:: commentaire'),
            'search'         => $this->app->trans('report:: question'),
            'date'           => $this->app->trans('report:: date'),
            'ddate'          => $this->app->trans('report:: date'),
            'fonction'       => $this->app->trans('report:: fonction'),
            'activite'       => $this->app->trans('report:: activite'),
            'pays'           => $this->app->trans('report:: pays'),
            'societe'        => $this->app->trans('report:: societe'),
            'nb'             => $this->app->trans('report:: nombre'),
            'pourcent'       => $this->app->trans('report:: pourcentage'),
            'telechargement' => $this->app->trans('report:: telechargement'),
            'record_id'      => $this->app->trans('report:: record id'),
            'final'          => $this->app->trans('report:: type d\'action'),
//      'xml'            => $this->app->trans('report:: sujet'),
            'file'           => $this->app->trans('report:: fichier'),
            'mime'           => $this->app->trans('report:: type'),
            'size'           => $this->app->trans('report:: taille'),
            'copyright'      => $this->app->trans('report:: copyright'),
            'final'          => $this->app->trans('phraseanet:: sous definition')
        ];

        return;
    }

    /**
     *   etablis les correspondance entre gettext et le nom des jours du report
     * @return array
     */
    private function setDay()
    {
        return [
            1 => $this->app->trans('phraseanet::jours:: lundi'),
            2 => $this->app->trans('phraseanet::jours:: mardi'),
            3 => $this->app->trans('phraseanet::jours:: mercredi'),
            4 => $this->app->trans('phraseanet::jours:: jeudi'),
            5 => $this->app->trans('phraseanet::jours:: vendredi'),
            6 => $this->app->trans('phraseanet::jours:: samedi'),
            7 => $this->app->trans('phraseanet::jours:: dimanche')];
    }

    /**
     *   etablis les correspondance entre gettext et le nom des mois du report
     * @return array
     */
    private function setMonth()
    {
        return [
            $this->app->trans('janvier'),
            $this->app->trans('fevrier'),
            $this->app->trans('mars'),
            $this->app->trans('avril'),
            $this->app->trans('mai'),
            $this->app->trans('juin'),
            $this->app->trans('juillet'),
            $this->app->trans('aout'),
            $this->app->trans('septembre'),
            $this->app->trans('octobre'),
            $this->app->trans('novembre'),
            $this->app->trans('decembre')
        ];
    }

    /**
     * effectue le mechanism de pagination
     * @param resultset $rs
     */
    protected function calculatePages()
    {
        if ($this->nb_record && $this->total > $this->nb_record) {
            $this->previous_page = $this->nb_page - 1;
            if ($this->previous_page == 0)
                $this->previous_page = false;

            $test = ($this->total / $this->nb_record);
            if ($this->nb_page == intval(ceil($test)))
                $this->next_page = false;
            else
                $this->next_page = $this->nb_page + 1;
        }
    }

    /**
     * on ne montre le formulaire que si il y'a plus de resultat que de nombre
     * de record que l'on veut afficher
     */
    protected function setDisplayNav()
    {
        if ($this->total > $this->nb_record)
            $this->display_nav = true;
    }

    /**
     * @desc Initialize the configuration foreach column displayed in the report
     * @param  array $display contain the conf's variables
     * @return void
     */
    protected function initDefaultConfigColumn($display)
    {
        $array = [];
        foreach ($display as $key => $value)
            $array[$value] = ["", 0, 0, 0, 0];
        $this->setConfigColumn($array);
    }

    /**
     * @desc Set your own configuration for each column displayed in the html table
     * @param  array $tab contain your conf's variables
     *                    array( 'field' =>
     *                    array('title of the colum', '1 = order ON / 0 = order OFF',
     *                    '1 = bound ON / 0 = bound OFF')
     * @example $tab = array('user' => array('user list', 1, 0),
     *                    'id'   => array(user id, 0, 0)); etc ..
     * @return void
     */
    protected function setConfigColumn($tab)
    {
        foreach ($tab as $column => $row) {
            foreach ($row as $ind => $value) {
                $title_text = "";
                if (array_key_exists($column, $this->cor)) {
                    $title_text = $this->cor[$column];
                }

                $title = empty($row[0]) ? "" : $row[0];

                $sort = $row[1];
                $bound = array_key_exists($column, $this->bound) ? $this->bound[$column] : $row[2];
                $filter = (isset($row[3]) ? $row[3] : 0);
                $groupby = $row[4];
                $config = [
                    'title'          => empty($title) ? (empty($title_text) ? $column : $title_text) : $title,
                    'sort'           => $sort,
                    'bound'          => $bound,
                    'filter'         => $filter,
                    'groupby'        => $groupby
                ];

                $this->display[$column] = $config;
            }
        }
    }

    public function setDateField($dateField)
    {
        $this->dateField = $dateField;

        return $this;
    }

    public function getDateField()
    {
        return $this->dateField;
    }

    /**
     * Build the final formated array which contains all the result,
     * we construct the html code from this array
     *
     * @param array $tab     pass the configcolumn parameter to this tab
     * @param array $groupby
     * @param array $on
     *
     * @return the formated array
     */
    public function buildReport($tab = false, $groupby = false, $on = false)
    {
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        if (sizeof($this->report) > 0) {
            return $this->report;
        }

        $databox = $this->app->findDataboxById($this->sbas_id);
        $conn = $databox->get_connection();

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        $this->buildReq($groupby, $on);

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\nreq=%s\n\n", __FILE__, __LINE__, $this->req), FILE_APPEND);

        try {
            try {
                $stmt = $conn->prepare($this->req);
                $stmt->execute($this->params);
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
            } catch (DBALException $e) {

                return;
            }

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s) %s\n\n", __FILE__, __LINE__, get_class($this)), FILE_APPEND);

            //set request field
            $this->setChamp($rs);
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);
            //set display
            $this->setDisplay($tab, $groupby);
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);
            //construct results
            $this->buildResult($this->app, $rs);
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);
            //calculate prev and next page
            $this->calculatePages();
            //do we display navigator ?
            $this->setDisplayNav();
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);
            //assign all variables
            $this->setReport();

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

            return $this->report;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public static function getPreff(Application $app, $sbasid)
    {
        $tab = [];

        $databox = $app->findDataboxById((int) $sbasid);

        foreach ($databox->get_meta_structure() as $databox_field) {
            /* @var $databox_field \databox_field */

            if ($databox_field->is_report()) {
                $tab[] = $databox_field->get_name();
            }
        }

        return $tab;
    }

    public static function getHost($url)
    {
        $parse_url = parse_url(trim($url));
        $paths = explode('/', $parse_url['path'], 2);
        $result = isset($parse_url['host']) ? $parse_url['host'] : array_shift($paths);

        return trim($result);
    }

    /**
     * Return filtered data
     *
     * @param string $field
     * @param bool $on
     * @return array
     */
    public function colFilter($field, $on = false)
    {
        throw new RuntimeException('colFilter was called on a module not handling the called');
    }
}
