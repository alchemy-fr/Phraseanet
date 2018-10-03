<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Admin;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Admin\FieldsController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Vocabulary\ControlProvider\UserProvider;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Fields implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['vocabularies'] = $app->share(function (PhraseaApplication $app) {
            $vocabularies = new \Pimple();

            $vocabularies['user'] = $vocabularies->share(function () use ($app) {
                return new UserProvider($app);
            });

            return $vocabularies;
        });
        $app['controller.admin.fields'] = $app->share(function (PhraseaApplication $app) {
            return new FieldsController($app);
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall
                ->requireAccessToModule('admin')
                ->requireRight(\ACL::BAS_MODIFY_STRUCT);
        });

        $controllers->get('/language.json', 'controller.admin.fields:getLanguage')
            ->bind('admin_fields_language');

        $controllers->get('/{sbas_id}', 'controller.admin.fields:displayApp')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields');

        $controllers->put('/{sbas_id}/fields', 'controller.admin.fields:updateFields')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields_register');

        $controllers->get('/{sbas_id}/fields', 'controller.admin.fields:listFields')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields_list');

        $controllers->post('/{sbas_id}/fields', 'controller.admin.fields:createField')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields_create_field');

        $controllers->get('/{sbas_id}/fields/{id}', 'controller.admin.fields:getField')
            ->assert('id', '\d+')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields_show_field');

        $controllers->put('/{sbas_id}/fields/{id}', 'controller.admin.fields:updateField')
            ->assert('id', '\d+')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields_update_field');

        $controllers->delete('/{sbas_id}/fields/{id}', 'controller.admin.fields:deleteField')
            ->assert('id', '\d+')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields_delete_field');

        $controllers->get('/tags/search', 'controller.admin.fields:searchTag')
            ->bind('admin_fields_search_tag');

        $controllers->get('/tags/{tagname}', 'controller.admin.fields:getTag')
            ->bind('admin_fields_show_tag');

        $controllers->get('/vocabularies', 'controller.admin.fields:listVocabularies')
            ->bind('admin_fields_list_vocabularies');

        $controllers->get('/vocabularies/{type}', 'controller.admin.fields:getVocabulary')
            ->bind('admin_fields_show_vocabulary');

        $controllers->get('/dc-fields', 'controller.admin.fields:listDcFields')
            ->bind('admin_fields_list_dc_fields');

        $controllers->get('/dc-fields/{name}', 'controller.admin.fields:getDcFields')
            ->bind('admin_fields_get_dc_fields');

        return $controllers;
    }
}
