<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchSettingsFormType;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\GlobalStructure;
use databox_descriptionStructure;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class SearchEngineController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function formConfigurationPanelAction(Request $request)
    {
        $options = $this->getElasticsearchOptions();
        $form = $this->getConfigurationForm($options);

        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var ElasticsearchOptions $data */
            $data = $form->getData();
            // $q = $request->request->get('elasticsearch_settings');
            $facetNames = [];   // rebuild the data "_customValues/facets" list following the form order
            foreach($request->request->get('elasticsearch_settings') as $name=>$value) {
                $matches = null;
                if(preg_match('/^facets:(.+):limit$/', $name, $matches) === 1) {
                    $facetNames[] = $matches[1];
                }
            }
            $data->reorderAggregableFields($facetNames);

            $this->saveElasticSearchOptions($data);

            return $this->app->redirectPath('admin_searchengine_form');
        }

        return $this->render('admin/search-engine/search-engine-settings.html.twig', [
            'form' => $form->createView(),
            'indexer' => $this->app['elasticsearch.indexer']
        ]);
    }



    public function dropIndexAction(Request $request)
    {
        $indexer = $this->app['elasticsearch.indexer'];
        if ($indexer->indexExists()) {
            $indexer->deleteIndex();
        }
        return $this->app->redirectPath('admin_searchengine_form');
    }

    public function createIndexAction(Request $request)
    {
        $indexer = $this->app['elasticsearch.indexer'];
        if (!$indexer->indexExists()) {
            $indexer->createIndex();
        }
        return $this->app->redirectPath('admin_searchengine_form');
    }

    /**
     * @return ElasticsearchOptions
     */
    private function getElasticsearchOptions()
    {
        return $this->app['elasticsearch.options'];
    }

    /**
     * @param ElasticsearchOptions $configuration
     * @return void
     */
    private function saveElasticSearchOptions(ElasticsearchOptions $configuration)
    {
        // save to databoxes fields for backward compatibility (useless ?)
        foreach($configuration->getAggregableFields() as $fname=>$aggregableField) {
            foreach ($this->app->getDataboxes() as $databox) {
                if(!is_null($f = $databox->get_meta_structure()->get_element_by_name($fname, databox_descriptionStructure::STRICT_COMPARE))) {
                    $f->set_aggregable($aggregableField['limit'])->save();
                }
            }
        }

        // save to conf
        $this->getConf()->set(['main', 'search-engine', 'options'], $configuration->toArray());
    }

    /**
     * @param ElasticsearchOptions $options
     * @return FormInterface
     */
    private function getConfigurationForm(ElasticsearchOptions $options)
    {
        /** @var GlobalStructure $g */
        $g = $this->app['search_engine.global_structure'];

        return $this->app->form(new ElasticsearchSettingsFormType($g, $options, $this->getTranslator()), $options, [
            'action' => $this->app->url('admin_searchengine_form'),
        ]);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSettingFromIndexAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }
        $indexer = $this->app['elasticsearch.indexer'];
        $index = $request->get('index');
        if (!$indexer->indexExists() || is_null($index))
        {
            return $this->app->json([
                'success' => false,
                'message' => $this->app->trans('An error occurred'),
            ]);
        }
        return $this->app->json([
            'success' => true,
            'response' => $indexer->getSettings(['index' => $index])
        ]);
    }

    /**
     * @return TranslatorInterface
     */
    private function getTranslator()
    {
        return $this->app['translator'];
    }
}
