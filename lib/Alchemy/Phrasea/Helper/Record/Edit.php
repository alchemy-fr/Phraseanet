<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\Record;

use Alchemy\Phrasea\Core;
use Alchemy\Phrasea\Helper\Record\Helper as RecordHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Edit Record Helper
 * This object handles /edit/ request and filters records that user can edit
 *
 * It prepares metadatas, databases structures.
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Edit extends RecordHelper
{
    /**
     *
     * @var Array
     */
    protected $javascript_fields;

    /**
     *
     * @var Array
     */
    protected $fields;

    /**
     *
     * @var Array
     */
    protected $javascript_status;

    /**
     *
     * @var Array
     */
    protected $javascript_sugg_values;

    /**
     *
     * @var Array
     */
    protected $javascript_elements = array();

    /**
     *
     * @var Array
     */
    protected $required_rights = array('canmodifrecord');

    /**
     *
     * @var boolean
     */
    protected $works_on_unique_sbas = true;

    /**
     *
     * @var type
     */
    protected $has_thesaurus = false;

    /**
     *
     * @param  \Alchemy\Phrasea\Core $core
     * @return Edit
     */
    public function __construct(Core $core, Request $Request)
    {
        parent::__construct($core, $Request);

        if ($this->is_single_grouping()) {
            $record = array_pop($this->selection->get_elements());
            $children = $record->get_children();
            foreach ($children as $child) {
                $this->selection->add_element($child);
            }
            $n = count($children);
            $this->elements_received = $this->selection->get_count() + $n - 1;
            $this->examinate_selection();
        }
        if ($this->is_possible()) {
            $this->generate_javascript_fields()
                ->generate_javascript_sugg_values()
                ->generate_javascript_status()
                ->generate_javascript_elements();
        }

        return $this;
    }

    public function propose_editing()
    {
        return $this;
    }

    public function has_thesaurus()
    {
        return $this->has_thesaurus;
    }

    /**
     * Return JSON data for UI
     *
     * @return String
     */
    public function get_javascript_elements_ids()
    {
        return json_encode(array_keys($this->javascript_elements));
    }

    /**
     * Return JSON data for UI
     *
     * @return String
     */
    public function get_javascript_elements()
    {
        return json_encode(array_values($this->javascript_elements));
    }

    /**
     * Return JSON data for UI
     *
     * @return String
     */
    public function get_javascript_sugg_values()
    {
        return json_encode($this->javascript_sugg_values);
    }

    /**
     * Return JSON data for UI
     *
     * @return String
     */
    public function get_javascript_status()
    {
        return json_encode($this->javascript_status);
    }

    /**
     * Return JSON data for UI
     *
     * @return String
     */
    public function get_javascript_fields()
    {
        return json_encode($this->javascript_fields);
    }

    /**
     * Return statusbit informations on database
     *
     * @return Array
     */
    public function get_status()
    {
        return $this->javascript_status;
    }

    /**
     * Return fields informations on database
     *
     * @return Array
     */
    public function get_fields()
    {
        return $this->fields;
    }

    /**
     * Generate data for JSON UI
     *
     * @return action_edit
     */
    protected function generate_javascript_elements()
    {
        $_lst = array();
        $user = $this->getCore()->getAuthenticatedUser();

        $twig = $this->getCore()->getTwig();

        $appbox = \appbox::get_instance($this->getCore());
        $databox = $appbox->get_databox($this->get_sbas_id());

        $databox_fields = array();
        foreach ($databox->get_meta_structure() as $field) {
            $databox_fields[$field->get_id()] = array(
                'dirty'          => false,
                'meta_struct_id' => $field->get_id(),
                'values'         => array()
            );
        }

        foreach ($this->selection as $record) {
            $indice = $record->get_number();
            $_lst[$indice] = array(
                'bid'         => $record->get_base_id(),
                'rid'         => $record->get_record_id(),
                'sselcont_id' => null,
                '_selected'   => false,
                'fields'      => $databox_fields
            );

            $_lst[$indice]['statbits'] = array();
            if ($user->ACL()->has_right_on_base($record->get_base_id(), 'chgstatus')) {
                foreach ($this->javascript_status as $n => $s) {
                    $tmp_val = substr(strrev($record->get_status()), $n, 1);
                    $_lst[$indice]['statbits'][$n]['value'] = ($tmp_val == '1') ? '1' : '0';
                    $_lst[$indice]['statbits'][$n]['dirty'] = false;
                }
            }
            $_lst[$indice]['originalname'] = '';

            $_lst[$indice]['originalname'] = $record->get_original_name();

            foreach ($record->get_caption()->get_fields(null, true) as $field) {
                $meta_struct_id = $field->get_meta_struct_id();
                if ( ! isset($this->javascript_fields[$meta_struct_id])) {
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

                $_lst[$indice]['fields'][$meta_struct_id] = array(
                    'dirty'          => false,
                    'meta_struct_id' => $meta_struct_id,
                    'values'         => $values
                );
            }

            $_lst[$indice]['subdefs'] = array();

            $thumbnail = $record->get_thumbnail();

            $_lst[$indice]['subdefs']['thumbnail'] = array(
                'url' => $thumbnail->get_url()
                , 'w'   => $thumbnail->get_width()
                , 'h'   => $thumbnail->get_height()
            );

            $_lst[$indice]['preview'] = $twig->render('common/preview.html.twig', array('record' => $record));

            $_lst[$indice]['type'] = $record->get_type();
        }

        $this->javascript_elements = $_lst;

        return $this;
    }

    /**
     * Generate data for JSON UI
     *
     * @return action_edit
     */
    protected function generate_javascript_sugg_values()
    {
        $done = array();
        $T_sgval = array();
        foreach ($this->selection as $record) {
            /* @var $record record_adapter */
            $base_id = $record->get_base_id();
            $record_id = $record->get_record_id();
            $databox = $record->get_databox();

            if (isset($done[$base_id]))
                continue;

            $T_sgval['b' . $base_id] = array();
            $collection = \collection::get_from_base_id($base_id);

            if ($sxe = simplexml_load_string($collection->get_prefs())) {
                $z = $sxe->xpath('/baseprefs/sugestedValues');

                if ( ! $z || ! is_array($z))
                    continue;

                foreach ($z[0] as $ki => $vi) { // les champs
                    $field = $databox->get_meta_structure()->get_element_by_name($ki);
                    if ( ! $field)
                        continue; // champ inconnu dans la structure ?
                    if ( ! $vi)
                        continue;

                    $T_sgval['b' . $base_id][$field->get_id()] = array();
                    foreach ($vi->value as $oneValue) { // les valeurs sug
                        $T_sgval['b' . $base_id][$field->get_id()][] =
                            (string) $oneValue;
                    }
                }
            }
            unset($collection);
            $done[$base_id] = true;
        }
        $this->javascript_sugg_values = $T_sgval;

        return $this;
    }

    /**
     * Generate data for JSON UI
     *
     * @return action_edit
     */
    protected function generate_javascript_status()
    {
        $_tstatbits = array();
        $user = $this->getCore()->getAuthenticatedUser();

        if ($user->ACL()->has_right('changestatus')) {
            $status = \databox_status::getDisplayStatus();
            if (isset($status[$this->get_sbas_id()])) {
                foreach ($status[$this->get_sbas_id()] as $n => $statbit) {
                    $_tstatbits[$n] = array();
                    $_tstatbits[$n]['label0'] = $statbit['labeloff'];
                    $_tstatbits[$n]['label1'] = $statbit['labelon'];
                    $_tstatbits[$n]['img_off'] = $statbit['img_off'];
                    $_tstatbits[$n]['img_on'] = $statbit['img_on'];
                    $_tstatbits[$n]['_value'] = 0;
                }
            }
        }

        $this->javascript_status = $_tstatbits;

        return $this;
    }

    /**
     * Generate data for JSON UI
     *
     * @return action_edit
     */
    protected function generate_javascript_fields()
    {
        $_tfields = $fields = array();

        $this->has_thesaurus = false;

        $appbox = \appbox::get_instance($this->getCore());
        $databox = $appbox->get_databox($this->get_sbas_id());
        $meta_struct = $databox->get_meta_structure();

        foreach ($meta_struct as $meta) {
            $fields[] = $meta;
            $this->generate_field($meta);
        }

        $this->fields = $fields;

        return $this;
    }

    protected function generate_field(\databox_field $meta)
    {
        $i = count($this->javascript_fields);

        $separator = $meta->get_separator();

        $datas = array(
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

        if (trim($meta->get_tbranch()) !== '')
            $this->has_thesaurus = true;

        $this->javascript_fields[$meta->get_id()] = $datas;
    }

    /**
     * Substitute Head file of groupings and save new Desc
     *
     * @param  http_request $request
     * @return action_edit
     */
    public function execute(Request $request)
    {
        $appbox = \appbox::get_instance($this->core);
        if ($request->get('act_option') == 'SAVEGRP' && $request->get('newrepresent')) {
            try {
                $reg_record = $this->get_grouping_head();
                $reg_sbas_id = $reg_record->get_sbas_id();

                $newsubdef_reg = new \record_adapter($reg_sbas_id, $request->get('newrepresent'));

                if ($newsubdef_reg->get_type() !== 'image')
                    throw new \Exception('A reg image must come from image data');

                foreach ($newsubdef_reg->get_subdefs() as $name => $value) {
                    if ( ! in_array($name, array('thumbnail', 'preview'))) {
                        continue;
                    }
                    $media = $this->core['mediavorus']->guess(new \SplFileInfo($value->get_pathfile()));
                    $reg_record->substitute_subdef($name, $media);
                }
            } catch (\Exception $e) {

            }
        }

        if ( ! is_array($request->get('mds'))) {
            return $this;
        }

        $sbas_id = (int) $request->get('sbid');
        $databox = $appbox->get_databox($sbas_id);
        $meta_struct = $databox->get_meta_structure();
        $write_edit_el = false;
        $date_obj = new \DateTime();
        foreach ($meta_struct->get_elements() as $meta_struct_el) {
            if ($meta_struct_el->get_tag() instanceof \Alchemy\Phrasea\Metadata\Tag\TfEditdate) {
                $write_edit_el = $meta_struct_el;
            }
        }

        $elements = $this->selection->get_elements();

        foreach ($request->get('mds') as $rec) {
            try {
                $record = $databox->get_record($rec['record_id']);
            } catch (\Exception $e) {
                continue;
            }

            $key = $record->get_serialize_key();

            if ( ! array_key_exists($key, $elements))
                continue;

            $statbits = $rec['status'];
            $editDirty = $rec['edit'];

            if ($editDirty == '0')
                $editDirty = false;
            else
                $editDirty = true;

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

                if ($field && ! $field->is_multi()) {
                    $meta_id = array_pop($field->get_values())->getId();
                }

                $metas = array(
                    array(
                        'meta_struct_id' => $write_edit_el->get_id()
                        , 'meta_id'        => $meta_id
                        , 'value'          => $date_obj->format('Y-m-d h:i:s')
                    )
                );

                $record->set_metadatas($metas, true);
            }

            $newstat = $record->get_status();
            $statbits = ltrim($statbits, 'x');
            if ( ! in_array($statbits, array('', 'null'))) {
                $mask_and = ltrim(str_replace(
                        array('x', '0', '1', 'z'), array('1', 'z', '0', '1'), $statbits), '0');
                if ($mask_and != '')
                    $newstat = \databox_status::operation_and_not($newstat, $mask_and);

                $mask_or = ltrim(str_replace('x', '0', $statbits), '0');

                if ($mask_or != '')
                    $newstat = \databox_status::operation_or($newstat, $mask_or);

                $record->set_binary_status($newstat);
            }

            $collection = \collection::get_from_base_id($record->get_base_id());
            $collection->reset_stamp($record->get_record_id());

            $record->write_metas();

            if ($statbits != '') {
                $appbox->get_session()
                    ->get_logger($record->get_databox())
                    ->log($record, \Session_Logger::EVENT_STATUS, '', '');
            }
            if ($editDirty) {
                $appbox->get_session()
                    ->get_logger($record->get_databox())
                    ->log($record, \Session_Logger::EVENT_EDIT, '', '');
            }
        }

        return $this;
    }
}
