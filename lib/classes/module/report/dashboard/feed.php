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

class module_report_dashboard_feed implements module_report_dashboard_componentInterface
{
    /**
     * @desc la date mini des resultats
     * @var <string>
     */
    public $dmin;

    /**
     * @desc la date maxi des resultats
     * @var <string>
     */
    public $dmax;

    /**
     * @desc version sql de la date mini
     * @var <string>
     */
    public $dminsql;

    /**
     * @desc version sql de la date maxi
     * @var <string>
     */
    public $dmaxsql;

    /**
     * @desc le sbasid de la base sur lequel on effectue les requetes
     * @var <int>
     */
    public $sbasid;

    /**
     * @la liste des collections séparés par une virgule
     * sur laquelle on effectue les requetes
     * @var <string>
     */
    public $collection;

    /**
     * @desc le tableau qui contien les resultats
     * @var <array>
     */
    public $report = [];
    private $app;

    /**
     * Returns l'objet stockee dans le cache si i l existe sinon instancie
     * un nouveau objet dashboard_feed
     *
     * @param Application $app
     * @param integer     $sbasid
     * @param string      $sbas_coll
     * @param mixed       $dmin
     * @param mixed       $dmax
     */
    public static function getInstance(Application $app, $sbasid, $sbas_coll, $dmin, $dmax)
    {
        $cache_id = 'feed_' . md5($sbasid . '_' . $sbas_coll . '_' . $dmin . '_' . $dmax);

        try {
            $result = $app->getApplicationBox()->get_data_from_cache($cache_id);
            $result->setApplication($app);

            return $result;
        } catch (\Exception $e) {

        }
        $tmp = new self($app, $sbasid, $sbas_coll, $dmin, $dmax);

        $app->getApplicationBox()->set_data_to_cache($tmp, $cache_id);

        return $tmp;
    }

    /**
     * Remplis les resultats bruts pour valeures passees en param
     *
     * @param Application $app
     * @param integer     $sbasid
     * @param string      $sbas_collection les collection sous forme de string séparés par une virgule
     * @param string      $dmin            Y-m-d
     * @param string      $dmax            Y-m-d
     */
    public function __construct(Application $app, $sbasid, $sbas_collection, $dmin, $dmax)
    {
        $this->app = $app;
        $this->dmin = $dmin;
        $this->dmax = $dmax;
        $this->dminsql = $this->dateToSqlDate('dmin');
        $this->dmaxsql = $this->dateToSqlDate('dmax');
        $this->sbasid = $sbasid;
        $this->collection = $sbas_collection;
        $this->process();
    }

    /**
     * @desc return les date dormate pour les requetes sql;
     * @param  <string> $d, vaut 'dmin' ou 'dmax'
     * @return Datetime
     */
    private function dateToSqlDate($d)
    {
        if ($d == 'dmax') {
            $datetime = new Datetime($this->dmax);

            return $this->app['date-formatter']->format_mysql($datetime);
        } elseif ($d == 'dmin') {
            $datetime = new Datetime($this->dmin);

            return $this->app['date-formatter']->format_mysql($datetime);
        }
    }

    /**
     * @desc fill the dash results for the current sbas
     * @return <void>
     */
    public function process()
    {
        try {
            //Get number of DLs
            $this->report['nb_dl'] = module_report_download::getNbDl(
                    $this->app, $this->dminsql, $this->dmaxsql, $this->sbasid, $this->collection
            );

            //Get Number of connexions
            $this->report['nb_conn'] = module_report_connexion::getNbConn(
                    $this->app, $this->dminsql, $this->dmaxsql, $this->sbasid, $this->collection
            );
            if ($this->app['conf']->get(['registry', 'modules', 'anonymous-report']) == false) {
                /**
                 * get Top ten user of
                 * number of dl doc, prev
                 * number of weight dl by doc, prev
                 */
                $top = module_report_activity::topTenUser(
                        $this->app, $this->dminsql, $this->dmaxsql, $this->sbasid, $this->collection
                );

                $this->report['top_ten_user_doc'] = $top['top_ten_doc'];
                $this->report['top_ten_user_prev'] = $top['top_ten_prev'];
                $this->report['top_ten_user_poiddoc'] = $top['top_ten_poiddoc'];
                $this->report['top_ten_user_poidprev'] = $top['top_ten_poidprev'];
            }

            /**
             *  get avtivity by hour
             */
            $this->report['activity'] = module_report_activity::activity(
                    $this->app, $this->dminsql, $this->dmaxsql, $this->sbasid, $this->collection
            );

            // get activty by day
            $this->report['activity_day'] = module_report_activity::activityDay(
                    $this->app, $this->dminsql, $this->dmaxsql, $this->sbasid, $this->collection
            );
            // get Most document and preview DL
            $topdl = module_report_download::getTopDl(
                    $this->app, $this->dminsql, $this->dmaxsql, $this->sbasid, $this->collection
            );

            $this->report['top_dl_preview'] = $topdl['preview'];
            $this->report['top_dl_document'] = $topdl['document'];

            if ($this->app['conf']->get(['registry', 'modules', 'anonymous-report']) == false) {
                // get users that ask the most questions
                $this->report['ask'] = module_report_activity::activityQuestion(
                        $this->app, $this->dminsql, $this->dmaxsql, $this->sbasid, $this->collection
                );
            }
            //get the refferer
            $this->report['top_ten_site'] = module_report_activity::activiteTopTenSiteView(
                    $this->app, $this->dminsql, $this->dmaxsql, $this->sbasid, $this->collection
            );
            //Get the most asked questions
            $this->report['top_ten_question'] = module_report_activity::activiteTopQuestion(
                    $this->app, $this->dminsql, $this->dmaxsql, $this->sbasid, $this->collection
            );

            //get the  number of added docuùments
            $this->report['activity_added'] = module_report_activity::activiteAddedDocument(
                    $this->app, $this->dminsql, $this->dmaxsql, $this->sbasid, $this->collection
            );

            //get number of edited document
            $this->report['activity_edited'] = module_report_activity::activiteEditedDocument(
                    $this->app, $this->dminsql, $this->dmaxsql, $this->sbasid, $this->collection
            );
            if ($this->app['conf']->get(['registry', 'modules', 'anonymous-report']) == false) {
                //get users that add the most documents
                $this->report['top_ten_added'] = module_report_activity::activiteAddedTopTenUser(
                        $this->app, $this->dminsql, $this->dmaxsql, $this->sbasid, $this->collection
                );
            }
        } catch (DBALException $e) {

        }

        return;
    }

    /**
     * @desc return variable that contains the results
     * @return <array>
     */
    public function getDash()
    {
        return $this->report;
    }

    /**
     * @desc check if the results are valid
     * @return <bool>
     */
    public function isValid()
    {
        if (isset($this->report) && sizeof($this->report) > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function setApplication(Application $app)
    {
        $this->app = $app;
    }

    public function __sleep()
    {
        $vars = [];
        foreach ($this as $key => $value) {
            if (in_array($key, ['app']))
                continue;
            $vars[] = $key;
        }

        return $vars;
    }
}
