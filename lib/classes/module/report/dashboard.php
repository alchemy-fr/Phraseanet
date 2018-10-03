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

class module_report_dashboard implements module_report_dashboard_componentInterface
{
    /**
     * objet user
     * @var user
     */
    public $usr;

    /**
     * @la date mini du report
     * @var <string>
     */
    public $dmin;

    /**
     * @desc la date maxi du report
     * @var <string>
     */
    public $dmax;

    /**
     * @desc le tableau qui contient toutes les collections ou l'utilisateur
     * a le droit de report
     * @var <array>
     */
    public $authorizedCollection = [];

    /**
     * @desc le tableau qui contient le dashboard
     * @var <array>
     */
    public $dashboard = [];

    /**
     * @des le nombre par defaut de resultats afficher par categorie
     * @var <int>
     */
    public $nbtop = 10;

    /**
     * @desc la periode par defaut d'affichage du dashboard
     * @var <string>
     */
    public $periode = '-5 days';

    /**
     * @desc le sbasid de la base ou on effectue le report
     * @var <int>
     */
    public $sbasid;
    private $app;

    public $nb_days;

    /**
     * Construit un dashboard selon les droits du usrid, si sbas vaut null
     * c'est un report sur toutes les bases, sinon sur le sbasid
     *
     * @param Application $app
     * @param integer     $usr
     * @param integer     $sbasid
     */
    public function __construct(Application $app, $usr, $sbasid = null)
    {
        $this->app = $app;
        $this->usr = $usr;
        if (is_null($sbasid))
            $this->sbasid = 'all';
        else
            $this->sbasid = $sbasid;
        $this->initDate();
        $this->setAuthCollection();
        $this->getPlotLegendDay($this->dmin);
    }

    /**
     * @desc genere le report
     * @return dashboard_main
     */
    public function execute()
    {
        $this->setReport();
        $this->process();

        return $this;
    }

    /**
     * @desc modifier les date du report
     * @param  Datetime $dmin
     * @param  Datetime $dmax
     * @return <void>
     */
    public function setDate($dmin, $dmax)
    {
        $dmin = new Datetime($dmin);
        $dmax = new Datetime($dmax);

        $dmax->modify('+23 hours +59 minutes +59 seconds');

        $this->dmax = $dmax->format('d-m-Y');
        $this->dmin = $dmin->format('d-m-Y');

        $this->getPlotLegendDay($this->dmin, $this->dmax);

        return;
    }

    /**
     * @desc return la liste des collection authoriséee
     * @return <array>
     */
    public function authorizedCollection()
    {
        return $this->authorizedCollection;
    }

    /**
     * @desc return le titre de la date
     * @param  <string> $d vaut 'dmin' ou 'dmax'
     * @return <string>
     */
    public function getTitleDate($d)
    {
        if ($d == 'dmax') {
            $datetime = new Datetime($this->dmax);

            return $this->app['date-formatter']->getPrettyString($datetime);
        } elseif ($d == 'dmin') {
            $datetime = new Datetime($this->dmin);

            return $this->app['date-formatter']->getPrettyString($datetime);
        } else
            throw new Exception('argument must be string dmin or dmax');
    }

