<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Core\Event\RecordEvent\ChangeMetadataEvent;
use Alchemy\Phrasea\Core\Event\RecordEvent\ChangeStatusEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Vocabulary\Controller as VocabularyController;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Metadata\Tag\TfEditdate;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Edit implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.prod.edit'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']
                ->requireNotGuest()
                ->requireRight('modifyrecord');
        });

        $controllers->post('/', function (Application $app, Request $request) {

            $records = RecordsRequest::fromRequest($app, $request, RecordsRequest::FLATTEN_YES_PRESERVE_STORIES, ['canmodifrecord']);

            $thesaurus = false;
            $status = $ids = $elements = $suggValues =
                $fields = $JSFields = [];
            $databox = null;

            $multipleDataboxes = count($records->databoxes()) > 1;

            if (1 === count($records->databoxes())) {
                $databoxes = $records->databoxes();
                $databox = array_pop($databoxes);

                /**
                 * generate javascript fields
                 */
                foreach ($databox->get_meta_structure() as $meta) {
                    $fields[] = $meta;

                    $separator = $meta->get_separator();

                    /** @Ignore */
                    $JSFields[$meta->get_id()] = [
                        'meta_struct_id' => $meta->get_id(),
                        'name'           => $meta->get_name(),
                        '_status'        => 0,
                        '_value'         => '',
                        '_sgval'         => [],
                        'required'             => $meta->is_required(),
                        /** @Ignore */
                        'label'                => $meta->get_label($app['locale']),
                        'readonly'             => $meta->is_readonly(),
                        'type'                 => $meta->get_type(),
                        'format'               => '',
                        'explain'              => '',
                        'tbranch'              => $meta->get_tbranch(),
                        'maxLength'            => $meta->get_tag()->getMaxLength(),
                        'minLength'            => $meta->get_tag()->getMinLength(),
                        'multi'                => $meta->is_multi(),
                        'separator'            => $separator,
                        'vocabularyControl'    => $meta->getVocabularyControl() ? $meta->getVocabularyControl()->getType() : null,
                        'vocabularyRestricted' => $meta->getVocabularyControl() ? $meta->isVocabularyRestricted() : false,
                    ];

                    if (trim($meta->get_tbranch()) !== '') {
                        $thesaurus = true;
                    }
                }

                /**
                 * generate javascript sugg values
                 */
                foreach ($records->collections() as $collection) {
                    /* @var $record record_adapter */

                    $suggValues['b' . $collection->get_base_id()] = [];

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

                            $suggValues['b' . $collection->get_base_id()][$field->get_id()] = [];

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
                if ($app['acl']->get($app['authentication']->getUser())->has_right('changestatus')) {
                    $dbstatus = \databox_status::getDisplayStatus($app);
                    if (isset($dbstatus[$databox->get_sbas_id()])) {
                        foreach ($dbstatus[$databox->get_sbas_id()] as $n => $statbit) {
                            $status[$n] = [];
                            $status[$n]['label0'] = $statbit['labels_off_i18n'][$app['locale']];
                            $status[$n]['label1'] = $statbit['labels_on_i18n'][$app['locale']];
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
                    $databox_fields[$field->get_id()] = [
                        'dirty'          => false,
                        'meta_struct_id' => $field->get_id(),
                        'values'         => []
                    ];
                }

                foreach ($records as $record) {
                    $indice = $record->get_number();
                    $elements[$indice] = [
                        'bid'         => $record->get_base_id(),
                        'rid'         => $record->get_record_id(),
                        'sselcont_id' => null,
                        '_selected'   => false,
                        'fields'      => $databox_fields
                    ];

                    $elements[$indice]['statbits'] = [];
                    if ($app['acl']->get($app['authentication']->getUser())->has_right_on_base($record->get_base_id(), 'chgstatus')) {
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

                        $values = [];
                        foreach ($field->get_values() as $value) {
                            $type = $id = null;

                            if ($value->getVocabularyType()) {
                                $type = $value->getVocabularyType()->getType();
                                $id = $value->getVocabularyId();
                            }

                            $values[$value->getId()] = [
                                'meta_id'        => $value->getId(),
                                'value'          => $value->getValue(),
                                'vocabularyId'   => $id,
                                'vocabularyType' => $type
                            ];
                        }

                        $elements[$indice]['fields'][$meta_struct_id] = [
                            'dirty'          => false,
                            'meta_struct_id' => $meta_struct_id,
                            'values'         => $values
                        ];
                    }

                    $elements[$indice]['subdefs'] = [];

                    $thumbnail = $record->get_thumbnail();

                    $elements[$indice]['subdefs']['thumbnail'] = [
                        'url' => (string) $thumbnail->get_url()
                        , 'w'   => $thumbnail->get_width()
                        , 'h'   => $thumbnail->get_height()
                    ];

                    $elements[$indice]['preview'] = $app['twig']->render('common/preview.html.twig', ['record' => $record]);

                    $elements[$indice]['type'] = $record->get_type();
                }
            }

            $params = [
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
            ];

            return $app['twig']->render('prod/actions/edit_default.html.twig', $params);
        });

        $controllers->get('/vocabulary/{vocabulary}/', function (Application $app, Request $request, $vocabulary) {
            $datas = ['success' => false, 'message' => '', 'results' => []];

            $sbas_id = (int) $request->query->get('sbas_id');

            try {
                if ($sbas_id === 0) {
                    throw new \Exception('Invalid sbas_id');
                }

                $VC = VocabularyController::get($app, $vocabulary);
                $databox = $app['phraseanet.appbox']->get_databox($sbas_id);
            } catch (\Exception $e) {
                $datas['message'] = $app->trans('Vocabulary not found');

                return $app->json($datas);
            }

            $query = $request->query->get('query');

            $results = $VC->find($query, $app['authentication']->getUser(), $databox);

            $list = [];

            foreach ($results as $Term) {
                /* @var $Term \Alchemy\Phrasea\Vocabulary\Term */
                $list[] = [
                    'id'      => $Term->getId(),
                    'context' => $Term->getContext(),
                    'value'   => $Term->getValue(),
                ];
            }

            $datas['success'] = true;
            $datas['results'] = $list;

            return $app->json($datas);
        });

        $controllers->post('/apply/', function (Application $app, Request $request) {

            $records = RecordsRequest::fromRequest($app, $request, RecordsRequest::FLATTEN_YES_PRESERVE_STORIES, ['canmodifrecord']);

            if (count($records->databoxes()) !== 1) {
                throw new \Exception('Unable to edit on multiple databoxes');
            }

            if ($request->request->get('act_option') == 'SAVEGRP'
                && $request->request->get('newrepresent')
                && $records->isSingleStory()) {
                try {
                    $reg_record = $records->singleStory();

                    $newsubdef_reg = new \record_adapter($app, $reg_record->get_sbas_id(), $request->request->get('newrepresent'));

                    foreach ($newsubdef_reg->get_subdefs() as $name => $value) {
                        if (!in_array($name, ['thumbnail', 'preview'])) {
                            continue;
                        }
                        if ($value->get_type() !== \media_subdef::TYPE_IMAGE) {
                            continue;
                        }

                        $media = $app['mediavorus']->guess($value->get_pathfile());
                        $app['subdef.substituer']->substitute($reg_record, $name, $media);
                        $app['phraseanet.logger']($reg_record->get_databox())->log(
                            $reg_record,
                            \Session_Logger::EVENT_SUBSTITUTE,
                            $name == 'document' ? 'HD' : $name,
                            ''
                        );
                    }
                } catch (\Exception $e) {

                }
            }

            if (!is_array($request->request->get('mds'))) {
                return $app->json(['message' => '', 'error'   => false]);
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

                if (isset($rec['metadatas']) && is_array($rec['metadatas'])) {
                    $record->set_metadatas($rec['metadatas']);

                    $app['dispatcher']->dispatch(PhraseaEvents::RECORD_CHANGE_METADATA, new ChangeMetadataEvent($record));
                }

                /**
                 * todo : this should not work
                 */
                if ($write_edit_el instanceof \databox_field) {
                    $fields = $record->get_caption()->get_fields([$write_edit_el->get_name()], true);
                    $field = array_pop($fields);

                    $meta_id = null;

                    if ($field && !$field->is_multi()) {
                        $values = $field->get_values();
                        $meta_id = array_pop($values)->getId();
                    }

                    $metas = [
                        [
                            'meta_struct_id' => $write_edit_el->get_id(),
                            'meta_id'        => $meta_id,
                            'value'          => $date_obj->format('Y-m-d h:i:s'),
                        ]
                    ];

                    $record->set_metadatas($metas, true);

                    $app['dispatcher']->dispatch(PhraseaEvents::RECORD_CHANGE_METADATA, new ChangeMetadataEvent($record));
                }

                $newstat = $record->get_status();
                $statbits = ltrim($statbits, 'x');
                if (!in_array($statbits, ['', 'null'])) {
                    $mask_and = ltrim(str_replace(['x', '0', '1', 'z'], ['1', 'z', '0', '1'], $statbits), '0');
                    if ($mask_and != '') {
                        $newstat = \databox_status::operation_and_not($app, $newstat, $mask_and);
                    }

                    $mask_or = ltrim(str_replace('x', '0', $statbits), '0');

                    if ($mask_or != '') {
                        $newstat = \databox_status::operation_or($app, $newstat, $mask_or);
                    }

                    $record->set_binary_status($newstat);

                    $app['dispatcher']->dispatch(PhraseaEvents::RECORD_CHANGE_STATUS, new ChangeStatusEvent($record));
                }

                $record
                    ->write_metas()
                    ->get_collection()
                    ->reset_stamp($record->get_record_id());

                if ($statbits != '') {
                    $app['phraseanet.logger']($record->get_databox())
                        ->log($record, \Session_Logger::EVENT_STATUS, '', '');
                }
                if ($editDirty) {
                    $app['phraseanet.logger']($record->get_databox())
                        ->log($record, \Session_Logger::EVENT_EDIT, '', '');
                }
            }

            return $app->json(['success' => true]);
        });

        return $controllers;
    }
}
