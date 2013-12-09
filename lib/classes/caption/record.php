<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Symfony\Component\Yaml\Dumper as YamlDumper;

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
        $buffer = [];

        foreach ($this->get_fields([], $includeBusinessFields) as $field) {
            $vi = $field->get_values();

            if ($field->is_multi()) {
                $buffer[$field->get_name()] = [];
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

        return ['record' => ['description' => $buffer]];
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

        foreach ($this->get_fields([], $includeBusinessFields) as $field) {
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
        return str_replace([
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
        ], '', $value);
    }

    protected function retrieve_fields()
    {
        if (is_array($this->fields)) {
            return $this->fields;
        }

        $fields = [];
        try {
            $fields = $this->get_data_from_cache();
        } catch (Exception $e) {
            $sql = "SELECT m.id as meta_id, s.id as structure_id
          FROM metadatas m, metadatas_structure s
          WHERE m.record_id = :record_id AND s.id = m.meta_struct_id
            ORDER BY s.sorter ASC";
            $stmt = $this->databox->get_connection()->prepare($sql);
            $stmt->execute([':record_id' => $this->record->get_record_id()]);
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $this->set_data_to_cache($fields);
        }

        $rec_fields = [];
        foreach ($fields as $row) {
            $databox_meta_struct = databox_field::get_instance($this->app, $this->databox, $row['structure_id']);
            $metadata = new caption_field($this->app, $databox_meta_struct, $this->record);

            $rec_fields[$databox_meta_struct->get_id()] = $metadata;
        }
        $this->fields = $rec_fields;

        return $this->fields;
    }

    /**
     *
     * @param array   $grep_fields
     * @param Boolean $IncludeBusiness
     *
     * @return array
     */
    public function get_fields(Array $grep_fields = null, $IncludeBusiness = false)
    {
        $fields = [];

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
     * @return array
     */
    public function get_highlight_fields($highlight = '', Array $grep_fields = null, SearchEngineInterface $searchEngine = null, $includeBusiness = false)
    {
        return $this->highlight_fields($highlight, $grep_fields, $searchEngine, $includeBusiness);
    }

    /**
     * @todo move this fun in caption_field object
     * @param  string                $highlight
     * @param  array                 $grep_fields
     * @param  SearchEngineInterface $searchEngine
     * @return array
     */
    protected function highlight_fields($highlight, Array $grep_fields = null, SearchEngineInterface $searchEngine = null, $includeBusiness = false)
    {
        $fields = [];

        foreach ($this->get_fields($grep_fields, $includeBusiness) as $meta_struct_id => $field) {

            $value = preg_replace(
                "(([^']{1})((https?|file):((/{2,4})|(\\{2,4}))[\w:#%/;$()~_?/\-=\\\.&]*)([^']{1}))"
                , '$1 $2 <a title="' . $this->app->trans('Open the URL in a new window') . '" class="ui-icon ui-icon-extlink" href="$2" style="display:inline;padding:2px 5px;margin:0 4px 0 2px;" target="_blank"> &nbsp;</a>$7'
                , $highlight ? $field->highlight_thesaurus() : $field->get_serialized_values(false, false)
            );

            $fields[$field->get_name()] = [
                'value'     => $value,
                /** @Ignore */
                'label'     => $field->get_databox_field()->get_label($this->app['locale']),
                'separator' => $field->get_databox_field()->get_separator(),
            ];
        }

        if ($searchEngine instanceof SearchEngineInterface) {
            $ret = $searchEngine->excerpt($highlight, $fields, $this->record);

            if ($ret) {
                $n = -1;

                foreach ($fields as $key => $value) {
                    $n++;

                    if (!isset($fields[$key])) {
                        continue;
                    }

                    if (strpos($fields[$key]['value'], '<a class="bounce" ') !== false) {
                        continue;
                    }

                    //if(strpos($fields[$key]['value'], '<a ') === false)
                    $fields[$key]['value'] = $ret[$n];
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
