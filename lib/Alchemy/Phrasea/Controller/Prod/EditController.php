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

use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\SubDefinitionSubstituerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\StoryCoverChangedEvent;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\Preset;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manipulator\PresetManipulator;
use Alchemy\Phrasea\Model\Repositories\PresetRepository;
use Alchemy\Phrasea\Vocabulary\ControlProvider\ControlProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EditController extends Controller
{
    use DataboxLoggerAware;
    use DispatcherAware;
    use SubDefinitionSubstituerAware;

    public function submitAction(Request $request)
    {
        $records = RecordsRequest::fromRequest(
            $this->app,
            $request,
            RecordsRequest::FLATTEN_YES_PRESERVE_STORIES,
            [\ACL::CANMODIFRECORD]
        );

        $thesaurus = false;
        $status = $ids = $elements = $suggValues = $fields = $JSFields = [];
        $databox = null;
        $databoxes = $records->databoxes();

        $multipleDataboxes = count($databoxes) > 1;

        if (1 === count($databoxes)) {
            /** @var \databox $databox */
            $databox = current($databoxes);

            // generate javascript fields
            foreach ($databox->get_meta_structure() as $meta) {
                /** @var \databox_field $meta */
                $fields[] = $meta;

                $separator = $meta->get_separator();

                /** @Ignore */
                $JSFields[$meta->get_id()] = [
                    'meta_struct_id'       => $meta->get_id(),
                    'name'                 => $meta->get_name(),
                    '_status'              => 0,
                    '_value'               => '',
                    '_sgval'               => [],
                    'required'             => $meta->is_required(),
                    /** @Ignore */
                    'label'                => $meta->get_label($this->app['locale']),
                    'readonly'             => $meta->is_readonly(),
                    'type'                 => $meta->get_type(),
                    'format'               => '',
                    'explain'              => '',
                    'tbranch'              => $meta->get_tbranch(),
                    'maxLength'            => $meta->get_tag()
                        ->getMaxLength(),
                    'minLength'            => $meta->get_tag()
                        ->getMinLength(),
                    'multi'                => $meta->is_multi(),
                    'separator'            => $separator,
                    'vocabularyControl'    => $meta->getVocabularyControl() ? $meta->getVocabularyControl()
                        ->getType() : null,
                    'vocabularyRestricted' => $meta->getVocabularyControl() ? $meta->isVocabularyRestricted()
                        : false,
                ];

                if (trim($meta->get_tbranch()) !== '') {
                    $thesaurus = true;
                }
            }

            // generate javascript sugg values
            foreach ($records->collections() as $collection) {
                $suggValues['b' . $collection->get_base_id()] = [];

                if ($sxe = simplexml_load_string($collection->get_prefs())) {
                    $z = $sxe->xpath('/baseprefs/sugestedValues');

                    if (!$z || !is_array($z)) {
                        continue;
                    }

                    foreach ($z[0] as $ki => $vi) { // les champs
                        $field = $databox->get_meta_structure()
                            ->get_element_by_name($ki);
                        if (!$field || !$vi) {
                            continue;
                        }

                        $suggValues['b' . $collection->get_base_id()][$field->get_id()] = [];

                        foreach ($vi->value as $oneValue) {
                            $suggValues['b' . $collection->get_base_id()][$field->get_id()][] = (string)$oneValue;
                        }
                    }
                }
                unset($collection);
            }

            // generate javascript status
            if ($this->getAclForUser()->has_right(\ACL::CHGSTATUS)) {
                $statusStructure = $databox->getStatusStructure();
                foreach ($statusStructure as $statbit) {
                    $bit = $statbit['bit'];

                    $status[$bit] = [];
                    $status[$bit]['label0'] = $statbit['labels_off_i18n'][$this->app['locale']];
                    $status[$bit]['label1'] = $statbit['labels_on_i18n'][$this->app['locale']];
                    $status[$bit]['img_off'] = $statbit['img_off'];
                    $status[$bit]['img_on'] = $statbit['img_on'];
                    $status[$bit]['_value'] = 0;
                }
            }

            // generate javascript elements
            $databox_fields = [];
            foreach ($databox->get_meta_structure() as $field) {
                $databox_fields[$field->get_id()] = [
                    'dirty'          => false,
                    'meta_struct_id' => $field->get_id(),
                    'values'         => []
                ];
            }

            /** @var \record_adapter $record */
            foreach ($records as $record) {
                $indice = $record->getNumber();
                $elements[$indice] = [
                    'bid'         => $record->getBaseId(),
                    'rid'         => $record->getRecordId(),
                    'sselcont_id' => null,
                    '_selected'   => false,
                    'fields'      => $databox_fields,
                ];

                $elements[$indice]['statbits'] = [];
                if ($this->getAclForUser()->has_right_on_base($record->getBaseId(), \ACL::CHGSTATUS)) {
                    foreach ($status as $n => $s) {
                        $tmp_val = substr(strrev($record->getStatus()), $n, 1);
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
                            'vocabularyType' => $type,
                        ];
                    }

                    $elements[$indice]['fields'][$meta_struct_id] = [
                        'dirty'          => false,
                        'meta_struct_id' => $meta_struct_id,
                        'values'         => $values,
                    ];
                }

                $elements[$indice]['subdefs'] = [];

                $thumbnail = $record->get_thumbnail();

                $elements[$indice]['subdefs']['thumbnail'] = [
                    'url' => (string)$thumbnail->get_url(),
                    'w'   => $thumbnail->get_width(),
                    'h'   => $thumbnail->get_height(),
                ];

                $elements[$indice]['preview'] = $this->render(
                    'common/preview.html.twig',
                    ['record' => $record]
                );

                $elements[$indice]['type'] = $record->getType();
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

        return $this->render('prod/actions/edit_default.html.twig', $params);
    }

    public function searchVocabularyAction(Request $request, $vocabulary) {
        $data = ['success' => false, 'message' => '', 'results' => []];

        $sbas_id = (int) $request->query->get('sbas_id');

        try {
            if ($sbas_id === 0) {
                throw new \Exception('Invalid sbas_id');
            }

            /** @var ControlProviderInterface $vocabularyProvider */
            $vocabularyProvider = $this->app['vocabularies'][strtolower($vocabulary)];
            $databox = $this->findDataboxById($sbas_id);
        } catch (\Exception $e) {
            $data['message'] = $this->app->trans('Vocabulary not found');

            return $this->app->json($data);
        }

        $query = $request->query->get('query');

        $results = $vocabularyProvider->find($query, $this->getAuthenticatedUser(), $databox);

        $list = [];

        foreach ($results as $term) {
            $list[] = [
                'id'      => $term->getId(),
                'context' => $term->getContext(),
                'value'   => $term->getValue(),
            ];
        }

        $data['success'] = true;
        $data['results'] = $list;

        return $this->app->json($data);
    }

    public function applyAction(Request $request) {

        $records = RecordsRequest::fromRequest($this->app, $request, RecordsRequest::FLATTEN_YES_PRESERVE_STORIES, [\ACL::CANMODIFRECORD]);

        $databoxes = $records->databoxes();
        if (count($databoxes) !== 1) {
            throw new \Exception('Unable to edit on multiple databoxes');
        }
        /** @var \databox $databox */
        $databox = reset($databoxes);

        if ($request->request->get('act_option') == 'SAVEGRP'
            && $request->request->get('newrepresent')
            && $records->isSingleStory()
        ) {
            try {
                $reg_record = $records->singleStory();

                $newsubdef_reg = new \record_adapter($this->app, $reg_record->getDataboxId(), $request->request->get('newrepresent'));

                $subdefChanged = false;
                foreach ($newsubdef_reg->get_subdefs() as $name => $value) {
                    if (!in_array($name, ['thumbnail', 'preview'])) {
                        continue;
                    }
                    if ($value->get_type() !== \media_subdef::TYPE_IMAGE) {
                        continue;
                    }

                    $media = $this->app->getMediaFromUri($value->getRealPath());
                    $this->getSubDefinitionSubstituer()->substituteSubdef($reg_record, $name, $media);
                    $this->getDataboxLogger($reg_record->getDatabox())->log(
                        $reg_record,
                        \Session_Logger::EVENT_SUBSTITUTE,
                        $name,
                        ''
                    );
                    $subdefChanged = true;
                }
                if($subdefChanged) {
                    $this->dispatch(RecordEvents::STORY_COVER_CHANGED, new StoryCoverChangedEvent($reg_record, $newsubdef_reg));
                    $this->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($reg_record));
                }
            } catch (\Exception $e) {

            }
        }

        if (!is_array($request->request->get('mds'))) {
            return $this->app->json(['message' => '', 'error'   => false]);
        }

        $elements = $records->toArray();

        foreach ($request->request->get('mds') as $rec) {
            try {
                $record = $databox->get_record($rec['record_id']);
            } catch (\Exception $e) {
                continue;
            }

            $key = $record->getId();

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
                $this->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($record));
            }

            $newstat = $record->getStatus();
            $statbits = ltrim($statbits, 'x');
            if (!in_array($statbits, ['', 'null'])) {
                $mask_and = ltrim(str_replace(['x', '0', '1', 'z'], ['1', 'z', '0', '1'], $statbits), '0');
                if ($mask_and != '') {
                    $newstat = \databox_status::operation_and_not($newstat, $mask_and);
                }

                $mask_or = ltrim(str_replace('x', '0', $statbits), '0');

                if ($mask_or != '') {
                    $newstat = \databox_status::operation_or($newstat, $mask_or);
                }

                $record->setStatus($newstat);
            }

            $record->write_metas();

            if ($statbits != '') {
                $this->getDataboxLogger($databox)
                    ->log($record, \Session_Logger::EVENT_STATUS, '', '');
            }
            if ($editDirty) {
                $this->getDataboxLogger($databox)
                    ->log($record, \Session_Logger::EVENT_EDIT, '', '');
            }
        }

        return $this->app->json(['success' => true]);
    }

    /**
     * @param int $preset_id
     * @return Preset
     */
    private function findPresetOr404($preset_id)
    {
        if (null === $preset = $this->getPresetRepository()->find($preset_id)) {
            $this->app->abort(404, sprintf("Preset with id '%' could not be found", $preset_id));
        }

        return $preset;
    }

    /**
     * route GET "../prod/records/edit/presets/{preset_id}"
     *
     * @param int $preset_id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function presetsLoadAction($preset_id)
    {
        $ret = [];

        $preset = $this->findPresetOr404($preset_id);

        $fields = [];
        foreach ($preset->getData() as $field) {
            $fields[$field['name']] = $field['value'];
        }

        $ret['fields'] = $fields;

        return $this->app->json($ret);
    }

    /**
     * route GET "../prod/records/edit/presets"
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function presetsListAction(Request $request)
    {
        $sbas_id = $request->get('sbas_id');
        $user = $this->getAuthenticatedUser();

        $ret = [];

        $ret['html'] = $this->getPresetHTMLList($sbas_id, $user);

        return $this->app->json($ret);
    }

    /**
     * route DELETE "../prod/records/edit/presets/{preset_id}"
     *
     * @param int $preset_id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function presetsDeleteAction($preset_id)
    {
        $user = $this->getAuthenticatedUser();

        $ret = [];

        $preset = $this->findPresetOr404($preset_id);

        $sbas_id = $preset->getSbasId();
        $this->getPresetManipulator()->delete($preset);

        $ret['html'] = $this->getPresetHTMLList($sbas_id, $user);

        return $this->app->json($ret);
    }

    /**
     * route POST "../prod/records/edit/presets"
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function presetsSaveAction(Request $request)
    {
        $sbas_id = $request->get('sbas_id');
        $user = $this->getAuthenticatedUser();

        $ret = [];

        $this->getPresetManipulator()->create(
            $user,
            $sbas_id,
            $request->get('title'),
            $request->get('fields')
        );

        $ret['html'] = $this->getPresetHTMLList($sbas_id, $user);

        return $this->app->json($ret);
    }

    /**
     * render edit preset
     *
     * @param $sbasId
     * @param User $user
     * @return Response
     */
    private function getPresetHTMLList($sbasId, User &$user)
    {
        $presetRepo = $this->getPresetRepository();

        $data = [];
        /** @var Preset $preset */
        foreach ($presetRepo->findBy(['user' => $user, 'sbasId' => $sbasId], ['created' => 'asc']) as $preset) {
            $fields = $presetData = [];
            $d = $preset->getData();
            array_walk($d, function ($field) use (&$fields) {
                $fields[$field['name']] = $field['value'];
            });
            $presetData['id'] = $preset->getId();
            $presetData['title'] = $preset->getTitle();
            $presetData['fields'] = $fields;

            $data[] = $presetData;
        }

        return $this->render('thesaurus/presets.html.twig', ['presets' => $data]);
    }

    /**
     * @return PresetRepository
     */
    private function getPresetRepository()
    {
        return $this->app['repo.presets'];
    }

    /**
     * @return PresetManipulator
     */
    private function getPresetManipulator()
    {
        return $this->app['manipulator.preset'];
    }

}
