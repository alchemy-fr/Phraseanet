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
use Alchemy\Phrasea\Databox\Caption\CachedCaptionDataRepository;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;

class caption_record implements cache_cacheableInterface
{
    /**
     * @var array
     */
    protected $fields;

    /**
     * @var RecordReferenceInterface
     */
    protected $record;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     * @param RecordReferenceInterface $record
     * @param array[]|null $fieldsData
     */
    public function __construct(Application $app, RecordReferenceInterface $record, array $fieldsData = null)
    {
        $this->app = $app;
        $this->record = $record;
        $this->fields = null === $fieldsData ? null : $this->mapFieldsFromData($fieldsData);
    }

    public function toArray($includeBusinessFields)
    {
        /** @var CaptionSerializer $serializer */
        $serializer = $this->app['serializer.caption'];

        return $serializer->toArray($this, $includeBusinessFields);
    }

    /**
     * @return RecordReferenceInterface
     */
    public function getRecordReference()
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

        $data = $this->getDataRepository()->findByRecordIds([$this->getRecordReference()->getRecordId()]);

        $this->fields = $this->mapFieldsFromData(array_shift($data));

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
            if ($grep_fields && ! in_array($field->get_name(), $grep_fields, true)) {
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
     * @return caption_field[]
     */
    public function getDCFields()
    {
        $databoxDcesFieldIds = array_map(function (databox_field $databox_field) {
            return $databox_field->get_id();
        }, $this->getDatabox()->get_meta_structure()->getDcesFields());

        return array_intersect_key(
            $this->retrieve_fields(),
            array_fill_keys($databoxDcesFieldIds, null)
        );
    }

    /**
     * @param  string $label
     * @return caption_field|null
     */
    public function get_dc_field($label)
    {
        $fields = $this->retrieve_fields();

        if (null !== $field = $this->getDatabox()->get_meta_structure()->get_dces_field($label)) {
            if (isset($fields[$field->get_id()])) {
                return $fields[$field->get_id()];
            }
        }

        return null;
    }

    /**
     * @param array $grep_fields
     * @param bool $includeBusiness
     * @return array
     */
    public function get_highlight_fields(array $grep_fields = null, $includeBusiness = false)
    {
        $fields = [];

        foreach ($this->get_fields($grep_fields, $includeBusiness) as $meta_struct_id => $field) {
            $values = [];
            foreach ($field->get_values() as $metaId => $v) {
                $values[$metaId] = [
                    'value' => $v->getValue(),
                    'from_thesaurus' => false,
                    'qjs' => null,
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
        return $this->getDatabox()->get_data_from_cache($this->get_cache_key($option));
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
        return $this->getDatabox()->set_data_to_cache($value, $this->get_cache_key($option), $duration);
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

        $this->getDataRepository()->invalidate($this->getRecordReference()->getRecordId());

        return $this->getDatabox()->delete_data_from_cache($this->get_cache_key($option));
    }

    /**
     * @return databox
     */
    private function getDatabox()
    {
        return $this->app->findDataboxById($this->record->getDataboxId());
    }

    /**
     * @param array $data
     * @return caption_field[]
     */
    protected function mapFieldsFromData($data)
    {
        if (!$data) {
            return [];
        }

        $rec_fields = array();

        $databox = $this->getDatabox();
        $databox_descriptionStructure = $databox->get_meta_structure();
        $record = $databox->get_record($this->record->getRecordId());

        // first group values by field
        $caption_fields = [];
        foreach ($data as $row) {
            $structure_id = $row['structure_id'];
            if (!array_key_exists($structure_id, $caption_fields)) {
                $caption_fields[$structure_id] = [
                    'db_field' => $databox_descriptionStructure->get_element($structure_id),
                    'values' => []
                ];
            }

            if (count($caption_fields[$structure_id]['values']) > 0 && !$caption_fields[$structure_id]['db_field']->is_multi()) {
                // Inconsistent, should not happen
                continue;
            }

            // build an EMPTY caption_Field_Value
            $cfv = new caption_Field_Value(
                $this->app,
                $caption_fields[$structure_id]['db_field'],
                $record,
                $row['meta_id'],
                caption_Field_Value::DONT_RETRIEVE_VALUES   // ask caption_Field_Value "no n+1 sql"
            );

            // inject the value we already know
            $cfv->injectValues($row['value'], $row['VocabularyType'], $row['VocabularyId']);

            // add the value to the field
            $caption_fields[$structure_id]['values'][] = $cfv;
        }

        // now build a "caption_field" with already known "caption_Field_Value"s
        foreach ($caption_fields as $structure_id => $caption_field) {

            // build an EMPTY caption_field
            $cf = new caption_field(
                $this->app,
                $caption_field['db_field'],
                $record,
                caption_field::DONT_RETRIEVE_VALUES     // ask caption_field "no n+1 sql"
            );

            // inject the value we already know
            $cf->injectValues($caption_field['values']);

            // add the field to the fields
            $rec_fields[$structure_id] = $cf;
        }

        return $rec_fields;
    }

    /**
     * @return CachedCaptionDataRepository
     */
    private function getDataRepository()
    {
        return $this->app['provider.data_repo.caption']
            ->getRepositoryForDatabox($this->getRecordReference()->getDataboxId());
    }
}