    /**
     * @desc check if a dashboard is valid
     * @return <bool>
     */
    public function isValid()
    {
        if (isset($this->dashboard) && sizeof($this->dashboard) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @desc return le dashboard
     * @return <array>
     */
    public function getDash()
    {
        return $this->dashboard;
    }

    /**
     * @desc construct the legend for charts
     * if dmax  === false dmax = now();
     * @param  <Datetime> $dmin
     * @param  <Datetime> $dmax
     * @return <void>
     */
    protected function getPlotLegendDay($dmin, $dmax = false)
    {
        if (! $dmax) {
            $date = new Datetime();
            $dmax = $date->format('d-m-Y');
        } else {
            $date = new Datetime($dmax);
            $dmax = $date->format('d-m-Y');
        }
        $this->legendDay[] = $this->app['date-formatter']->getPrettyString($date);

        while ($dmin != $dmax) {
            $date->modify('-1 day');
            $dmax = $date->format('d-m-Y');
            $this->legendDay[] = $this->app['date-formatter']->getPrettyString($date);
            $this->nb_days ++;
        }
        $this->legendDay = array_reverse($this->legendDay);

        return;
    }

    /**
     * @desc merge tous les resultats dans dashboard
     * @return <void>
     */
    public function process()
    {
        $tri = $this->group()->tri();
        $x = $tri->getDash();
        $a = $tri->top()->getTop($this->nbtop);
        $this->dashboard = [];
        foreach ($a as $k => $v) {
            if (array_key_exists($k, $x)) {
                $x[$k] = $v;
            }
        }
        $this->dashboard = $x;

        return;
    }

    /**
     * @desc init dmin and dmax
     * @return <void>
     */
    protected function initDate()
    {
        $datetime = new Datetime();
        $this->dmax = $datetime->format('d-m-Y');
        $datetime->modify($this->periode);
        $this->dmin = $datetime->format('d-m-Y');

        return;
    }

    /**
     * @desc construit un array avec tous les collections ou l'utilisateur
     * a le droit de reporting
     * @return <array>
     */
    public function getAllColl()
    {
        $all_coll = [];

        $base_ids = $this->app->getAclForUser($this->usr)->get_granted_base([\ACL::CANREPORT]);

        foreach ($base_ids as $base_id => $collection) {
            $databox = $collection->get_databox();
            $sbas_id = $databox->get_sbas_id();
            if ( ! isset($all_coll[$sbas_id])) {
                $all_coll[$sbas_id] = [];
                $all_coll[$sbas_id]['name_sbas'] = $databox->get_label($this->app['locale']);
            }
            $all_coll[$sbas_id]['sbas_collections'][] = [
                'base_id' => $base_id,
                'sbas_id' => $sbas_id,
                'coll_id' => $collection->get_base_id(),
                'name'    => $collection->get_label($this->app['locale'])
            ];
        }

        return $all_coll;
    }

    /**
     * @des set authorizedCollection
     * @return <void>
     */
    protected function setAuthCollection()
    {
        $all_coll = $this->getAllColl();

        foreach ($all_coll as $sbas => $info) {
            $listeColl = [];

            foreach ($info['sbas_collections'] as $key => $value) {
                $listeColl[] = (int) $value['coll_id'];
            }

            $this->authorizedCollection[(int) $sbas] = [
                'sbas_id' => (int) $sbas,
                'coll'    => implode(',', $listeColl),
                'name'    => phrasea::sbas_labels($sbas, $this->app)
            ];
        }

        return;
    }

    /**
     * La liste des base authorisee sous forme de string
     *
     * @param string $separator
     *
     * @return string
     */
    public function getListeBase($separator)
    {
        $all_coll = $this->getAllColl();
        $liste = '';
        foreach ($all_coll as $sbas => $info) {
            $liste .= phrasea::sbas_labels($sbas, $this->app) . ' ' . $separator . ' ';
        }

        return $liste;
    }

    /**
     * @desc Foreach authorized collection, fill the dashboard
     * with an object dashboard
     * @return <void>
     */
    protected function setReport()
    {
        $i = 0;
        foreach ($this->authorizedCollection as $key => $value) {
            $sbasid = $value['sbas_id'];
            $coll = $value['coll'];
            try {
                if ($this->sbasid != "all") {
                    if ($this->sbasid == $sbasid) {
                        $this->dashboard[$sbasid] = module_report_dashboard_feed::getInstance(
                                $this->app, $sbasid, $coll, $this->dmin, $this->dmax
                        );
                        break;
                    }
                } else {
                    $this->dashboard[$sbasid] = module_report_dashboard_feed::getInstance(
                            $this->app, $sbasid, $coll, $this->dmin, $this->dmax
                    );
                }
            } catch (\Exception $e) {

            }
            $i ++;
        }

        return;
    }

    /**
     * @return dashboard_group
     */
    public function group()
    {
        return new module_report_dashboard_group($this);
    }
}
