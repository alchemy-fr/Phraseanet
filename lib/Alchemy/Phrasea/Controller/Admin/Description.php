<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Description implements ControllerProviderInterface
{

  public function connect(Application $app)
  {

    $controllers = new ControllerCollection();


    $controllers->post('/{sbas_id}/', function(Application $app, $sbas_id)
      {
        $Core = $app['Core'];
        $user = $Core->getAuthenticatedUser();

        $request = $app['request'];

        if (!$user->ACL()->has_right_on_sbas($sbas_id, 'bas_modify_struct'))
        {
          throw new \Exception_Forbidden('You are not allowed to access this zone');
        }

        $databox             = \databox::get_instance((int) $sbas_id);
        $fields              = $databox->get_meta_structure();
        $available_fields    = \databox::get_available_metadatas();
        $available_dc_fields = $databox->get_available_dcfields();


        $databox->get_connection()->beginTransaction();
        $error = false;
        try
        {
          if (is_array($request->get('field_ids')))
          {
            foreach ($request->get('field_ids') as $id)
            {
              try
              {
                $field = \databox_field::get_instance($databox, $id);

                $field->set_name($request->get('name_' . $id));
                $field->set_thumbtitle($request->get('thumbtitle_' . $id));
                $field->set_source($request->get('src_' . $id));
                $field->set_multi($request->get('multi_' . $id));
                $field->set_indexable($request->get('indexable_' . $id));
                $field->set_required($request->get('required_' . $id));
                $field->set_separator($request->get('separator_' . $id));
                $field->set_readonly($request->get('readonly_' . $id));
                $field->set_type($request->get('type_' . $id));
                $field->set_tbranch($request->get('tbranch_' . $id));
                $field->set_report($request->get('report_' . $id));

                $field->setVocabularyControl(null);
                $field->setVocabularyRestricted(false);

                try
                {
                  $vocabulary = \Alchemy\Phrasea\Vocabulary\Controller::get($request->get('vocabulary_' . $id));
                  $field->setVocabularyControl($vocabulary);
                  $field->setVocabularyRestricted($request->get('vocabularyrestricted_' . $id));
                }
                catch (\Exception $e)
                {

                }

                $dces_element = null;

                $class        = 'databox_Field_DCES_' . $request->get('dces_' . $id);
                if (class_exists($class))
                  $dces_element = new $class();

                $field->set_dces_element($dces_element);
                $field->save();

                if ($request->get('regname') == $field->get_id())
                {
                  $field->set_regname();
                }
                if ($request->get('regdate') == $field->get_id())
                {
                  $field->set_regdate();
                }
                if ($request->get('regdesc') == $field->get_id())
                {
                  $field->set_regdesc();
                }
              }
              catch (Exception $e)
              {
                continue;
              }
            }
          }

          if ($request->get('newfield'))
          {
            \databox_field::create($databox, $request->get('newfield'));
          }

          if (is_array($request->get('todelete_ids')))
          {
            foreach ($request->get('todelete_ids') as $id)
            {
              try
              {
                $field = \databox_field::get_instance($databox, $id);
                $field->delete();
              }
              catch (\Exception $e)
              {

              }
            }
          }
        }
        catch (\Exception $e)
        {
          $error = true;
        }

        if ($error)
          $databox->get_connection()->rollBack();
        else
          $databox->get_connection()->commit();

        return new RedirectResponse('/admin/databox/' . $sbas_id . '/description/');
      });

    $controllers->get('/{sbas_id}/', function(Application $app, $sbas_id)
      {

        $Core = $app['Core'];
        $user = $Core->getAuthenticatedUser();

        $request = $app['request'];

        if (!$user->ACL()->has_right_on_sbas($sbas_id, 'bas_modify_struct'))
        {
          throw new \Exception_Forbidden('You are not allowed to access this zone');
        }

        $databox             = \databox::get_instance((int) $sbas_id);
        $fields              = $databox->get_meta_structure();
        $available_fields    = \databox::get_available_metadatas();
        $available_dc_fields = $databox->get_available_dcfields();


        $params = array(
          'databox'             => $databox,
          'fields'              => $fields,
          'available_fields'    => $available_fields,
          'available_dc_fields' => $available_dc_fields,
          'vocabularies'        => \Alchemy\Phrasea\Vocabulary\Controller::getAvailable(),
        );

        return new Response($Core->getTwig()->render('admin/databox/doc_structure.twig', $params));
      });

    return $controllers;
  }

}
