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
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Guzzle\Http\Url;

class record_preview extends record_adapter
{
    /**
     * @var string
     */
    protected $env;

    /**
     * @var int
     */
    protected $total;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed content
     */
    protected $container;

    /**
     * @var mixed content
     */
    protected $train;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var array
     */
    protected $short_history;

    /**
     * @var media_adapter
     */
    protected $view_popularity;

    /**
     * @var media_adapter
     */
    protected $refferer_popularity;

    /**
     * @var media_adapter
     */
    protected $download_popularity;

    protected $original_item;

    protected $pos;
    protected $searchEngine;
    protected $query;
    protected $options;

    public function __construct(Application $app, $env, $pos, $contId, SearchEngineInterface $search_engine = null, $query = '', SearchEngineOptions $options = null)
    {
        $number = null;
        $this->env = $env;
        $this->app = $app;
        $this->pos = $pos;
        $this->searchEngine = $search_engine;
        $this->query = $query;
        $this->options = $options;

        switch ($env) {
            case "RESULT":
                if (null === $search_engine) {
                    throw new \LogicException('Search Engine should be provided');
                }
                if (!$options) {
                    $options = new SearchEngineOptions();
                }
                $options->setFirstResult($pos);
                $options->setMaxResults(1);

                $results = $search_engine->query($query, $options);

                if ($results->getResults()->isEmpty()) {
                    throw new Exception('Record introuvable');
                }
                foreach ($results->getResults() as $record) {
                    $number = $pos;
                    $this->original_item = $record;
                    $sbas_id = $record->getDataboxId();
                    $record_id = $record->getRecordId();
                    break;
                }
                break;
            case "REG":
                $contId = explode('_', $contId);
                $sbas_id = $contId[0];
                $record_id = $contId[1];

                $this->container = new record_adapter($app, $sbas_id, $record_id);
                $this->original_item = $this->container;
                if ($pos == 0) {
                    $number = 0;
                } else {
                    $children = $this->container->getChildren();
                    foreach ($children as $child) {
                        $sbas_id = $child->getDataboxId();
                        $this->original_item = $child;
                        $record_id = $child->getRecordId();
                        if ($child->getNumber() == $pos)
                            break;
                    }
                    $number = $pos;
                    $this->total = $children->get_count();
                }

                break;
            case "BASK":
                $Basket = $app['converter.basket']->convert($contId);
                $app['acl.basket']->hasAccess($Basket, $app->getAuthenticatedUser());

                $this->container = $Basket;
                $this->total = $Basket->getElements()->count();
                $i = 0;
                $first = true;

                foreach ($Basket->getElements() as $element) {
                    $i ++;
                    if ($element->getOrd() == $pos || $first) {
                        $this->original_item = $element;
                        $sbas_id = $element->getSbasId();
                        $record_id = $element->getRecordId();
                        $this->name = $Basket->getName();
                        $number = $element->getOrd();
                        $first = false;
                    }
                }
                break;
            case "FEED":
                /** @var FeedEntry $entry */
                $entry = $app['repo.feed-entries']->find($contId);

                $this->container = $entry;
                $this->total = count($entry->getItems());
                $i = 0;
                $first = true;

                foreach ($entry->getItems() as $element) {
                    $i ++;
                    if ($element->getOrd() == $pos || $first) {
                        $sbas_id = $element->getSbasId();
                        $record_id = $element->getRecordId();
                        $this->name = $entry->getTitle();
                        $this->original_item = $element;
                        $number = $element->getOrd();
                        $first = false;
                    }
                }
                break;
            default:
                throw new InvalidArgumentException(sprintf('Expects env argument to one of (RESULT, REG, BASK, FEED) got %s', $env));
        }

        if (!(isset($sbas_id) && isset($record_id))) {
            throw new Exception('No record could be found');
        }

        parent::__construct($app, $sbas_id, $record_id, $number);
    }

