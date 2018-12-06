<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\SearchEngineAware;
use Alchemy\Phrasea\Cache\Exception;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Configuration\DisplaySettingService;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\Model\Entities\ElasticsearchRecord;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Alchemy\Phrasea\Utilities\StringHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QueryController extends Controller
{
    use SearchEngineAware;

    /**
     * Query Phraseanet to fetch records
     *
     * @param  Request $request
     * @return Response
     */
    public function query(Request $request)
    {
        $query = (string) $request->request->get('qry');

        // since the query comes from a submited form, normalize crlf,cr,lf ...
        $query = StringHelper::crlfNormalize($query);

        $json = [
            'query' => $query
        ];

        $options = SearchEngineOptions::fromRequest($this->app, $request);

        $perPage = (int) $this->getSettings()->getUserSetting($this->getAuthenticatedUser(), 'images_per_page');

        $page = (int) $request->request->get('pag');
        $firstPage = $page < 1;

        $engine = $this->getSearchEngine();
        if ($page < 1) {
            $engine->resetCache();
            $page = 1;
        }

        $options->setFirstResult(($page - 1) * $perPage);
        $options->setMaxResults($perPage);

        $user = $this->getAuthenticatedUser();
        $userManipulator = $this->getUserManipulator();
        $userManipulator->logQuery($user, $query);

        try {
            $result = $engine->query($query, $options);

            if ($this->getSettings()->getUserSetting($user, 'start_page') === 'LAST_QUERY') {
                $userManipulator->setUserSetting($user, 'start_page_query', $query);
            }

            foreach ($options->getDataboxes() as $databox) {
                $collections = array_map(function (\collection $collection) {
                    return $collection->get_coll_id();
                }, array_filter($options->getCollections(), function (\collection $collection) use ($databox) {
                    return $collection->get_databox()->get_sbas_id() == $databox->get_sbas_id();
                }));

                $this->getSearchEngineLogger()->log($databox, $result->getQueryText(), $result->getTotal(), $collections);
            }

            $proposals = $firstPage ? $result->getProposals() : false;

            $npages = $result->getTotalPages($perPage);

            $page = $result->getCurrentPage($perPage);

            $queryESLib = $result->getQueryESLib();

            $string = '';

            if ($npages > 1) {
                $d2top = ($npages - $page);
                $d2bottom = $page;

                if (min($d2top, $d2bottom) < 4) {
                    if ($d2bottom < 4) {
                        if($page != 1){
                            $string .= "<a id='PREV_PAGE' class='btn btn-primary btn-mini'></a>";
                        }
                        for ($i = 1; ($i <= 4 && (($i <= $npages) === true)); $i++) {
                            if ($i == $page)
                                $string .= '<input onkeypress="if(event.keyCode == 13 && !isNaN(parseInt(this.value)))gotopage(parseInt(this.value))" type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" class="btn btn-mini" />';
                            else
                                $string .= "<a onclick='gotopage(" . $i . ");return false;' class='btn btn-primary btn-mini'>" . $i . "</a>";
                        }
                        if ($npages > 4)
                            $string .= "<a id='NEXT_PAGE' class='btn btn-primary btn-mini'></a>";
                        $string .= "<a onclick='gotopage(" . ($npages) . ");return false;' class='btn btn-primary btn-mini' id='last'></a>";
                    } else {
                        $start = $npages - 4;
                        if (($start) > 0){
                            $string .= "<a onclick='gotopage(1);return false;' class='btn btn-primary btn-mini' id='first'></a>";
                            $string .= "<a id='PREV_PAGE' class='btn btn-primary btn-mini'></a>";
                        }else
                            $start = 1;
                        for ($i = ($start); $i <= $npages; $i++) {
                            if ($i == $page)
                                $string .= '<input onkeypress="if(event.keyCode == 13 && !isNaN(parseInt(this.value)))gotopage(parseInt(this.value))" type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" class="btn btn-mini" />';
                            else
                                $string .= "<a onclick='gotopage(" . $i . ");return false;' class='btn btn-primary btn-mini'>" . $i . "</a>";
                        }
                        if($page < $npages){
                            $string .= "<a id='NEXT_PAGE' class='btn btn-primary btn-mini'></a>";
                        }
                    }
                } else {
                    $string .= "<a onclick='gotopage(1);return false;' class='btn btn-primary btn-mini' id='first'></a>";

                    for ($i = ($page - 2); $i <= ($page + 2); $i++) {
                        if ($i == $page)
                            $string .= '<input onkeypress="if(event.keyCode == 13 && !isNaN(parseInt(this.value)))gotopage(parseInt(this.value))" type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" class="btn btn-mini" />';
                        else
                            $string .= "<a onclick='gotopage(" . $i . ");return false;' class='btn btn-primary btn-mini'>" . $i . "</a>";
                    }

                    $string .= "<a onclick='gotopage(" . ($npages) . ");return false;' class='btn btn-primary btn-mini' id='last'></a>";
                }
            }
            $string .= '<div style="display:none;"><div id="NEXT_PAGE"></div><div id="PREV_PAGE"></div></div>';

            $explain = $this->render(
                "prod/results/infos.html.twig",
                [
                    'results'=> $result,
                    'esquery' => $this->getAclForUser()->is_admin() ?
                        json_encode($queryESLib['body'], JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) :
                        null
                ]
            );

            $infoResult = '<div id="docInfo">'
                . $this->app->trans('%number% documents<br/>selectionnes', ['%number%' => '<span id="nbrecsel"></span>'])
                . '</div><a href="#" class="infoDialog" infos="' . str_replace('"', '&quot;', $explain) . '">'
                . $this->app->trans('%total% reponses', ['%total%' => '<span>'.$result->getTotal().'</span>']) . '</a>';

            $json['infos'] = $infoResult;
            $json['navigationTpl'] = $string;
            $json['navigation'] = [
                'page' => $page,
                'perPage' => $perPage
            ];

            $prop = null;

            if ($firstPage) {
                $propals = $result->getSuggestions();
                if (count($propals) > 0) {
                    foreach ($propals as $prop_array) {
                        if ($prop_array->getSuggestion() !== $query && $prop_array->getHits() > $result->getTotal()) {
                            $prop = $prop_array->getSuggestion();
                            break;
                        }
                    }
                }
            }

            if ($result->getTotal() === 0) {
                $template = 'prod/results/help.html.twig';
            } else {
                $template = 'prod/results/records.html.twig';
            }
            $json['results'] = $this->render($template, ['results'=> $result]);


            // add technical fields
            $fieldsInfosByName = [];
            foreach(ElasticsearchOptions::getAggregableTechnicalFields() as $k => $f) {
                $fieldsInfosByName[$k] = $f;
                $fieldsInfosByName[$k]['trans_label'] = $this->app->trans($f['label']);
            }

            // add databox fields
            // get infos about fields, fusionned and by databox
            $fieldsInfos = [];  // by databox
            foreach ($this->app->getDataboxes() as $databox) {
                $sbasId = $databox->get_sbas_id();
                $fieldsInfos[$sbasId] = [];
                foreach ($databox->get_meta_structure() as $field) {
                    $name = $field->get_name();
                    $fieldsInfos[$sbasId][$name] = [
                        'label'    => $field->get_label($this->app['locale']),
                        'type'     => $field->get_type(),
                        'business' => $field->isBusiness(),
                        'multi'    => $field->is_multi(),
                    ];
                    if (!isset($fieldsInfosByName[$name])) {
                        $fieldsInfosByName[$name] = [
                            'label' => $field->get_label($this->app['locale']),
                            'type' => $field->get_type(),
                            'field' => $field->get_name(),
                            'query' => "field." . $field->get_name() . ":%s",
                            'trans_label' => $field->get_label($this->app['locale']),
                        ];
                        $field->get_label($this->app['locale']);
                    }
                }
            }

            // populates fileds infos
            $json['fields'] = $fieldsInfos;

            // populates rawresults
            // need acl so the result will not include business fields where not allowed
            $acl = $this->getAclForUser();
            $json['rawResults'] = [];
            /** @var ElasticsearchRecord $record */
            foreach ($result->getResults() as $record) {
                $rawRecord = $record->asArray();

                $sbasId = $record->getDataboxId();
                $baseId = $record->getBaseId();

                $caption = $rawRecord['caption'];
                if ($acl && $acl->has_right_on_base($baseId, \ACL::CANMODIFRECORD)) {
                    $caption = array_merge($caption, $rawRecord['privateCaption']);
                }

                // read the fields following the structure order
                $rawCaption = [];
                foreach ($fieldsInfos[$sbasId] as $fieldName => $fieldInfos) {
                    if (array_key_exists($fieldName, $caption)) {
                        $rawCaption[$fieldName] = $caption[$fieldName];
                    }
                }
                $rawRecord['caption'] = $rawCaption;
                unset($rawRecord['privateCaption']);

                $json['rawResults'][$record->getId()] = $rawRecord;
            }

            // populates facets (aggregates)
            $facets = [];
            // $facetClauses = [];
            foreach ($result->getFacets() as $facet) {
                $facetName = $facet['name'];

                if(array_key_exists($facetName, $fieldsInfosByName)) {
                    $f = $fieldsInfosByName[$facetName];
                    $facet['label'] = $f['trans_label'];
                    $facet['type'] = strtoupper($f['type']) . "-AGGREGATE";
                    $facets[] = $facet;
                    // $facetClauses[] = [
                    //    'type'  => strtoupper($f['type']) . "-AGGREGATE",
                    //    'field' => $f['field'],
                    //    'facet' => $facet
                    // ];
                }
            }

            // $json['jsq'] = $facetClauses;

            $json['facets'] = $facets;
            $json['phrasea_props'] = $proposals;
            $json['total_answers'] = (int) $result->getAvailable();
            $json['next_page'] = ($page < $npages && $result->getAvailable() > 0) ? ($page + 1) : false;
            $json['prev_page'] = ($page > 1 && $result->getAvailable() > 0) ? ($page - 1) : false;
            $json['form'] = $options->serialize();
            $json['queryCompiled'] = $result->getQueryCompiled();
            $json['queryAST'] = $result->getQueryAST();
            $json['queryESLib'] = $queryESLib;
        }
        catch(\Exception $e) {
            // we'd like a message from the parser so get all the exceptions messages
            $msg = '';
            for(; $e; $e=$e->getPrevious()) {
                $msg .= ($msg ? "\n":"") . $e->getMessage();
            }
            $template = 'prod/results/help.html.twig';
            $result = [
                'error' => $msg
            ];
            $json['results'] = $this->render($template, ['results'=> $result]);
        }


        return $this->app->json($json);
    }

    /**
     * @return DisplaySettingService
     */
    private function getSettings()
    {
        return $this->app['settings'];
    }

    /**
     * @return mixed
     */
    private function getUserManipulator()
    {
        return $this->app['manipulator.user'];
    }

    /**
     * Get a preview answer train
     *
     * @param  Request $request
     * @return Response
     */
    public function queryAnswerTrain(Request $request)
    {
        if (null === $optionsSerial = $request->get('options_serial')) {
            $this->app->abort(400, 'Search engine options are missing');
        }

        try {
            $options = SearchEngineOptions::hydrate($this->app, $optionsSerial);
        } catch (\Exception $e) {
            $this->app->abort(400, 'Provided search engine options are not valid');
        }

        $pos = (int) $request->request->get('pos', 0);
        $query = $request->request->get('query', '');

        $record = new \record_preview($this->app, 'RESULT', $pos, '', $this->getSearchEngine(), $query, $options);

        $index = ($pos - 3) < 0 ? 0 : ($pos - 3);
        return $this->app->json([
            'current' => $this->render('prod/preview/result_train.html.twig', [
                'records'  => $record->get_train(),
                'index' => $index,
                'selected' => $pos,
            ])
        ]);
    }
}
