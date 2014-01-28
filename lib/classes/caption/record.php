<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
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
    public function get_highlight_fields($highlight = '', Array $grep_fields = null, SearchEngineInterface $searchEngine = null, $includeBusiness = false, SearchEngineOptions $options = null)
    {
        return $this->highlight_fields($highlight, $grep_fields, $searchEngine, $includeBusiness, $options);
    }

    /**
     * @todo move this fun in caption_field object
     * @param  string                $highlight
     * @param  array                 $grep_fields
     * @param  SearchEngineInterface $searchEngine
     * @return array
     */
    protected function highlight_fields($highlight, Array $grep_fields = null, SearchEngineInterface $searchEngine = null, $includeBusiness = false, SearchEngineOptions $options = null)
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
            $ret = $searchEngine->excerpt($highlight, $fields, $this->record, $options);

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
