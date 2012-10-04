<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @package     caption
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class caption_field implements cache_cacheableInterface
{
    /**
     *
     * @var databox_field
     */
    protected $databox_field;

    /**
     *
     * @var string
     */
    protected $values;

    /**
     *
     * @var record
     */
    protected $record;
    protected $app;

    protected static $localCache = array();

    /**
     *
     * @param  databox_field    $databox_field
     * @param  record_Interface $record
     * @param  int              $id
     * @return caption_field
     */
    public function __construct(Application $app, databox_field $databox_field, record_Interface $record)
    {
        $this->app = $app;
        $this->record = $record;
        $this->databox_field = $databox_field;
        $this->values = array();

        $rs = $this->get_metadatas_ids();

        foreach ($rs as $row) {
            $this->values[$row['id']] = new caption_Field_Value($this->app, $databox_field, $record, $row['id']);

            /**
             * Inconsistent, should not happen
             */
            if ( ! $databox_field->is_multi()) {
                break;
            }
        }

        return $this;
    }

    protected function get_metadatas_ids()
    {
        try {
            return $this->get_data_from_cache();
        } catch (\Exception $e) {

        }

        $connbas = $this->databox_field->get_connection();

        $sql = 'SELECT id FROM metadatas
                WHERE record_id = :record_id
                  AND meta_struct_id = :meta_struct_id';

        $params = array(
            ':record_id'      => $this->record->get_record_id()
            , ':meta_struct_id' => $this->databox_field->get_id()
        );

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->set_data_to_cache($ids);

        return $ids;
    }

    /**
     *
     * @return record_adapter
     */
    public function get_record()
    {
        return $this->record;
    }

    /**
     *
     * @return boolean
     */
    public function is_required()
    {
        return $this->databox_field->is_required();
    }

    /**
     *
     * @return boolean
     */
    public function is_multi()
    {
        return $this->databox_field->is_multi();
    }

    /**
     *
     * @return boolean
     */
    public function is_readonly()
    {
        return $this->databox_field->is_readonly();
    }

    /**
     *
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
     *
     * @param  array  $values
     * @param  string $separator
     * @return string
     */
    protected static function serialize_value(Array $values, $separator, $highlight = false)
    {
        if (strlen($separator) > 1)
            $separator = $separator[0];

        if (trim($separator) === '')
            $separator = ' ';
        else
            $separator = ' ' . $separator . ' ';

        $array_values = array();

        foreach ($values as $value) {
            if ($highlight)
                $array_values[] = $value->highlight_thesaurus();
            else
                $array_values[] = $value->getValue();
        }

        return implode($separator, $array_values);
    }

    /**
     *
     * @return array
     */
    public function get_values()
    {
        return $this->values;
    }

    /**
     *
     * @param  int   $meta_id
     * @return array
     */
    public function get_value($meta_id)
    {
        return $this->values[$meta_id];
    }

    /**
     *
     * @param  string $custom_separator
     * @return mixed
     */
    public function get_serialized_values($custom_separator = false, $highlightTheso = false)
    {
        if ($this->databox_field->is_multi() === true) {
            if ($custom_separator !== false)
                $separator = $custom_separator;
            else
                $separator = $this->databox_field->get_separator();

            return self::serialize_value($this->values, $separator, $highlightTheso);
        } else {
            foreach ($this->values as $value) {
                /* @var $value Caption_Field_Value */

                return $value->highlight_thesaurus();
            }
        }

        return null;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->databox_field->get_name();
    }

    /**
     *
     * @return int
     */
    public function get_meta_struct_id()
    {
        return $this->databox_field->get_id();
    }

    /**
     *
     * @return boolean
     */
    public function is_indexable()
    {
        return $this->databox_field->is_indexable();
    }

    /**
     *
     * @return databox_field
     */
    public function get_databox_field()
    {
        return $this->databox_field;
    }

    /**
     *
     * @return string
     */
    public function highlight_thesaurus()
    {
        $value = $this->get_serialized_values(false, true);

        return $value;
    }

    /**
     *
     * @param  string $serialized_value
     * @param  string $separator
     * @return array
     */
    public static function get_multi_values($serialized_value, $separator)
    {
        $values = array();
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

    public static function rename_all_metadatas(databox_field $databox_field)
    {
        $sql = 'SELECT count(id) as count_id FROM metadatas
            WHERE meta_struct_id = :meta_struct_id';
        $stmt = $databox_field->get_databox()->get_connection()->prepare($sql);
        $params = array(
            ':meta_struct_id' => $databox_field->get_id()
        );

        $stmt->execute($params);
        $rowcount = $stmt->rowCount();
        $stmt->closeCursor();

        $n = 0;
        $increment = 500;

        while ($n < $rowcount) {
            $sql = 'SELECT record_id, id FROM metadatas
              WHERE meta_struct_id = :meta_struct_id LIMIT ' . $n . ', ' . $increment;

            $params = array(
                ':meta_struct_id' => $databox_field->get_id()
            );

            $stmt = $databox_field->get_databox()->get_connection()->prepare($sql);
            $stmt->execute($params);
            $rowcount = $stmt->rowCount();
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);

            foreach ($rs as $row) {
                try {
                    $record = $databox_field->get_databox()->get_record($row['record_id']);
                    $record->set_metadatas(array());
                    unset($record);
                } catch (Exception $e) {

                }
            }

            $n += $increment;
        }

        return;
    }

    public static function delete_all_metadatas(Application $app, databox_field $databox_field)
    {
        $sql = 'SELECT count(id) as count_id FROM metadatas
            WHERE meta_struct_id = :meta_struct_id';

        $stmt = $databox_field->get_databox()->get_connection()->prepare($sql);
        $params = array(
            ':meta_struct_id' => $databox_field->get_id()
        );

        $stmt->execute($params);
        $rowcount = $stmt->rowCount();
        $stmt->closeCursor();

        $n = 0;
        $increment = 500;

        while ($n < $rowcount) {
            $sql = 'SELECT record_id, id FROM metadatas
              WHERE meta_struct_id = :meta_struct_id
              LIMIT ' . $n . ', ' . $increment;

            $params = array(
                ':meta_struct_id' => $databox_field->get_id()
            );

            $stmt = $databox_field->get_databox()->get_connection()->prepare($sql);
            $stmt->execute($params);
            $rowcount = $stmt->rowCount();
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);

            foreach ($rs as $row) {
                try {
                    $record = $databox_field->get_databox()->get_record($row['record_id']);
                    $caption_field = new caption_field($app, $databox_field, $record);
                    $caption_field->delete();
                    $record->set_metadatas(array());
                    unset($caption_field);
                    unset($record);
                } catch (Exception $e) {

                }
            }

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
        return 'caption_field_' . $this->databox_field->get_id() . '_' . $this->record->get_serialize_key() . ($option ? '_' . $option : '');
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string $option
     * @return mixed
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
}
