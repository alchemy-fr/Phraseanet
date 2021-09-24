<?php

namespace Alchemy\Phrasea\PhraseanetService\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\PhraseanetService\Controller\PSExposeController;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class PSExposeServiceProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    /**
     * @inheritDoc
     */
    public function register(Application $app)
    {
        $app['controller.ps.expose'] = $app->share(function (PhraseaApplication $app) {
            return new PSExposeController($app);
        });
    }

    /**
     * @inheritDoc
     */
    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        $controllers->match('/authenticate/', 'controller.ps.expose:authenticateAction')
            ->method('POST')
            ->bind('ps_expose_authenticate');

        $controllers->match('/logout/', 'controller.ps.expose:logoutAction')
            ->method('GET')
            ->bind('ps_expose_logout');

        $controllers->match('/create-publication/', 'controller.ps.expose:createPublicationAction')
            ->method('POST')
            ->bind('ps_expose_create_publication');

        $controllers->match('/update-publication/{publicationId}', 'controller.ps.expose:updatePublicationAction')
            ->method('POST|PUT')
            ->bind('ps_expose_update_publication');

        $controllers->match('/list-publication/', 'controller.ps.expose:listPublicationAction')
            ->method('GET')
            ->bind('ps_expose_list_publication');

        $controllers->match('/get-publication/{publicationId}/assets', 'controller.ps.expose:getPublicationAssetsAction')
            ->method('GET')
            ->bind('ps_expose_get_publication_assets');

        $controllers->match('/get-publication/{publicationId}', 'controller.ps.expose:getPublicationAction')
            ->method('GET')
            ->bind('ps_expose_get_publication');

        $controllers->match('/list-profile', 'controller.ps.expose:listProfileAction')
            ->method('GET')
            ->bind('ps_expose_get_publication_profile');

        $controllers->match('/delete-publication/{publicationId}/', 'controller.ps.expose:deletePublicationAction')
            ->method('POST|DELETE')
            ->bind('ps_expose_delete_publication');

        $controllers->match('/publication/delete-asset/{publicationId}/{assetId}/', 'controller.ps.expose:deletePublicationAssetAction')
            ->method('POST|DELETE')
            ->bind('ps_expose_publication_delete_asset');

        $controllers->match('/publication/add-assets', 'controller.ps.expose:addPublicationAssetsAction')
            ->method('POST')
            ->bind('ps_expose_publication_add_assets');

        $controllers->match('/publication/update-assets-order/', 'controller.ps.expose:updatePublicationAssetsOrderAction')
            ->method('POST|PUT')
            ->bind('ps_expose_publication_update_assets_order');

        $controllers->match('/publication/permission/update', 'controller.ps.expose:updatePublicationPermissionAction')
            ->method('POST')
            ->bind('ps_expose_publication_permission_update');

        $controllers->match('/publication/permission/list', 'controller.ps.expose:listPublicationPermissionAction')
            ->method('GET')
            ->bind('ps_expose_publication_permission_list');

        $controllers->match('/publication/slug-availability/{slug}/', 'controller.ps.expose:checkPublicationSlugAction')
            ->method('GET')
            ->bind('ps_expose_publication_slug_availability');

        $controllers->match('/databoxes-field', 'controller.ps.expose:getDataboxesFieldAction')
            ->method('GET')
            ->bind('ps_expose_get_databoxes_field');

        $controllers->match('/subdefs-list', 'controller.ps.expose:getSubdefsListAction')
            ->method('GET')
            ->bind('ps_expose_get_subdefs_list');

        $controllers->match('/field-mapping', 'controller.ps.expose:saveFieldMappingAction')
            ->method('POST')
            ->bind('ps_expose_save_field_mapping');

        $controllers->get('/field-mapping', 'controller.ps.expose:getFieldMappingAction')
            ->bind('ps_expose_get_field_mapping');

        $controllers->match('/subdef-mapping', 'controller.ps.expose:saveSubdefMappingAction')
            ->method('POST')
            ->bind('ps_expose_save_subdef_mapping');

        return $controllers;
    }

    /**
     * @inheritDoc
     */
    public function boot(Application $app)
    {

    }
}
