<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class record_preview extends record_adapter
{
    /**
     *
     * @var string
     */
    protected $env;

    /**
     *
     * @var int
     */
    protected $total;

    /**
     *
     * @var string
     */
    protected $name;

    /**
     *
     * @var mixed content
     */
    protected $container;

    /**
     *
     * @var mixed content
     */
    protected $train;

    /**
     *
     * @var string
     */
    protected $title;

    /**
     *
     * @var Array
     */
    protected $short_history;

    /**
     *
     * @var media
     */
    protected $view_popularity;

    /**
     *
     * @var media
     */
    protected $refferer_popularity;

    /**
     *
     * @var media
     */
    protected $download_popularity;

    /**
     *
     * @param  string         $env
     * @param  int            $pos
     * @param  mixed content  $contId
     * @param  boolean        $reload_train
     * @return record_preview
     */
    public function __construct(Application $app, $env, $pos, $contId, $reload_train = false, searchEngine_adapter $search_engine = null, $query = '')
    {
        $number = null;
        $this->env = $env;

        switch ($env) {
            case "RESULT":
                $results = $search_engine->query_per_offset($query, (int) ($pos), 1);
                $mypreview = array();

                if ($results->get_datas()->is_empty()) {
                    throw new Exception('Record introuvable');
                }
                foreach ($results->get_datas() as $record) {
                    $number = $pos;
                    $sbas_id = $record->get_sbas_id();
                    $record_id = $record->get_record_id();
                    break;
                }
                break;
            case "REG":
                $contId = explode('_', $contId);
                $sbas_id = $contId[0];
                $record_id = $contId[1];

                $this->container = new record_adapter($app, $sbas_id, $record_id);
                if ($pos == 0) {
                    $number = 0;
                    $title = _('preview:: regroupement ');
                } else {
//          $this->container = new record_adapter($sbas_id, $record_id);
                    $children = $this->container->get_children();
                    foreach ($children as $child) {
                        $sbas_id = $child->get_sbas_id();
                        $record_id = $child->get_record_id();
                        if ($child->get_number() == $pos)
                            break;
                    }
                    $number = $pos;
                    $this->total = $children->get_count();
                }

                break;
            case "BASK":
                $repository = $app['EM']->getRepository('\Entities\Basket');

                /* @var $repository \Repositories\BasketRepository */
                $Basket = $repository->findUserBasket($app, $contId, $app['phraseanet.user'], false);

                /* @var $Basket \Entities\Basket */
                $this->container = $Basket;
                $this->total = $Basket->getElements()->count();
                $i = 0;
                $first = true;

                foreach ($Basket->getElements() as $element) {
                    /* @var $element \Entities\BasketElement */
                    $i ++;
                    if ($first) {
                        $sbas_id = $element->getRecord($this->app)->get_sbas_id();
                        $record_id = $element->getRecord($this->app)->get_record_id();
                        $this->name = $Basket->getName();
                        $number = $element->getOrd();
                    }
                    $first = false;

                    if ($element->getOrd() == $pos) {
                        $sbas_id = $element->getRecord($this->app)->get_sbas_id();
                        $record_id = $element->getRecord($this->app)->get_record_id();
                        $this->name = $Basket->getName();
                        $number = $element->getOrd();
                    }
                }
                break;
            case "FEED":
                $entry = Feed_Entry_Adapter::load_from_id($app, $contId);

                $this->container = $entry;
                $this->total = count($entry->get_content());
                $i = 0;
                $first = true;

                foreach ($entry->get_content() as $element) {
                    $i ++;
                    if ($first) {
                        $sbas_id = $element->get_record()->get_sbas_id();
                        $record_id = $element->get_record()->get_record_id();
                        $this->name = $entry->get_title();
                        $number = $element->get_ord();
                    }
                    $first = false;

                    if ($element->get_ord() == $pos) {
                        $sbas_id = $element->get_record()->get_sbas_id();
                        $record_id = $element->get_record()->get_record_id();
                        $this->name = $entry->get_title();
                        $number = $element->get_ord();
                    }
                }
                break;
        }
        parent::__construct($app, $sbas_id, $record_id, $number);

        return $this;
    }

    public function get_train($pos = 0, $query = '', searchEngine_adapter $search_engine = null)
    {
        if ($this->train) {
            return $this->train;
        }

        switch ($this->env) {
            case 'RESULT':
                $perPage = 56;
                $index = ($pos - 3) < 0 ? 0 : ($pos - 3);
                $page = (int) ceil($pos / $perPage);
                $results = $search_engine->query_per_offset($query, $index, $perPage);

                $this->train = $results->get_datas();
                break;
            case 'BASK':
                $this->train = $this->container->getElements();
                break;
            case 'REG':
                $this->train = $this->container->get_children();
                break;
        }

        return $this->train;
    }

    /**
     *
     * @return boolean
     */
    public function is_from_result()
    {
        return $this->env == 'RESULT';
    }

    public function is_from_feed()
    {
        return $this->env == 'FEED';
    }

    /**
     *
     * @return boolean
     */
    public function is_from_basket()
    {
        return $this->env == 'BASK';
    }

    /**
     *
     * @return boolean
     */
    public function is_from_reg()
    {
        return $this->env == 'REG';
    }

    /**
     *
     * @return String
     */
    public function get_title($highlight = '', searchEngine_adapter $search_engine = null)
    {
        if ($this->title) {
            return $this->title;
        }

        $this->title = collection::getLogo($this->get_base_id(), $this->app) . ' ';

        switch ($this->env) {

            case "RESULT":
                $this->title .= sprintf(
                    _('preview:: resultat numero %s '), '<span id="current_result_n">' . ($this->number + 1)
                    . '</span> : '
                );

                $this->title .= parent::get_title($highlight, $search_engine);
                break;
            case "BASK":
                $this->title .= $this->name . ' - ' . parent::get_title($highlight, $search_engine)
                    . ' (' . $this->get_number() . '/' . $this->total . ') ';
                break;
            case "REG":
                $title = parent::get_title();
                if ($this->get_number() == 0) {
                    $this->title .= $title;
                } else {
                    $this->title .= sprintf(
                        '%s %s', $title, $this->get_number() . '/' . $this->total
                    );
                }
                break;
            default:
                $this->title .= parent::get_title($highlight, $search_engine);
                break;
        }

        return $this->title;
    }

    /**
     *
     * @return mixed content
     */
    public function get_container()
    {
        return $this->container;
    }

    /**
     *
     * @return Array
     */
    public function get_short_history()
    {
        if ( ! is_null($this->short_history)) {
            return $this->short_history;
        }

        $tab = array();

        $report = $this->app['phraseanet.user']->ACL()->has_right_on_base($this->get_base_id(), 'canreport');

        $connsbas = connection::getPDOConnection($this->app, $this->get_sbas_id());

        $sql = 'SELECT d . * , l.user, l.usrid as usr_id, l.site
                FROM log_docs d, log l
                WHERE d.log_id = l.id
                AND d.record_id = :record_id ';
        $params = array(':record_id' => $this->get_record_id());

        if ( ! $report) {
            $sql .= ' AND ((l.usrid = :usr_id AND l.site= :site) OR action="add")';
            $params[':usr_id'] = $this->app['phraseanet.user']->get_id();
            $params[':site'] = $this->app['phraseanet.registry']->get('GV_sit');
        }

        $sql .= 'ORDER BY d.date, usrid DESC';

        $stmt = $connsbas->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $hour = $this->app['date-formatter']->getPrettyString(new DateTime($row['date']));

            if ( ! isset($tab[$hour]))
                $tab[$hour] = array();

            $site = $row['site'];

            if ( ! isset($tab[$hour][$site]))
                $tab[$hour][$site] = array();

            $action = $row['action'];

            if ( ! isset($tab[$hour][$site][$action]))
                $tab[$hour][$site][$action] = array();

            if ( ! isset($tab[$hour][$site][$action][$row['usr_id']])) {
                $user = null;

                try {
                    $user = \User_Adapter::getInstance($row['usr_id'], $this->app);
                } catch (Exception $e) {

                }

                $tab[$hour][$site][$action][$row['usr_id']] =
                    array(
                        'final' => array()
                        , 'comment' => array()
                        , 'user' => $user
                );
            }

            if ( ! in_array($row['final'], $tab[$hour][$site][$action][$row['usr_id']]['final'])) {
                if($action == 'collection') {
                    $tab[$hour][$site][$action][$row['usr_id']]['final'][] = phrasea::baseFromColl($this->get_sbas_id(), $row['final'], $this->app);
                } else {
                    $tab[$hour][$site][$action][$row['usr_id']]['final'][] = $row['final'];
                }
            }
            if ( ! in_array($row['comment'], $tab[$hour][$site][$action][$row['usr_id']]['comment']))
                $tab[$hour][$site][$action][$row['usr_id']]['comment'][] =
                    $row['comment'];
        }

        $this->short_history = array_reverse($tab);

        return $this->short_history;
    }

    /**
     *
     * @return media_image
     */
    public function get_view_popularity()
    {
        if ( ! is_null($this->view_popularity)) {
            return $this->view_popularity;
        }

        $report = $this->app['phraseanet.user']->ACL()->has_right_on_base(
            $this->get_base_id(), 'canreport');

        if ( ! $report && ! $this->app['phraseanet.registry']->get('GV_google_api')) {
            $this->view_popularity = false;

            return $this->view_popularity;
        }

        $views = $dwnls = array();
        $top = 1;
        $day = 30;
        $min = 0;
        $average = 0;

        while ($day >= 0) {

            $datetime = new DateTime('-' . $day . ' days');
            $date = date_format($datetime, 'Y-m-d');
            $views[$date] = $dwnls[$date] = 0;
            $day --;
        }

        $sql = 'SELECT count(id) as views, DATE(date) as datee
          FROM `log_view`
          WHERE record_id = :record_id
          AND date > (NOW() - INTERVAL 1 MONTH)
          AND site_id = :site
          GROUP BY datee ORDER BY datee ASC';

        $connsbas = connection::getPDOConnection($this->app, $this->get_sbas_id());
        $stmt = $connsbas->prepare($sql);
        $stmt->execute(
            array(
                ':record_id' => $this->get_record_id(),
                ':site'      => $this->app['phraseanet.registry']->get('GV_sit')
            )
        );
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            if (isset($views[$row['datee']])) {
                $views[$row['datee']] = (int) $row['views'];
                $top = max((int) $row['views'], $top);
                $min = isset($min) ? min($row['views'], $min) : $row['views'];
                $average += $row['views'];
            }
        }

        $topScale = round($top * 1.2);

        $average = $average / 30;
        $max = round(($top) * 100 / ($topScale));
        $min = round($min * 100 / ($topScale));
        $average = round($average * 100 / ($topScale));

        $width = 350;
        $height = 150;
        $url = 'http://chart.apis.google.com/chart?' .
            'chs=' . $width . 'x' . $height .
            '&chd=t:' . implode(',', $views) .
            '&cht=lc' .
            '&chf=bg,s,00000000' .
            '&chxt=x,y,r' .
            '&chds=0,' . $topScale .
            '&chls=2.0&chxtc=2,-350' .
            '&chxl=0:|' . date_format(new DateTime('-30 days'), 'd M') . '|'
            . date_format(new DateTime('-15 days'), 'd M') . '|'
            . date_format(new DateTime(), 'd M') . '|1:|0|'
            . round($top / 2, 2) . '|' . $top
            . '|2:|min|average|max' .
            '&chxp=2,' . $min . ',' . $average . ',' . $max;

        $this->view_popularity = new media_adapter($url, $width, $height);

        return $this->view_popularity;
    }

    /**
     *
     * @return media
     */
    public function get_refferer_popularity()
    {
        if ( ! is_null($this->refferer_popularity)) {
            return $this->refferer_popularity;
        }

        $report = $this->app['phraseanet.user']->ACL()->has_right_on_base(
            $this->get_base_id(), 'canreport');

        if ( ! $report && ! $this->app['phraseanet.registry']->get('GV_google_api')) {
            $this->refferer_popularity = false;

            return $this->refferer_popularity;
        }

        $connsbas = connection::getPDOConnection($this->app, $this->get_sbas_id());

        $sql = 'SELECT count( id ) AS views, referrer
          FROM `log_view`
          WHERE record_id = :record_id
            AND date > ( NOW( ) - INTERVAL 1 MONTH )
            GROUP BY referrer ORDER BY referrer ASC';

        $stmt = $connsbas->prepare($sql);
        $stmt->execute(array(':record_id' => $this->get_record_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $referrers = array();

        foreach ($rs as $row) {
            if ($row['referrer'] == 'NO REFERRER')
                $row['referrer'] = _('report::acces direct');
            if ($row['referrer'] == $this->app['phraseanet.registry']->get('GV_ServerName') . 'prod/')
                $row['referrer'] = _('admin::monitor: module production');
            if ($row['referrer'] == $this->app['phraseanet.registry']->get('GV_ServerName') . 'client/')
                $row['referrer'] = _('admin::monitor: module client');
            if (strpos($row['referrer'], $this->app['phraseanet.registry']->get('GV_ServerName') . 'login/') !== false)
                $row['referrer'] = _('report:: page d\'accueil');
            if (strpos($row['referrer'], 'http://apps.cooliris.com/') !== false)
                $row['referrer'] = _('report:: visualiseur cooliris');

            if (strpos($row['referrer'], $this->app['phraseanet.registry']->get('GV_ServerName') . 'document/') !== false) {
                if (strpos($row['referrer'], '/view/') !== false)
                    $row['referrer'] = _('report::presentation page preview');
                else
                    $row['referrer'] = _('report::acces direct');
            }
            if (strpos($row['referrer'], $this->app['phraseanet.registry']->get('GV_ServerName') . 'permalink/') !== false) {
                if (strpos($row['referrer'], '/view/') !== false)
                    $row['referrer'] = _('report::presentation page preview');
                else
                    $row['referrer'] = _('report::acces direct');
            }
            if ( ! isset($referrers[$row['referrer']]))
                $referrers[$row['referrer']] = 0;
            $referrers[$row['referrer']] += (int) $row['views'];
        }

        $width = 550;
        $height = 100;

        $url = 'http://chart.apis.google.com/chart?'
            . 'cht=p3&chf=bg,s,00000000&chd=t:'
            . implode(',', $referrers)
            . '&chs=' . $width . 'x' . $height
            . '&chl='
            . urlencode(implode('|', array_keys($referrers))) . '';

        $this->refferer_popularity = new media_adapter($url, $width, $height);

        return $this->refferer_popularity;
    }

    /**
     *
     * @return media
     */
    public function get_download_popularity()
    {
        if ( ! is_null($this->download_popularity)) {
            return $this->download_popularity;
        }

        $report = $this->app['phraseanet.user']->ACL()->has_right_on_base($this->get_base_id(), 'canreport');

        $ret = false;
        if ( ! $report && ! $this->app['phraseanet.registry']->get('GV_google_api')) {
            $this->download_popularity = false;

            return $this->download_popularity;
        }

        $views = $dwnls = array();
        $top = 1;
        $day = 30;
        $min = 0;
        $average = 0;

        while ($day >= 0) {

            $datetime = new DateTime('-' . $day . ' days');
            $date = date_format($datetime, 'Y-m-d');
            $views[$date] = $dwnls[$date] = 0;
            $day --;
        }

        $sql = 'SELECT count(d.id) as dwnl, DATE(d.date) as datee
        FROM `log_docs` d, log l
        WHERE action="download"
        AND log_id=l.id
        AND record_id= :record_id
        AND d.date > (NOW() - INTERVAL 1 MONTH)
        AND site= :site
        GROUP BY datee ORDER BY datee ASC';

        $connsbas = connection::getPDOConnection($this->app, $this->get_sbas_id());
        $stmt = $connsbas->prepare($sql);
        $stmt->execute(
            array(
                ':record_id' => $this->get_record_id(),
                ':site'      => $this->app['phraseanet.registry']->get('GV_sit')
            )
        );
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $top = 10;

        foreach ($rs as $row) {
            if (isset($dwnls[$row['datee']])) {
                $dwnls[$row['datee']] = (int) $row['dwnl'];
                $top = max(((int) $row['dwnl'] + 10), $top);
            }
        }

        $width = 250;
        $height = 150;
        $url = 'http://chart.apis.google.com/chart?' .
            'chs=' . $width . 'x' . $height .
            '&chd=t:' . implode(',', $dwnls) .
            '&cht=lc' .
            '&chf=bg,s,00000000' .
            '&chxt=x,y' .
            '&chds=0,' . $top .
            '&chxl=0:|' . date_format(new DateTime('-30 days'), 'd M') . '|'
            . date_format(new DateTime('-15 days'), 'd M') . '|'
            . date_format(new DateTime(), 'd M') . '|1:|0|'
            . round($top / 2) . '|' . $top . '';

        $ret = new media_adapter($url, $width, $height);
        $this->download_popularity = $ret;

        return $this->download_popularity;
    }
}
