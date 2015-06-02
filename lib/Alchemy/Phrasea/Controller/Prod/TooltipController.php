<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

class TooltipController extends Controller
{
    public function displayBasket(Application $app, Basket $basket)
    {
        return $app['twig']->render('prod/Tooltip/Basket.html.twig', ['basket' => $basket]);
    }

    public function displayStory(Application $app, $sbas_id, $record_id)
    {
        $Story = new \record_adapter($app, $sbas_id, $record_id);

        return $app['twig']->render('prod/Tooltip/Story.html.twig', ['Story' => $Story]);
    }

    public function displayUserBadge(Application $app, $usr_id)
    {
        $user = $app['repo.users']->find($usr_id);

        return $app['twig']->render(
            'prod/Tooltip/User.html.twig'
            , ['user' => $user]
        );
    }

    public function displayPreview(Application $app, $sbas_id, $record_id)
    {
        return $app['twig']->render('prod/Tooltip/Preview.html.twig', [
            'record' => new \record_adapter($app, $sbas_id, $record_id),
            'not_wrapped' => true
        ]);
    }

    public function displayCaption(Application $app, $sbas_id, $record_id, $context)
    {
        $number = (int) $app['request']->get('number');
        $record = new \record_adapter($app, $sbas_id, $record_id, $number);

        $search_engine = $search_engine_options = null;

        if ($context == 'answer') {
            try {
                $search_engine_options = SearchEngineOptions::hydrate($app, $app['request']->request->get('options_serial'));
                $search_engine = $app['phraseanet.SE'];
            } catch (\Exception $e) {
                $search_engine = null;
            }
        }

        return $app['twig']->render(
            'prod/Tooltip/Caption.html.twig'
            , [
            'record'        => $record,
            'view'          => $context,
            'highlight'     => $app['request']->request->get('query'),
            'searchEngine'  => $search_engine,
            'searchOptions' => $search_engine_options,
        ]);
    }

    public function displayTechnicalDatas(Application $app, $sbas_id, $record_id)
    {
        $record = new \record_adapter($app, $sbas_id, $record_id);

        try {
            $document = $record->get_subdef('document');
        } catch (\Exception $e) {
            $document = null;
        }

        return $app['twig']->render(
            'prod/Tooltip/TechnicalDatas.html.twig'
            , ['record'   => $record, 'document' => $document]
        );
    }

    public function displayFieldInfos(Application $app, $sbas_id, $field_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $field = \databox_field::get_instance($app, $databox, $field_id);

        return $app['twig']->render(
            'prod/Tooltip/DataboxField.html.twig'
            , ['field' => $field]
        );
    }

    public function displayDCESInfos(Application $app, $sbas_id, $field_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $field = \databox_field::get_instance($app, $databox, $field_id);

        return $app['twig']->render(
            'prod/Tooltip/DCESFieldInfo.html.twig'
            , ['field' => $field]
        );
    }

    public function displayMetaRestrictions(Application $app, $sbas_id, $field_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $field = \databox_field::get_instance($app, $databox, $field_id);

        return $app['twig']->render(
            'prod/Tooltip/DataboxFieldRestrictions.html.twig'
            , ['field' => $field]
        );
    }
}
