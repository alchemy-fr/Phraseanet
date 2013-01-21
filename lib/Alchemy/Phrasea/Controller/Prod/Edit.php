<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Vocabulary\Controller as VocabularyController;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Metadata\Tag\TfEditdate;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Edit implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']
                ->requireNotGuest()
                ->requireRight('modifyrecord');
        });

        $controllers->post('/', function(Application $app, Request $request) {

            $records = RecordsRequest::fromRequest($app, $request, true, array('canmodifrecord'));

            $thesaurus = $multipleDataboxes = false;
            $status = $ids = $elements = $suggValues =
                $fields = $JSFields = array();

            $databox = null;

            $multipleDataboxes = count($records->databoxes()) > 1;

            if (!$multipleDataboxes) {
                $databoxes = $records->databoxes();
                $databox = array_pop($databoxes);

                /**
                 * generate javascript fields
                 */
                foreach ($databox->get_meta_structure() as $meta) {
                    $fields[] = $meta;

                    $separator = $meta->get_separator();

                    $JSFields[$meta->get_id()] = array(
                        'meta_struct_id' => $meta->get_id()
                        , 'name'           => $meta->get_name()
                        , '_status'        => 0
                        , '_value'         => ''
                        , '_sgval'         => array()
                        , 'required'             => $meta->is_required()
                        , 'readonly'             => $meta->is_readonly()
                        , 'type'                 => $meta->get_type()
                        , 'format'               => ''
                        , 'explain'              => ''
                        , 'tbranch'              => $meta->get_tbranch()
                        , 'maxLength'            => $meta->get_tag()->getMaxLength()
                        , 'minLength'            => $meta->get_tag()->getMinLength()
                        , 'multi'                => $meta->is_multi()
                        , 'separator'            => $separator
                        , 'vocabularyControl'    => $meta->getVocabularyControl() ? $meta->getVocabularyControl()->getType() : null
                        , 'vocabularyRestricted' => $meta->getVocabularyControl() ? $meta->isVocabularyRestricted() : false
                    );

                    if (trim($meta->get_tbranch()) !== '') {
                        $thesaurus = true;
                    }
                }


                /**
                 * generate javascript sugg values
                 */
                foreach ($records->collections() as $collection) {
                    /* @var $record record_adapter */

                    $suggValues['b' . $collection->get_base_id()] = array();

                    if ($sxe = simplexml_load_string($collection->get_prefs())) {
                        $z = $sxe->xpath('/baseprefs/sugestedValues');

                        if (!$z || !is_array($z)) {
                            continue;
                        }

                        foreach ($z[0] as $ki => $vi) { // les champs
                            $field = $databox->get_meta_structure()->get_element_by_name($ki);
                            if (!$field || !$vi) {
                                continue;
                            }

                            $suggValues['b' . $collection->get_base_id()][$field->get_id()] = array();

                            foreach ($vi->value as $oneValue) {
                                $suggValues['b' . $collection->get_base_id()][$field->get_id()][] = (string) $oneValue;
                            }
                        }
                    }
                    unset($collection);
                }


                /**
                 * generate javascript status
                 */
                if ($app['phraseanet.user']->ACL()->has_right('changestatus')) {
                    $dbstatus = \databox_status::getDisplayStatus($app);
                    if (isset($dbstatus[$databox->get_sbas_id()])) {
                        foreach ($dbstatus[$databox->get_sbas_id()] as $n => $statbit) {
                            $status[$n] = array();
                            $status[$n]['label0'] = $statbit['labeloff'];
                            $status[$n]['label1'] = $statbit['labelon'];
                            $status[$n]['img_off'] = $statbit['img_off'];
                            $status[$n]['img_on'] = $statbit['img_on'];
                            $status[$n]['_value'] = 0;
                        }
                    }
                }

                /**
                 * generate javascript elements
                 */
                foreach ($databox->get_meta_structure() as $field) {
                    $databox_fields[$field->get_id()] = array(
                        'dirty'          => false,
                        'meta_struct_id' => $field->get_id(),
                        'values'         => array()
                    );
                }

                foreach ($records as $record) {
                    $indice = $record->get_number();
                    $elements[$indice] = array(
                        'bid'         => $record->get_base_id(),
                        'rid'         => $record->get_record_id(),
                        'sselcont_id' => null,
                        '_selected'   => false,
                        'fields'      => $databox_fields
                    );

                    $elements[$indice]['statbits'] = array();
                    if ($app['phraseanet.user']->ACL()->has_right_on_base($record->get_base_id(), 'chgstatus')) {
                        foreach ($status as $n => $s) {
                            $tmp_val = substr(strrev($record->get_status()), $n, 1);
                            $elements[$indice]['statbits'][$n]['value'] = ($tmp_val == '1') ? '1' : '0';
                            $elements[$indice]['statbits'][$n]['dirty'] = false;
                        }
                    }

                    $elements[$indice]['originalname'] = $record->get_original_name();

                    foreach ($record->get_caption()->get_fields(null, true) as $field) {
                        $meta_struct_id = $field->get_meta_struct_id();
                        if (!isset($JSFields[$meta_struct_id])) {
                            continue;
                        }

                        $values = array();
                        foreach ($field->get_values() as $value) {
                            $type = $id = null;

                            if ($value->getVocabularyType()) {
                                $type = $value->getVocabularyType()->getType();
                                $id = $value->getVocabularyId();
                            }

                            $values[$value->getId()] = array(
                                'meta_id'        => $value->getId(),
                                'value'          => $value->getValue(),
                                'vocabularyId'   => $id,
                                'vocabularyType' => $type
                            );
                        }

                        $elements[$indice]['fields'][$meta_struct_id] = array(
                            'dirty'          => false,
                            'meta_struct_id' => $meta_struct_id,
                            'values'         => $values
                        );
                    }

                    $elements[$indice]['subdefs'] = array();

                    $thumbnail = $record->get_thumbnail();

                    $elements[$indice]['subdefs']['thumbnail'] = array(
                        'url' => $thumbnail->get_url()
                        , 'w'   => $thumbnail->get_width()
                        , 'h'   => $thumbnail->get_height()
                    );

                    $elements[$indice]['preview'] = $app['twig']->render('common/preview.html.twig', array('record' => $record));

                    $elements[$indice]['type'] = $record->get_type();
                }
            }

            $params = array(
                'multipleDataboxes' => $multipleDataboxes,
                'recordsRequest'    => $records,
                'databox'           => $databox,
                'JSonStatus'        => json_encode($status),
                'JSonRecords'       => json_encode($elements),
                'JSonFields'        => json_encode($JSFields),
                'JSonIds'           => json_encode(array_keys($elements)),
                'status'            => $status,
                'fields'            => $fields,
                'JSonSuggValues'    => json_encode($suggValues),
                'thesaurus'         => $thesaurus,
            );

            return $app['twig']->render('prod/actions/edit_default.html.twig', $params);
        });

        $controllers->get('/vocabulary/{vocabulary}/', function(Application $app, Request $request, $vocabulary) {
            $datas = array('success' => false, 'message' => '', 'results' => array());

            $sbas_id = (int) $request->query->get('sbas_id');

            try {
                if ($sbas_id === 0) {
                    throw new \Exception('Invalid sbas_id');
                }

                $VC = VocabularyController::get($app, $vocabulary);
                $databox = $app['phraseanet.appbox']->get_databox($sbas_id);
            } catch (\Exception $e) {
                $datas['message'] = _('Vocabulary not found');

                return $app->json($datas);
            }

            $query = $request->query->get('query');

            $results = $VC->find($query, $app['phraseanet.user'], $databox);

            $list = array();

            foreach ($results as $Term) {
                /* @var $Term \Alchemy\Phrasea\Vocabulary\Term */
                $list[] = array(
                    'id'      => $Term->getId(),
                    'context' => $Term->getContext(),
                    'value'   => $Term->getValue(),
                );
            }

            $datas['success'] = true;
            $datas['results'] = $list;

            return $app->json($datas);
        });

        $controllers->post('/apply/', function(Application $app, Request $request) {

            $records = RecordsRequest::fromRequest($app, $request, true, array('canmodifrecord'));

            if (count($records->databoxes()) !== 1) {
                throw new \Exception('Unable to edit on multiple databoxes');
            }

            if ($request->request->get('act_option') == 'SAVEGRP'
                && $request->request->get('newrepresent')
                && $records->isSingleStory()) {
                try {
                    $reg_record = $records->singleStory();

                    $newsubdef_reg = new \record_adapter($app, $reg_record->get_sbas_id(), $request->request->get('newrepresent'));

                    if ($newsubdef_reg->get_type() !== 'image') {
                        throw new \Exception('A reg image must come from image data');
                    }

                    foreach ($newsubdef_reg->get_subdefs() as $name => $value) {
                        if (!in_array($name, array('thumbnail', 'preview'))) {
                            continue;
                        }
                        $media = $app['mediavorus']->guess($value->get_pathfile());
                        $reg_record->substitute_subdef($name, $media, $app);
                    }
                } catch (\Exception $e) {

                }
            }

            if (!is_array($request->request->get('mds'))) {
                return $app->json(array('message' => '', 'error'   => false));
            }

            $databoxes = $records->databoxes();
            $databox = array_pop($databoxes);

            $meta_struct = $databox->get_meta_structure();
            $write_edit_el = false;
            $date_obj = new \DateTime();
            foreach ($meta_struct->get_elements() as $meta_struct_el) {
                if ($meta_struct_el->get_tag() instanceof TfEditdate) {
                    $write_edit_el = $meta_struct_el;
                }
            }

            $elements = $records->toArray();

            foreach ($request->request->get('mds') as $rec) {
                try {
                    $record = $databox->get_record($rec['record_id']);
                } catch (\Exception $e) {
                    continue;
                }

                $key = $record->get_serialize_key();

                if (!array_key_exists($key, $elements)) {
                    continue;
                }

                $statbits = $rec['status'];
                $editDirty = $rec['edit'];

                if ($editDirty == '0') {
                    $editDirty = false;
                } else {
                    $editDirty = true;
                }

                if (is_array($rec['metadatas'])) {
                    $record->set_metadatas($rec['metadatas']);
                }

                /**
                 * todo : this should not work
                 */
                if ($write_edit_el instanceof \databox_field) {
                    $fields = $record->get_caption()->get_fields(array($write_edit_el->get_name()), true);
                    $field = array_pop($fields);

                    $meta_id = null;

                    if ($field && !$field->is_multi()) {
                        $values = $field->get_values();
                        $meta_id = array_pop($values)->getId();
                    }

                    $metas = array(
                        array(
                            'meta_struct_id' => $write_edit_el->get_id(),
                            'meta_id'        => $meta_id,
                            'value'          => $date_obj->format('Y-m-d h:i:s'),
                        )
                    );

                    $record->set_metadatas($metas, true);
                }

                $newstat = $record->get_status();
                $statbits = ltrim($statbits, 'x');
                if (!in_array($statbits, array('', 'null'))) {
                    $mask_and = ltrim(str_replace(array('x', '0', '1', 'z'), array('1', 'z', '0', '1'), $statbits), '0');
                    if ($mask_and != '') {
                        $newstat = \databox_status::operation_and_not($app, $newstat, $mask_and);
                    }

                    $mask_or = ltrim(str_replace('x', '0', $statbits), '0');

                    if ($mask_or != '') {
                        $newstat = \databox_status::operation_or($app, $newstat, $mask_or);
                    }

                    $record->set_binary_status($newstat);
                }

                $record
                    ->get_collection()
                    ->reset_stamp($record->get_record_id())
                    ->write_metas();

                if ($statbits != '') {
                    $app['phraseanet.logger']($record->get_databox())
                        ->log($record, \Session_Logger::EVENT_STATUS, '', '');
                }
                if ($editDirty) {
                    $app['phraseanet.logger']($record->get_databox())
                        ->log($record, \Session_Logger::EVENT_EDIT, '', '');
                }
            }



            return $app['twig']->render('prod/actions/edit_default.html.twig', array('edit'    => $editing, 'message' => ''));
        });

        return $controllers;
    }
}
