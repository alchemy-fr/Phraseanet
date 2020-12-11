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
use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Doctrine\DBAL\Driver\Statement;

class caption_field implements cache_cacheableInterface
{
    const RETRIEVE_VALUES = true;
    const DONT_RETRIEVE_VALUES = false;

    /**
     * @var databox_field
     */
    protected $databox_field;

    /**
     * @var caption_Field_Value[]
     */
    protected $values;

    /**
     * @var \record_adapter
     */
    protected $record;
    protected $app;

    protected static $localCache = [];

    /**
     * @param Application    $app
     * @param databox_field  $databox_field
     * @param record_adapter $record
     * @param bool           $retrieveValues
     */
    public function __construct(Application $app, databox_field $databox_field, \record_adapter $record, $retrieveValues = self::RETRIEVE_VALUES)
    {
        $this->app = $app;
        $this->record = $record;
        $this->databox_field = $databox_field;
        $this->values = [];

        if($retrieveValues == self::RETRIEVE_VALUES) {
            $rs = $this->get_metadatas_ids();

            foreach ($rs as $row) {
                $this->values[$row['id']] = new caption_Field_Value($this->app, $databox_field, $record, $row['id']);

                // Inconsistent, should not happen
                if (!$databox_field->is_multi()) {
                    break;
                }
            }
        }
    }

    /**
     * @param caption_Field_Value[] $values
     */
    public function injectValues($values)
    {
        $this->values = $values;
    }

    protected function get_metadatas_ids()
    {
        try {
            return $this->get_data_from_cache();
        } catch (\Exception $e) {

        }

        $connbas = $this->databox_field->get_connection();

        $sql = 'SELECT id FROM metadatas WHERE record_id = :record_id AND meta_struct_id = :meta_struct_id';

        $params = [
            ':record_id'      => $this->record->getRecordId()
            , ':meta_struct_id' => $this->databox_field->get_id()
        ];

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->set_data_to_cache($ids);

        return $ids;
    }

    /**
     * @return record_adapter
     */
    public function get_record()
    {
        return $this->record;
    }

    /**
     * @return bool
     */
    public function is_required()
    {
        return $this->databox_field->is_required();
    }

    /**
     * @return bool
     */
    public function is_multi()
    {
        return $this->databox_field->is_multi();
    }

    /**
     * @return bool
     */
    public function is_readonly()
    {
        return $this->databox_field->is_readonly();
    }

    /**
     * @return caption_field
     */
    public function delete()
    {

        foreach ($this->get_values() as $value) {
            $value->delete();
        }

        return $this;
    }

    /**
     * @param caption_Field_Value[] $values
     * @param string $separator
     * @param bool $highlight
     * @return string
     */
    protected function serialize_value(array $values, $separator, $highlight = false)
    {
        if (strlen($separator) > 1) {
            $separator = $separator[0];
        }

        if (trim($separator) === '') {
            $separator = ' ';
        } else {
            $separator = ' ' . $separator . ' ';
        }

        $array_values = [];

        foreach ($values as $value) {
            $array_values[] = $highlight ? $value->highlight_thesaurus() : $value->getValue();
        }

        return implode($separator, $array_values);
    }

    /**
     * @return \caption_Field_Value[]
     */
    public function get_values()
    {
        return $this->values;
    }

    /**
     * @param  int   $meta_id
     * @return \caption_Field_Value
     */
    public function get_value($meta_id)
    {
        return $this->values[$meta_id];
    }

    /**
     * @param string|bool $custom_separator
     * @param bool $highlight
     *
     * @return string
     */
    public function get_serialized_values($custom_separator = false, $highlight = false)
    {
        if (0 === count($this->values)) {
            return null;
        }

        if ($this->is_multi()) {
            $separator = $custom_separator !== false ? $custom_separator : $this->databox_field->get_separator();

            return $this->serialize_value($this->values, $separator, $highlight);
        }

        /** @var caption_Field_Value $value */
        $value = current($this->values);

        if ($highlight) {
            return $value->highlight_thesaurus();
        }

        return $value->getValue();

    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->databox_field->get_name();
    }

    /**
     * @return int
     */
    public function get_meta_struct_id()
    {
        return $this->databox_field->get_id();
    }

    /**
     * @return bool
     */
    public function is_indexable()
    {
        return $this->databox_field->is_indexable();
    }

    /**
     * @return databox_field
     */
    public function get_databox_field()
    {
        return $this->databox_field;
    }

