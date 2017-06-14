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
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchSettingsFormType;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
            $this->saveElasticSearchOptions($form->getData());

            return $this->app->redirectPath('admin_searchengine_form');
        }

        return $this->render('admin/search-engine/elastic-search.html.twig', [
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
        $this->getConf()->set(['main', 'search-engine', 'options'], $configuration->toArray());
    }

    /**
     * @param ElasticsearchOptions $options
     * @return FormInterface
     */
    private function getConfigurationForm(ElasticsearchOptions $options)
    {
        return $this->app->form(new ElasticsearchSettingsFormType(), $options, [
            'action' => $this->app->url('admin_searchengine_form'),
        ]);
    }

    public function dumpResultIndexElasticsearchAction()
    {
        $indexer = $this->app['elasticsearch.indexer'];

        if (!$indexer->indexExists())
        {
            return $this->app->json([
                'success' => false,
                'message' => $this->app->trans('An error occurred'),
            ]);
        }

        $index = $this->app['elasticsearch.index'];
        $settings = $indexer->getSettings();

        return $this->app->json([
                'success' => true,
                'response' => $settings[$index->getName()]['settings']['index']
        ]);
    }
}
