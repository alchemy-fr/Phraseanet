<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Query implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
                $app['firewall']->requireAuthentication();
            });

        /**
         * Query Phraseanet
         *
         * name         : prod_query
         *
         * description  : Query Phraseanet
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/', $this->call('query'))
            ->bind('prod_query');

        /**
         * Get a preview answer train
         *
         * name         : preview_answer_train
         *
         * description  : Get a preview answer train
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/answer-train/', $this->call('queryAnswerTrain'))
            ->bind('preview_answer_train');

        /**
         * Get a preview reg train
         *
         * name         : preview_reg_train
         *
         * description  : Get a preview reg train
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/reg-train/', $this->call('queryRegTrain'))
            ->bind('preview_reg_train');

        return $controllers;
    }

    /**
     * Query Phraseanet to fetch records
     *
     * @param   Application $app
     * @param   Request     $request
     * @return  JsonResponse
     */
    public function query(Application $app, Request $request)
    {
        $query = (string) $request->request->get('qry');

        $mod = $app['phraseanet.user']->getPrefs('view');

        $json = array();

        $options = new \searchEngine_options();

        $bas = is_array($request->request->get('bas')) ? $request->request->get('bas') : array_keys($user->ACL()->get_granted_base());

        if ($app['phraseanet.user']->ACL()->has_right('modifyrecord')) {
            $options->set_business_fields(array());

            $BF = array();

            foreach ($app['phraseanet.user']->ACL()->get_granted_base(array('canmodifrecord')) as $collection) {
                if (count($bas) === 0 || in_array($collection->get_base_id(), $bas)) {
                    $BF[] = $collection->get_base_id();
                }
            }
            $options->set_business_fields($BF);
        } else {
            $options->set_business_fields(array());
        }

        $status = is_array($request->request->get('status')) ? $request->request->get('status') : array();
        $fields = is_array($request->request->get('fields')) ? $request->request->get('fields') : array();

        $options->set_fields($fields);
        $options->set_status($status);
        $options->set_bases($bas, $app['phraseanet.user']->ACL());

        $options->set_search_type($request->request->get('search_type'));
        $options->set_record_type($request->request->get('recordtype'));
        $options->set_min_date($request->request->get('datemin'));
        $options->set_max_date($request->request->get('datemax'));
        $options->set_date_fields(explode('|', $request->request->get('datefield')));
        $options->set_sort($request->request->get('sort'), $request->request->get('ord', PHRASEA_ORDER_DESC));
        $options->set_use_stemming($request->request->get('stemme'));

        $form = serialize($options);

        $perPage = (int) $app['phraseanet.user']->getPrefs('images_per_page');

        $search_engine = new \searchEngine_adapter($app);
        $search_engine->set_options($options);

        $page = (int) $request->request->get('pag');

        if ($page < 1) {
            $search_engine->set_is_first_page(true);
            $search_engine->reset_cache();
            $page = 1;
        }

        $result = $search_engine->query_per_page($query, $page, $perPage);

        $proposals = $search_engine->is_first_page() ? $result->get_propositions() : false;

        $npages = $result->get_total_pages();

        $page = $result->get_current_page();

        $string = '';

        if ($npages > 1) {

            $d2top = ($npages - $page);
            $d2bottom = $page;

            if (min($d2top, $d2bottom) < 4) {
                if ($d2bottom < 4) {
                    for ($i = 1; ($i <= 4 && (($i <= $npages) === true)); $i++) {
                        if ($i == $page)
                            $string .= '<input onkeypress="if(event.keyCode == 13 && !isNaN(parseInt(this.value)))gotopage(parseInt(this.value))" type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" class="btn btn-mini" />';
                        else
                            $string .= "<a onclick='gotopage(" . $i . ");return false;' class='btn btn-primary btn-mini'>" . $i . "</a>";
                    }
                    if ($npages > 4)
                        $string .= "<a onclick='gotopage(" . ($npages) . ");return false;' class='btn btn-primary btn-mini'>&gt;&gt;</a>";
                } else {
                    $start = $npages - 4;
                    if (($start) > 0)
                        $string .= "<a onclick='gotopage(1);return false;' class='btn btn-primary btn-mini'>&lt;&lt;</a>";
                    else
                        $start = 1;
                    for ($i = ($start); $i <= $npages; $i++) {
                        if ($i == $page)
                            $string .= '<input onkeypress="if(event.keyCode == 13 && !isNaN(parseInt(this.value)))gotopage(parseInt(this.value))" type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" class="btn btn-mini" />';
                        else
                            $string .= "<a onclick='gotopage(" . $i . ");return false;' class='btn btn-primary btn-mini'>" . $i . "</a>";
                    }
                }
            } else {
                $string .= "<a onclick='gotopage(1);return false;' class='btn btn-primary btn-mini'>&lt;&lt;</a>";

                for ($i = ($page - 2); $i <= ($page + 2); $i++) {
                    if ($i == $page)
                        $string .= '<input onkeypress="if(event.keyCode == 13 && !isNaN(parseInt(this.value)))gotopage(parseInt(this.value))" type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" class="btn btn-mini" />';
                    else
                        $string .= "<a onclick='gotopage(" . $i . ");return false;' class='btn btn-primary btn-mini'>" . $i . "</a>";
                }

                $string .= "<a onclick='gotopage(" . ($npages) . ");return false;' class='btn btn-primary btn-mini'>&gt;&gt;</a>";
            }
        }
        $string .= '<div style="display:none;"><div id="NEXT_PAGE"></div><div id="PREV_PAGE"></div></div>';

        $explain = "<div id=\"explainResults\" class=\"myexplain\">";

        $explain .= "<img src=\"/skins/icons/answers.gif\" /><span><b>";

        if ($result->get_count_total_results() != $result->get_count_available_results()) {
            $explain .= sprintf(_('reponses:: %d Resultats rappatries sur un total de %d trouves'), $result->get_count_available_results(), $result->get_count_total_results());
        } else {
            $explain .= sprintf(_('reponses:: %d Resultats'), $result->get_count_total_results());
        }

        $explain .= " </b></span>";
        $explain .= '<br><div>' . $result->get_query_time() . ' s</div>dans index ' . $result->get_search_indexes();
        $explain .= "</div>";

        $infoResult = '<a href="#" class="infoDialog" infos="' . str_replace('"', '&quot;', $explain) . '">' . sprintf(_('reponses:: %d reponses'), $result->get_count_total_results()) . '</a> | ' . sprintf(_('reponses:: %s documents selectionnes'), '<span id="nbrecsel"></span>');

        $json['infos'] = $infoResult;
        $json['navigation'] = $string;

        $prop = null;

        if ($search_engine->is_first_page()) {
            $propals = $result->get_suggestions($app['locale.I18n']);
            if (count($propals) > 0) {
                foreach ($propals as $prop_array) {
                    if ($prop_array['value'] !== $query && $prop_array['hits'] > $result->get_count_total_results()) {
                        $prop = $prop_array['value'];
                        break;
                    }
                }
            }
        }

        if ($result->get_count_total_results() === 0) {
            $template = 'prod/results/help.html.twig';
        } else {
            if ($mod == 'thumbs') {
                $template = 'prod/results/answergrid.html.twig';
            } else {
                $template = 'prod/results/answerlist.html.twig';
            }
        }

        $json['results'] = $app['twig']->render($template, array(
            'results'         => $result,
            'GV_social_tools' => $app['phraseanet.registry']->get('GV_social_tools'),
            'highlight'       => $search_engine->get_query(),
            'searchEngine'    => $search_engine,
            'suggestions'     => $prop
            )
        );

        $json['query'] = $query;
        $json['phrasea_props'] = $proposals;
        $json['total_answers'] = (int) $result->get_count_available_results();
        $json['next_page'] = ($page < $npages && $result->get_count_available_results() > 0) ? ($page + 1) : false;
        $json['prev_page'] = ($page > 1 && $result->get_count_available_results() > 0) ? ($page - 1) : false;
        $json['form'] = $form;

        return $app->json($json);
    }

    /**
     * Get a preview answer train
     *
     * @param   Application $app
     * @param   Request     $request
     * @return  JsonResponse
     */
    public function queryAnswerTrain(Application $app, Request $request)
    {
        $searchEngine = null;

        if (($options = unserialize($request->request->get('options_serial'))) !== false) {
            $searchEngine = new \searchEngine_adapter($app);
            $searchEngine->set_options($options);
        }

        $pos = $request->request->get('pos');
        $query = $request->request->get('query');

        $record = new \record_preview($app, 'RESULT', $pos, '', '', $searchEngine, $query);

        return $app->json(array(
                'current' => $app['twig']->render('prod/preview/result_train.html.twig', array(
                    'records'  => $record->get_train($pos, $query, $searchEngine),
                    'selected' => $pos
                ))
            ));
    }

    /**
     * Get a preview reg train
     *
     * @param   Application $app
     * @param   Request     $request
     * @return  Response
     */
    public function queryRegTrain(Application $app, Request $request)
    {
        $record = new \record_preview($app, 'REG', $request->request->get('pos'), $request->request->get('cont'));

        return $app['twig']->render('prod/preview/reg_train.html.twig', array(
                'container_records' => $record->get_container()->get_children(),
                'record'            => $record
            ));
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