    public function get_train()
    {
        if ($this->train) {
            return $this->train;
        }

        switch ($this->env) {
            case 'RESULT':
                $options = $this->options ?: new SearchEngineOptions();
                $options->setFirstResult(($this->pos - 3) < 0 ? 0 : ($this->pos - 3));
                $options->setMaxResults(56);

                $results = $this->searchEngine->query($this->query, $options);

                $this->train = $results->getResults()->toArray();
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
     * @return bool
     */
    public function is_from_result()
    {
        return $this->env == 'RESULT';
    }

    /**
     * @return bool
     */
    public function is_from_feed()
    {
        return $this->env == 'FEED';
    }

    /**
     * @return bool
     */
    public function is_from_basket()
    {
        return $this->env == 'BASK';
    }

    /**
     * @return bool
     */
    public function is_from_reg()
    {
        return $this->env == 'REG';
    }

    public function get_original_item()
    {
        return $this->original_item;
    }

    /**
     *
     * @return String
     */
    public function get_title(Array $options = [])
    {
        if ($this->title) {
            return $this->title;
        }

        $this->title = '';

        switch ($this->env) {

            case "RESULT":
                $this->title .= $this->app->trans('resultat numero %number%', ['%number%' => '<span id="current_result_n">' . ($this->getNumber() + 1) . '</span> : ']);
                $this->title .= parent::get_title($options);
                break;
            case "BASK":
                $this->title .= $this->name . ' - ' . parent::get_title($options)
                    . ' (' . $this->getNumber() . '/' . $this->total . ') ';
                break;
            case "REG":
                $title = parent::get_title($options);
                if ($this->getNumber() == 0) {
                    $this->title .= $title;
                } else {
                    $this->title .= sprintf(
                        '%s %s', $title, $this->getNumber() . '/' . $this->total
                    );
                }
                break;
            default:
                $this->title .= parent::get_title($options);
                break;
        }

        return $this->title;
    }

    /**
     * @return mixed content
     */
    public function get_container()
    {
        return $this->container;
    }

    /**
     * @return array
     */
    public function get_short_history()
    {
        if ( ! is_null($this->short_history)) {
            return $this->short_history;
        }

        $tab = [];

        $report = $this->app->getAclForUser($this->app->getAuthenticatedUser())
            ->has_right_on_base($this->getBaseId(), \ACL::CANREPORT);

        $sql = 'SELECT d.* , l.user, l.usrid as usr_id, l.site
                FROM log_docs d, log l
                WHERE d.log_id = l.id
                AND d.record_id = :record_id ';
        $params = [':record_id' => $this->getRecordId()];

        if (! $report) {
            $sql .= ' AND ((l.usrid = :usr_id AND l.site= :site) OR action="add")';
            $params[':usr_id'] = $this->app->getAuthenticatedUser()->getId();
            $params[':site'] = $this->app['conf']->get(['main', 'key']);
        }

        $sql .= 'ORDER BY d.date, usrid DESC';

        foreach ($this->getDataboxConnection()->executeQuery($sql, $params)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $hour = $this->app['date-formatter']->getPrettyString(new DateTime($row['date']));

            if ( ! isset($tab[$hour]))
                $tab[$hour] = [];

            $site = $row['site'];

            if ( ! isset($tab[$hour][$site]))
                $tab[$hour][$site] = [];

            $action = $row['action'];

            if ( ! isset($tab[$hour][$site][$action]))
                $tab[$hour][$site][$action] = [];

            if ( ! isset($tab[$hour][$site][$action][$row['usr_id']])) {
                $tab[$hour][$site][$action][$row['usr_id']] =
                    [
                        'final' => []
                        , 'comment' => []
                        , 'user' => $row['usr_id'] ? $this->app['repo.users']->find($row['usr_id']) : null
                ];
            }

            if ( ! in_array($row['final'], $tab[$hour][$site][$action][$row['usr_id']]['final'])) {
                if ($action == 'collection') {
                    $tab[$hour][$site][$action][$row['usr_id']]['final'][] = phrasea::baseFromColl($this->getDataboxId(), $row['final'], $this->app);
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
     * @return media_adapter
     */
    public function get_view_popularity()
    {
        if ( ! is_null($this->view_popularity)) {
            return $this->view_popularity;
        }

        $report = $this->app->getAclForUser($this->app->getAuthenticatedUser())
            ->has_right_on_base($this->getBaseId(), \ACL::CANREPORT);

        if ( ! $report && ! $this->app['conf']->get(['registry', 'webservices', 'google-charts-enabled'])) {
            $this->view_popularity = false;

            return $this->view_popularity;
        }

        $views = $dwnls = [];
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

        $result = $this->getDataboxConnection()
            ->executeQuery($sql, [
                ':record_id' => $this->getRecordId(),
                ':site' => $this->app['conf']->get(['main', 'key'])
            ])
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $row) {
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
        $url = Url::factory('https://chart.googleapis.com/chart?' .
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
            '&chxp=2,' . $min . ',' . $average . ',' . $max);

        $this->view_popularity = new media_adapter($width, $height, $url);

        return $this->view_popularity;
    }

    /**
     * @return media_adapter
     */
    public function get_refferer_popularity()
    {
        if ( ! is_null($this->refferer_popularity)) {
            return $this->refferer_popularity;
        }

        $report = $this->app->getAclForUser($this->app->getAuthenticatedUser())
            ->has_right_on_base($this->getBaseId(), \ACL::CANREPORT);

        if ( ! $report && ! $this->app['conf']->get(['registry', 'webservices', 'google-charts-enabled'])) {
            $this->refferer_popularity = false;

            return $this->refferer_popularity;
        }

        $sql = 'SELECT count( id ) AS views, referrer
          FROM `log_view`
          WHERE record_id = :record_id
            AND date > ( NOW( ) - INTERVAL 1 MONTH )
            GROUP BY referrer ORDER BY referrer ASC';

        $result = $this->getDataboxConnection()
            ->executeQuery($sql, [':record_id' => $this->getRecordId()])
            ->fetchAll(PDO::FETCH_ASSOC);

        $referrers = [];

        foreach ($result as $row) {
            if ($row['referrer'] == 'NO REFERRER')
                $row['referrer'] = $this->app->trans('report::acces direct');
            if ($row['referrer'] == $this->app['conf']->get('servername') . 'prod/')
                $row['referrer'] = $this->app->trans('admin::monitor: module production');
            if ($row['referrer'] == $this->app['conf']->get('servername') . 'client/')
                $row['referrer'] = $this->app->trans('admin::monitor: module client');
            if (strpos($row['referrer'], $this->app['conf']->get('servername') . 'login/') !== false)
                $row['referrer'] = $this->app->trans('report:: page d\'accueil');
            if (strpos($row['referrer'], 'http://apps.cooliris.com/') !== false)
                $row['referrer'] = $this->app->trans('report:: visualiseur cooliris');

            if (strpos($row['referrer'], $this->app['conf']->get('servername') . 'document/') !== false) {
                $row['referrer'] = $this->app->trans('report::acces direct');
            }
            if (strpos($row['referrer'], $this->app['conf']->get('servername') . 'permalink/') !== false) {
                $row['referrer'] = $this->app->trans('report::acces direct');
            }
            if ( ! isset($referrers[$row['referrer']]))
                $referrers[$row['referrer']] = 0;
            $referrers[$row['referrer']] += (int) $row['views'];
        }

        $width = 550;
        $height = 100;

        $url = Url::factory('https://chart.googleapis.com/chart?'
            . 'cht=p3&chf=bg,s,00000000&chd=t:'
            . implode(',', $referrers)
            . '&chs=' . $width . 'x' . $height
            . '&chl='
            . urlencode(implode('|', array_keys($referrers))));

        $this->refferer_popularity = new media_adapter($width, $height, $url);

        return $this->refferer_popularity;
    }

    /**
     * @return media_adapter
     */
    public function get_download_popularity()
    {
        if ( ! is_null($this->download_popularity)) {
            return $this->download_popularity;
        }

        $report = $this->app->getAclForUser($this->app->getAuthenticatedUser())
            ->has_right_on_base($this->getBaseId(), \ACL::CANREPORT);

        $ret = false;
        if ( ! $report && ! $this->app['conf']->get(['registry', 'webservices', 'google-charts-enabled'])) {
            $this->download_popularity = false;

            return $this->download_popularity;
        }

        $views = $dwnls = [];
        $top = 1;
        $day = 30;

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

        $result = $this->getDataboxConnection()
            ->executeQuery($sql, [
                ':record_id' => $this->getRecordId(),
                ':site'      => $this->app['conf']->get(['main', 'key'])
            ])
            ->fetchAll(PDO::FETCH_ASSOC);

        $top = 10;

        foreach ($result as $row) {
            if (isset($dwnls[$row['datee']])) {
                $dwnls[$row['datee']] = (int) $row['dwnl'];
                $top = max(((int) $row['dwnl'] + 10), $top);
            }
        }

        $width = 250;
        $height = 150;
        $url = Url::factory('https://chart.googleapis.com/chart?' .
            'chs=' . $width . 'x' . $height .
            '&chd=t:' . implode(',', $dwnls) .
            '&cht=lc' .
            '&chf=bg,s,00000000' .
            '&chxt=x,y' .
            '&chds=0,' . $top .
            '&chxl=0:|' . date_format(new DateTime('-30 days'), 'd M') . '|'
            . date_format(new DateTime('-15 days'), 'd M') . '|'
            . date_format(new DateTime(), 'd M') . '|1:|0|'
            . round($top / 2) . '|' . $top);

        $ret = new media_adapter($width, $height, $url);
        $this->download_popularity = $ret;

        return $this->download_popularity;
    }
}