    /**
     * @param  string $serialized_value
     * @param  string $separator
     * @return array
     */
    public static function get_multi_values($serialized_value, $separator)
    {
        if (strlen($separator) == 1) {
            $values = explode($separator, $serialized_value);
        } else {
            // s'il y'a plusieurs delimiters, on transforme
            // en regexp pour utiliser split
            $separator = preg_split('//', $separator, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $separator = '/\\' . implode('|\\', $separator) . '/';
            $values = preg_split($separator, $serialized_value);
        }

        foreach ($values as $key => $value) {
            $values[$key] = trim($value);
        }

        return $values;
    }

    public static function rename_all_metadatas(Application $app, databox_field $databox_field)
    {
        $connection = $databox_field->get_databox()->get_connection();
        $builder = $connection->createQueryBuilder();
        $builder
            ->select('COUNT(m.id) AS count_id')
            ->from('metadatas', 'm')
            ->where($builder->expr()->eq('m.meta_struct_id', ':meta_struct_id'))
            ->setParameter('meta_struct_id', $databox_field->get_id())
        ;

        /** @var Statement $stmt */
        $stmt = $builder->execute();
        $rowcount = $stmt->fetchColumn();
        $stmt->closeCursor();
        unset($stmt);

        $n = 0;
        $increment = 500;

        $builder
            ->select('m.record_id', 'm.id')
            ->setMaxResults($increment)
        ;
        while ($n < $rowcount) {
            /** @var Statement $stmt */
            $stmt = $builder
                ->setFirstResult($n)
                ->execute();

            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rs as $row) {
                try {
                    $record = $databox_field->get_databox()->get_record($row['record_id']);
                    $record->set_metadatas([]);

                    unset($record);
                } catch (\Exception $e) {

                }
            }

            // order to write metas for those records
            $app['dispatcher']->dispatch(WorkerEvents::RECORDS_WRITE_META,
                new RecordsWriteMetaEvent(array_column($rs, 'record_id'), $databox_field->get_databox()->get_sbas_id())
            );

            $n += $increment;
        }
    }

    public static function delete_all_metadatas(Application $app, databox_field $databox_field)
    {
        $connection = $databox_field->get_databox()->get_connection();
        $builder = $connection->createQueryBuilder();
        $builder
            ->select('COUNT(m.id) AS count_id')
            ->from('metadatas', 'm')
            ->where($builder->expr()->eq('m.meta_struct_id', ':meta_struct_id'))
            ->setParameter('meta_struct_id', $databox_field->get_id())
        ;

        /** @var Statement $stmt */
        $stmt = $builder->execute();
        $rowcount = $stmt->fetchColumn();
        $stmt->closeCursor();
        unset($stmt);

        $n = 0;
        $increment = 500;

        $builder
            ->select('m.record_id', 'm.id')
            ->setMaxResults($increment)
        ;
        while ($n < $rowcount) {
            /** @var Statement $stmt */
            $stmt = $builder
                ->setFirstResult($n)
                ->execute();

            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rs as $row) {
                try {
                    $record = $databox_field->get_databox()->get_record($row['record_id']);
                    $caption_field = new caption_field($app, $databox_field, $record);
                    $caption_field->delete();
                    $record->set_metadatas([]);

                    unset($caption_field, $record);
                } catch (\Exception $e) {

                }
            }

            // order to write metas for those records
            $app['dispatcher']->dispatch(WorkerEvents::RECORDS_WRITE_META,
                new RecordsWriteMetaEvent(array_column($rs, 'record_id'), $databox_field->get_databox()->get_sbas_id())
            );

            $n += $increment;
        }

        return;
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string $option
     * @return string
     */
    public function get_cache_key($option = null)
    {
        return 'caption_field_' . $this->databox_field->get_id() . '_' . $this->record->getId() . ($option ? '_' . $option : '');
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string $option
     * @return mixed
     * @throws Exception
     */
    public function get_data_from_cache($option = null)
    {
        if (isset(self::$localCache[$this->get_cache_key($option)])) {
            return self::$localCache[$this->get_cache_key($option)];
        }

        throw new Exception('no value');
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  mixed         $value
     * @param  string        $option
     * @param  int           $duration
     * @return caption_field
     */
    public function set_data_to_cache($value, $option = null, $duration = 360000)
    {
        return self::$localCache[$this->get_cache_key($option)] = $value;
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string        $option
     * @return caption_field
     */
    public function delete_data_from_cache($option = null)
    {
        unset(self::$localCache[$this->get_cache_key($option)]);
    }

    public static function purge()
    {
        self::$localCache = [];
    }
}
