<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

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

    /**
     *
     * @param Application      $app
     * @param record_adapter $record
     * @param databox          $databox
     *
     * @return caption_record
     */
    public function __construct(Application $app, \record_adapter $record, databox $databox)
    {
        $this->app = $app;
        $this->sbas_id = $record->getDataboxId();
        $this->record = $record;
        $this->databox = $databox;
    }

    public function toArray($includeBusinessFields)
    {
        /** @var CaptionSerializer $serializer */
        $serializer = $this->app['serializer.caption'];

        return $serializer->toArray($this, $includeBusinessFields);
    }

    public function get_record()
    {
        return $this->record;
    }

    /**
     * @return \caption_field[]
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function retrieve_fields()
    {
        if (is_array($this->fields)) {
            return $this->fields;
        }

        try {
            $fields = $this->get_data_from_cache();
        } catch (\Exception $e) {
            $sql = "SELECT m.id AS meta_id, s.id AS structure_id, value, VocabularyType, VocabularyId"
                . " FROM metadatas m, metadatas_structure s"
                . " WHERE m.record_id = :record_id AND s.id = m.meta_struct_id"
                . " ORDER BY s.sorter ASC";
            $stmt = $this->databox->get_connection()->prepare($sql);
            $stmt->execute([':record_id' => $this->record->getRecordId()]);
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            if ($fields) {
                $this->set_data_to_cache($fields);
            }
        }

        $rec_fields = array();

        if ($fields) {
            $databox_descriptionStructure = $this->databox->get_meta_structure();

            // first group values by field
            $caption_fields = [];
            foreach ($fields as $row) {
                $structure_id = $row['structure_id'];
                if(!array_key_exists($structure_id, $caption_fields)) {
                    $caption_fields[$structure_id] = [
                        'db_field' => $databox_descriptionStructure->get_element($structure_id),
                        'values' => []
                    ];
                }

                if (count($caption_fields[$structure_id]['values']) > 0  && !$caption_fields[$structure_id]['db_field']->is_multi()) {
                    // Inconsistent, should not happen
                    continue;
                }

                // build an EMPTY caption_Field_Value
                $cfv = new caption_Field_Value(
                    $this->app,
                    $caption_fields[$structure_id]['db_field'],
                    $this->record,
                    $row['meta_id'],
                    caption_Field_Value::DONT_RETRIEVE_VALUES   // ask caption_Field_Value "no n+1 sql"
                );

                // inject the value we already know
                $cfv->injectValues($row['value'], $row['VocabularyType'], $row['VocabularyId']);

                // add the value to the field
                $caption_fields[$structure_id]['values'][] = $cfv;
            }

            // now build a "caption_field" with already known "caption_Field_Value"s
            foreach($caption_fields as $structure_id => $caption_field) {

                // build an EMPTY caption_field
                $cf = new caption_field(
                    $this->app,
                    $caption_field['db_field'],
                    $this->record,
                    caption_field::DONT_RETRIEVE_VALUES     // ask caption_field "no n+1 sql"
                );

                // inject the value we already know
                $cf->injectValues($caption_field['values']);

                // add the field to the fields
                $rec_fields[$structure_id] = $cf;
            }
        }
        $this->fields = $rec_fields;

        return $this->fields;
    }

    /**
     * @param array $grep_fields
     * @param bool  $includeBusiness
     *
     * @return \caption_field[]
     */
    public function get_fields(array $grep_fields = null, $includeBusiness = false)
    {
        $fields = [];

        foreach ($this->retrieve_fields() as $meta_struct_id => $field) {
            if ($grep_fields && ! in_array($field->get_name(), $grep_fields)) {
                continue;
            }

            if ((!$includeBusiness) && $field->get_databox_field()->isBusiness() === true) {
                continue;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * @param  string $fieldname
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
     * @param  string $label
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
    public function get_highlight_fields($highlight = '', Array $grep_fields = null, SearchEngineInterface $searchEngine = null, $includeBusiness = false, SearchEngineOptions $options = null)
    {
        $fields = [];

        foreach ($this->get_fields($grep_fields, $includeBusiness) as $meta_struct_id => $field) {
            $values = [];
            foreach ($field->get_values() as $metaId => $v) {
                $values[$metaId] = [
                    'value' => $v->getValue(),
                    'from_thesaurus' => $highlight ? $v->isThesaurusValue() : false,
                    'qjs' => $v->getQjs(),
                 ];
            }
            $fields[$field->get_name()] = [
                'values'    => $values,
                'name'      => $field->get_name(),
                'label_name'     => $field->get_databox_field()->get_label($this->app['locale']),
                'separator' => $field->get_databox_field()->get_separator(),
                'sbas_id'   => $field->get_databox_field()->get_databox()->get_sbas_id()
            ];
        }

        if ($searchEngine instanceof SearchEngineInterface) {
            $ret = $searchEngine->excerpt($highlight, $fields, $this->record, $options);

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
        return 'caption_' . $this->record->getId() . ($option ? '_' . $option : '');
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string $option
     * @return mixed
     */
    public function get_data_from_cache($option = null)
    {
        return $this->record->getDatabox()->get_data_from_cache($this->get_cache_key($option));
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
        return $this->record->getDatabox()->set_data_to_cache($value, $this->get_cache_key($option), $duration);
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

        return $this->record->getDatabox()->delete_data_from_cache($this->get_cache_key($option));
    }
}
