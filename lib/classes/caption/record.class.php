<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     caption
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class caption_record implements caption_interface, cache_cacheableInterface
{
    /**
     *
     * @var array
     */
    protected $fields;

    /**
     *
     * @var int
     */
    protected $sbas_id;

    /**
     *
     * @var record
     */
    protected $record;
    protected $dces_elements = array();
    protected $databox;

    const SERIALIZE_XML = 'xml';
    const SERIALIZE_YAML = 'yaml';

    /**
     *
     * @param  record_Interface $record
     * @param  databox          $databox
     * @return caption_record
     */
    public function __construct(record_Interface &$record, databox &$databox)
    {
        $this->sbas_id = $record->get_sbas_id();
        $this->record = $record;
        $this->databox = $databox;

        return $this;
    }

    public function serialize($format, $includeBusinessFields = false)
    {
        switch ($format) {
            case self::SERIALIZE_XML:
                return $this->serializeXML( ! ! $includeBusinessFields);
                break;
            case self::SERIALIZE_YAML:
                return $this->serializeYAML( ! ! $includeBusinessFields);
                break;
            default:
                throw new \Exception(sprintf('Unknown format %s', $format));
                break;
        }
    }

    protected function serializeYAML($includeBusinessFields)
    {
        $buffer = array();

        foreach ($this->get_fields(array(), $includeBusinessFields) as $field) {
            $vi = $field->get_values();

            if ($field->is_multi()) {
                $buffer[$field->get_name()] = array();
                foreach ($vi as $value) {
                    $val = $value->getValue();
                    $buffer[$field->get_name()][] = ctype_digit($val) ? (int) $val : $val;
                }
            } else {
                $value = array_pop($vi);
                $val = $value->getValue();
                $buffer[$field->get_name()] = ctype_digit($val) ? (int) $val : $val;
            }
        }

        $buffer = array('record' => array('description' => $buffer));

        $dumper = new Symfony\Component\Yaml\Dumper();

        return $dumper->dump($buffer, 3);
    }

    protected function serializeXML($includeBusinessFields)
    {
        $dom_doc = new DOMDocument('1.0', 'UTF-8');
        $dom_doc->formatOutput = true;
        $dom_doc->standalone = true;

        $record = $dom_doc->createElement('record');
        $record->setAttribute('record_id', $this->record->get_record_id());
        $dom_doc->appendChild($record);
        $description = $dom_doc->createElement('description');
        $record->appendChild($description);

        foreach ($this->get_fields(array(), $includeBusinessFields) as $field) {
            $values = $field->get_values();

            foreach ($values as $value) {
                $elem = $dom_doc->createElement($field->get_name());
                $elem->appendChild($dom_doc->createTextNode($value->getValue()));
                $elem->setAttribute('meta_id', $value->getId());
                $elem->setAttribute('meta_struct_id', $field->get_meta_struct_id());
                $description->appendChild($elem);
            }
        }

        $doc = $dom_doc->createElement('doc');

        $tc_datas = $this->record->get_technical_infos();

        foreach ($tc_datas as $key => $data) {
            $doc->setAttribute($key, $data);
        }

        $record->appendChild($doc);

        return $dom_doc->saveXML();
    }

    protected function retrieve_fields()
    {
        if (is_array($this->fields)) {
            return $this->fields;
        }

        $fields = array();
        try {
            $fields = $this->get_data_from_cache();
        } catch (Exception $e) {
            $sql = "SELECT m.id as meta_id, s.id as structure_id
          FROM metadatas m, metadatas_structure s
          WHERE m.record_id = :record_id AND s.id = m.meta_struct_id";
            $stmt = $this->databox->get_connection()->prepare($sql);
            $stmt->execute(array(':record_id' => $this->record->get_record_id()));
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $this->set_data_to_cache($fields);
        }

        $rec_fields = array();
        foreach ($fields as $row) {
            $databox_meta_struct = databox_field::get_instance($this->databox, $row['structure_id']);
            $metadata = new caption_field($databox_meta_struct, $this->record);

            $rec_fields[$databox_meta_struct->get_id()] = $metadata;
            $dces_element = $metadata->get_databox_field()->get_dces_element();
            if ($dces_element instanceof databox_Field_DCESAbstract) {
                $this->dces_elements[$dces_element->get_label()] = $databox_meta_struct->get_id();
            }
        }
        $this->fields = $rec_fields;

        return $this->fields;
    }

    /**
     *
     * @param  array $grep_fields
     * @return array
     */
    public function get_fields(Array $grep_fields = null, $IncludeBusiness = false)
    {
        $fields = array();

        foreach ($this->retrieve_fields() as $meta_struct_id => $field) {
            if ($grep_fields && ! in_array($field->get_name(), $grep_fields)) {
                continue;
            }

            if ($field->get_databox_field()->isBusiness() === true && ! $IncludeBusiness) {
                continue;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     *
     * @param  type           $fieldname
     * @return \caption_field
     * @throws \Exception
     */
    public function get_field($fieldname)
    {
        foreach ($this->get_fields(null, true) as $meta_struct_id => $field) {
            if ($field->get_name() == $fieldname) {
                return $field;
            }
        }

        throw new \Exception('Field not found');
    }

    /**
     *
     * @param  type          $label
     * @return caption_field
     */
    public function get_dc_field($label)
    {
        $fields = $this->get_fields();
        if (isset($this->dces_elements[$label])) {
            return $fields[$this->dces_elements[$label]];
        }

        return null;
    }

    /**
     *
     * @param  string               $highlight
     * @param  array                $grep_fields
     * @param  searchEngine_adapter $searchEngine
     * @return array
     */
    public function get_highlight_fields($highlight = '', Array $grep_fields = null, searchEngine_adapter $searchEngine = null, $includeBusiness = false)
    {
        return $this->highlight_fields($highlight, $grep_fields, $searchEngine, $includeBusiness);
    }

    /**
     * @todo move this fun in caption_field object
     * @param  string               $highlight
     * @param  array                $grep_fields
     * @param  searchEngine_adapter $searchEngine
     * @return array
     */
    protected function highlight_fields($highlight, Array $grep_fields = null, searchEngine_adapter $searchEngine = null, $includeBusiness = false)
    {
        $fields = array();

        foreach ($this->get_fields($grep_fields, $includeBusiness) as $meta_struct_id => $field) {

            $value = preg_replace(
                "(([^']{1})((https?|file):((/{2,4})|(\\{2,4}))[\w:#%/;$()~_?/\-=\\\.&]*)([^']{1}))"
                , '$1 $2 <a title="' . _('Open the URL in a new window') . '" class="ui-icon ui-icon-extlink" href="$2" style="display:inline;padding:2px 5px;margin:0 4px 0 2px;" target="_blank"> &nbsp;</a>$7'
                , $field->highlight_thesaurus()
            );

            $fields[$field->get_name()] = array(
                'value'     => $value
                , 'separator' => $field->get_databox_field()->get_separator()
            );
        }

        if ($searchEngine instanceof searchEngine_adapter) {
            $ret = $searchEngine->build_excerpt($highlight, $fields, $this->record);

            if ($ret) {
                $n = 0;

                foreach ($fields as $key => $value) {
                    if ( ! isset($fields[$key]))
                        continue;

                    //if(strpos($fields[$key]['value'], '<a ') === false)
                    $fields[$key]['value'] = $ret[$n];

                    $n ++;
                }
            }
        }

        return $fields;
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string $option
     * @return string
     */
    public function get_cache_key($option = null)
    {
        return 'caption_' . $this->record->get_serialize_key() . ($option ? '_' . $option : '');
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string $option
     * @return mixed
     */
    public function get_data_from_cache($option = null)
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $databox = $appbox->get_databox($this->record->get_sbas_id());

        return $databox->get_data_from_cache($this->get_cache_key($option));
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  mixed         $value
     * @param  string        $option
     * @param  int           $duration
     * @return caption_field
     */
    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $databox = $appbox->get_databox($this->record->get_sbas_id());

        return $databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string        $option
     * @return caption_field
     */
    public function delete_data_from_cache($option = null)
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $databox = $appbox->get_databox($this->record->get_sbas_id());
        $this->fields = null;

        return $databox->delete_data_from_cache($this->get_cache_key($option));
    }
}
