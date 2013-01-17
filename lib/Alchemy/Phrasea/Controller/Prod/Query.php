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

use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
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

        $options = SearchEngineOptions::fromRequest($app, $request);

        $form = $options->serialize();

        $perPage = (int) $app['phraseanet.user']->getPrefs('images_per_page');

        $app['phraseanet.SE']->setOptions($options);

        $page = (int) $request->request->get('pag');
        $firstPage = $page < 1;

        if ($page < 1) {
            $app['phraseanet.SE']->resetCache();
            $page = 1;
        }

        $result = $app['phraseanet.SE']->query($query, (($page - 1) * $perPage), $perPage);

        foreach ($options->getDataboxes() as $databox) {
            $colls = array_map(function(\collection $collection) {
                return $collection->get_coll_id();
            }, array_filter($options->getCollections(), function(\collection $collection) use ($databox) {
                return $collection->get_databox()->get_sbas_id() == $databox->get_sbas_id();
            }));

            $app['phraseanet.SE.logger']->log($databox, $result->getQuery(), $result->getTotal(), $colls);
        }

        $proposals = $firstPage ? $result->getProposals() : false;

        $npages = $result->getTotalPages($perPage);

        $page = $result->getCurrentPage($perPage);

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

        if ($result->getTotal() != $result->getAvailable()) {
            $explain .= sprintf(_('reponses:: %d Resultats rappatries sur un total de %d trouves'), $result->getAvailable(), $result->getTotal());
        } else {
            $explain .= sprintf(_('reponses:: %d Resultats'), $result->getTotal());
        }

        $explain .= " </b></span>";
        $explain .= '<br><div>' . $result->getDuration() . ' s</div>dans index ' . $result->getIndexes();
        $explain .= "</div>";

        $infoResult = '<a href="#" class="infoDialog" infos="' . str_replace('"', '&quot;', $explain) . '">' . sprintf(_('reponses:: %d reponses'), $result->getTotal()) . '</a> | ' . sprintf(_('reponses:: %s documents selectionnes'), '<span id="nbrecsel"></span>');

        $json['infos'] = $infoResult;
        $json['navigation'] = $string;

        $prop = null;

        if ($firstPage) {
            $propals = $result->getSuggestions();
            if (count($propals) > 0) {
                foreach ($propals as $prop_array) {
                    if ($prop_array['value'] !== $query && $prop_array['hits'] > $result->getTotal()) {
                        $prop = $prop_array['value'];
                        break;
                    }
                }
            }
        }

        if ($result->getTotal() === 0) {
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
            'highlight'       => $result->getQuery(),
            'searchEngine'    => $app['phraseanet.SE'],
            'suggestions'     => $prop
            )
        );

        $json['query'] = $query;
        $json['phrasea_props'] = $proposals;
        $json['total_answers'] = (int) $result->getAvailable();
        $json['next_page'] = ($page < $npages && $result->getAvailable() > 0) ? ($page + 1) : false;
        $json['prev_page'] = ($page > 1 && $result->getAvailable() > 0) ? ($page - 1) : false;
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
        if (null === $optionsSerial = $request->request->get('options_serial')) {
            $app->abort(400, 'Search engine options are missing');
        }

        try {
            $options = SearchEngineOptions::hydrate($app, $optionsSerial);
            $app['phraseanet.SE']->setOptions($options);
        } catch (\Exception $e) {
            $app->abort(400, 'Provided search engine options are not valid');
        }

        $pos = (int) $request->request->get('pos', 0);
        $query = $request->request->get('query', '');

        $record = new \record_preview($app, 'RESULT', $pos, '', $app['phraseanet.SE'], $query);

        return $app->json(array(
            'current' => $app['twig']->render('prod/preview/result_train.html.twig', array(
                'records'  => $record->get_train($pos, $query, $app['phraseanet.SE']),
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

        return new Response($app['twig']->render('prod/preview/reg_train.html.twig', array(
            'container_records' => $record->get_container()->get_children(),
            'record'            => $record
        )));
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
