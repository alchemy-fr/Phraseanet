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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchManagementService;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchSettingsFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchEngineController extends Controller
{
    /**
     * @var ElasticSearchManagementService
     */
    private $managementService;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @param Application $app
     * @param ElasticSearchManagementService $managementService
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(
        Application $app,
        ElasticSearchManagementService $managementService,
        FormFactoryInterface $formFactory
    ) {
        parent::__construct($app);

        $this->managementService = $managementService;
        $this->formFactory = $formFactory;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function formConfigurationPanelAction(Request $request)
    {
        $options = $this->managementService->getCurrentConfiguration();
        $form = $this->formFactory->create(new ElasticsearchSettingsFormType(), $options, [
            'action' => $this->app->url('admin_searchengine_form'),
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $options = $form->getData();

            $this->managementService->updateConfiguration($options);

            return $this->app->redirectPath('admin_searchengine_form');
        }

        return $this->render('admin/search-engine/elastic-search.html.twig', [
            'form' => $form->createView(),
            'elasticSearchIndexExists' => $this->managementService->indexExists()
        ]);
    }

    public function dropIndexAction()
    {
        $this->managementService->dropIndices();

        return $this->app->redirectPath('admin_searchengine_form');
    }

    public function createIndexAction()
    {
        $this->managementService->createIndices();

        return $this->app->redirectPath('admin_searchengine_form');
    }
}
