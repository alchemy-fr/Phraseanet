<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Symfony\Component\Yaml\Dumper as YamlDumper;

/**
 *
 * @package     caption
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class caption_record implements caption_interface, cache_cacheableInterface
{
    /**
     * @param Application $app
     * @param record_adapter[] $records
     * @return caption_record[]
     */
    public static function getMany(Application $app, databox $databox, array $records)
    {
        $query = "SELECT m.record_id as record_id, m.id as meta_id, m.id as id, s.id as structure_id,
                         m.value, m.VocabularyType, m.VocabularyId
                  FROM metadatas m INNER JOIN metadatas_structure s ON (s.id = m.meta_struct_id)
                  WHERE m.record_id IN (%s)
                  ORDER BY m.record_id, s.sorter ASC";

        $query = sprintf($query, implode(', ', array_map(function (record_adapter $record) {
            return $record->get_record_id();
        }, $records)));

        $statement = $databox->get_connection()->prepare($query);
        $statement->execute();
        $fields = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        $captions = array();

        $groupedFields = array();
        $row = reset($fields);

        while ($row !== false) {
            $recordId = (int) $row['record_id'];
            $record = $records[$recordId];

            $structureId = (int) $row['structure_id'];
            $databox_field = databox_field::get_instance($app, $databox, $structureId);

            if (! array_key_exists($recordId, $groupedFields)) {
                $groupedFields[$recordId] = array();
            }

            if (! array_key_exists($structureId, $groupedFields[$recordId])) {
                $groupedFields[$recordId][$structureId] = array();
            }

            $caption_field_values = caption_Field_Value::fromData($app, $databox_field, $record, $row);
            $groupedFields[$recordId][$structureId][] = $caption_field_values;

            $row = next($fields);
        }

        foreach ($records as $record) {
            $caption = new self($app, $record, $databox);
            $fields = array();


            foreach ($groupedFields[$record->get_record_id()] as $structureId => $structureFields) {
                $databox_field = databox_field::get_instance($app, $databox, $structureId);
                $caption_field = new caption_field($app, $databox_field, $record, array(), false);

                if (! $caption_field->is_multi() && ! empty($structureFields)) {
                    $field = reset($structureFields);
                    $structureFields = array($field->getId() => $field);
                }

                $caption_field->set_values($structureFields);

                $fields[] = $caption_field;
            }

            $caption->fields = $fields;
            $captions[] = $caption;
        }

        return $captions;
    }

    /**
     * @param caption_record $caption_record
     * @param $fields
     */
    protected static function mapFromFields(self $caption_record, $fields)
    {
        $metaIds = array_map(function ($row) {
            return $row['meta_id'];
        }, $fields);

        $caption_record->fields = caption_field::getMany($caption_record->app, $caption_record->record, $metaIds);
    }

    protected static function load(self $caption_record)
    {
        try {
            $fields = $caption_record->get_data_from_cache();
        } catch (\Exception $e) {
            $sql = "SELECT m.id as meta_id, s.id as structure_id
                    FROM metadatas m INNER JOIN metadatas_structure s ON (s.id = m.meta_struct_id)
                    WHERE m.record_id = :record_id
                    ORDER BY s.sorter ASC";

            $stmt = $caption_record->databox->get_connection()->prepare($sql);
            $stmt->execute(array(':record_id' => $caption_record->record->get_record_id()));
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($fields) {
                $caption_record->set_data_to_cache($fields);
            }
        }

        if ($fields) {
            self::mapFromFields($caption_record, $fields);
        }
    }

    /**
     *
     * @var caption_field[]
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
    protected $databox;
    protected $app;

    const SERIALIZE_XML = 'xml';
    const SERIALIZE_YAML = 'yaml';
    const SERIALIZE_JSON = 'json';

    /**
     *
     * @param Application      $app
     * @param record_Interface $record
     * @param databox          $databox
     *
     * @return caption_record
     */
    public function __construct(Application $app, record_Interface $record, databox $databox)
    {
        $this->app = $app;
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
            case self::SERIALIZE_JSON:
                return $this->serializeJSON( ! ! $includeBusinessFields);
                break;
            default:
                throw new \Exception(sprintf('Unknown format %s', $format));
                break;
        }
    }

    protected function serializeYAML($includeBusinessFields)
    {
        $dumper = new YamlDumper();

        return $dumper->dump($this->toArray($includeBusinessFields), 3);
    }

    protected function serializeJSON($includeBusinessFields)
    {
        return \p4string::jsonencode($this->toArray($includeBusinessFields));
    }

    protected function toArray($includeBusinessFields)
    {
        $buffer = array();

        foreach ($this->get_fields(array(), $includeBusinessFields) as $field) {
            $vi = $field->get_values();

            if ($field->is_multi()) {
                $buffer[$field->get_name()] = array();
                foreach ($vi as $value) {
                    $val = $value->getValue();
                    $buffer[$field->get_name()][] = ctype_digit($val) ? (int) $val : $this->sanitizeSerializedValue($val);
                }
            } else {
                $value = array_pop($vi);
                $val = $value->getValue();
                $buffer[$field->get_name()] = ctype_digit($val) ? (int) $val : $this->sanitizeSerializedValue($val);
            }
        }

        return array('record' => array('description' => $buffer));
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
                $elem->appendChild($dom_doc->createTextNode($this->sanitizeSerializedValue($value->getValue())));
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

    private function sanitizeSerializedValue($value)
    {
        return str_replace(array(
            "\x00", //null
            "\x01", //start heading
            "\x02", //start text
            "\x03", //end of text
            "\x04", //end of transmission
            "\x05", //enquiry
            "\x06", //acknowledge
            "\x07", //bell
            "\x08", //backspace
            "\x0C", //new page
            "\x0E", //shift out
            "\x0F", //shift in
            "\x10", //data link escape
            "\x11", //dc 1
            "\x12", //dc 2
            "\x13", //dc 3
            "\x14", //dc 4
            "\x15", //negative ack
            "\x16", //synchronous idle
            "\x17", //end of trans block
            "\x18", //cancel
            "\x19", //end of medium
            "\x1A", //substitute
            "\x1B", //escape
            "\x1C", //file separator
            "\x1D", //group sep
            "\x1E", //record sep
            "\x1F", //unit sep
        ), '', $value);
    }

    protected function retrieve_fields()
    {
        if (is_array($this->fields)) {
            return $this->fields;
        }

        self::load($this);

        return $this->fields;
    }

    public function get_record_id()
    {
        return $this->record->get_record_id();
    }

    /**
     *
     * @param array   $grep_fields
     * @param Boolean $IncludeBusiness
     *
     * @return caption_field[]
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

            $fields[$meta_struct_id] = $field;
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
        foreach ($this->get_fields(null, true) as $field) {
            if ($field->get_name() == $fieldname) {
                return $field;
            }
        }

        throw new \Exception('Field not found');
    }

    public function has_field($fieldname)
    {
        foreach ($this->get_fields(null, true) as $field) {
            if ($field->get_name() == $fieldname) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * @param  type          $label
     * @return caption_field
     */
    public function get_dc_field($label)
    {
        $fields = $this->retrieve_fields();

        if (null !== $field = $this->databox->get_meta_structure()->get_dces_field($label)) {
            if (isset($fields[$field->get_id()])) {
                return $fields[$field->get_id()];
            }
        }

        return null;
    }

    /**
     *
     * @param string                $highlight
     * @param array                 $grep_fields
     * @param SearchEngineInterface $searchEngine
     * @param Boolean               $includeBusiness
     *
     * @return caption_field[]
     */
    public function get_highlight_fields($highlight = '', Array $grep_fields = null, SearchEngineInterface $searchEngine = null, $includeBusiness = false)
    {
        $fields = array();

        foreach ($this->get_fields($grep_fields, $includeBusiness) as $meta_struct_id => $field) {
            $values = array();

            foreach ($field->get_values() as $metaId => $v) {
                $values[$metaId] = array(
                    'value' => $v->getValue(),
                    'from_thesaurus' => $highlight ? $v->isThesaurusValue() : false,
                    'qjs' => $v->getQjs(),
                 );
            }

            $fields[$field->get_name()] = array(
                'values'    => $values,
                'name'      => $field->get_name(),
                'label'     => $field->get_databox_field()->get_label($this->app['locale.I18n']),
                'separator' => $field->get_databox_field()->get_separator(),
                'sbas_id'   => $field->get_databox_field()->get_databox()->get_sbas_id()
            );
        }

        if ($searchEngine instanceof SearchEngineInterface) {
            $ret = $searchEngine->excerpt($highlight, $fields, $this->record);

            // sets highlighted value from search engine, highlighted values will now
            // be surrounded by [[em]][[/em]] tags
            if ($ret) {
                foreach ($fields as $key => $value) {
                    if (!isset($ret[$key])) {
                        continue;
                    }

                    foreach ($ret[$key] as $metaId => $newValue) {
                        $fields[$key]['values'][$metaId]['value'] = $newValue;
                    }
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
        return $this->record->get_databox()->get_data_from_cache($this->get_cache_key($option));
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
        return $this->record->get_databox()->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string        $option
     * @return caption_field
     */
    public function delete_data_from_cache($option = null)
    {
        $this->fields = null;

        return $this->record->get_databox()->delete_data_from_cache($this->get_cache_key($option));
    }
}
