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

    public function get_record()
    {
        return $this->record;
    }

    protected function retrieve_fields()
    {
        if (is_array($this->fields)) {
            return $this->fields;
        }

        $fields = [];
        try {
            $fields = $this->get_data_from_cache();
        } catch (\Exception $e) {
            $sql = "SELECT m.id as meta_id, s.id as structure_id
          FROM metadatas m, metadatas_structure s
          WHERE m.record_id = :record_id AND s.id = m.meta_struct_id
            ORDER BY s.sorter ASC";
            $stmt = $this->databox->get_connection()->prepare($sql);
            $stmt->execute([':record_id' => $this->record->get_record_id()]);
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            if ($fields) {
                $this->set_data_to_cache($fields);
            }
        }

        $rec_fields = array();

        if ($fields) {
            foreach ($fields as $row) {
                $databox_meta_struct = databox_field::get_instance($this->app, $this->databox, $row['structure_id']);
                $metadata = new caption_field($this->app, $databox_meta_struct, $this->record);

                $rec_fields[$databox_meta_struct->get_id()] = $metadata;
            }
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
                'labe_'     => $field->get_databox_field()->get_label($this->app['locale']),
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
