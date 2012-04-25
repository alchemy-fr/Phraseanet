<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_fieldUnknown extends databox_field
{

    /**
     *
     * @param databox $databox
     * @param int $id
     * @return databox_fieldUnknown
     */
    public function __construct(databox $databox, $id)
    {
        $this->set_databox($databox);
        $this->sbas_id = $databox->get_sbas_id();
        $this->id = $id;

        return $this;
    }

    /**
     *
     * @param databox $databox
     * @param int $id
     * @return databox_fieldUnknown
     */
    public static function get_instance(databox &$databox, $id)
    {
        $cache_key = 'field_' . $id;
        $instance_id = $databox->get_sbas_id() . '_' . $id;
        if ( ! isset(self::$_instance[$instance_id]) || (self::$_instance[$instance_id] instanceof self) === false) {
            try {
                self::$_instance[$instance_id] = $databox->get_data_from_cache($cache_key);
            } catch (Exception $e) {
                self::$_instance[$instance_id] = new self($databox, $id);
                $databox->set_data_to_cache(self::$_instance[$instance_id], $cache_key);
            }
        }

        return self::$_instance[$instance_id];
    }

    /**
     *
     * @return string
     */
    public function get_metadata_source()
    {
        return '';
    }

    public function get_metadata_namespace()
    {
        return '';
    }

    /**
     *
     * @return string
     */
    public function get_metadata_tagname()
    {
        return '';
    }

    /**
     * Return true because the field is unknown
     *
     * @return boolean
     */
    public function is_on_error()
    {
        return true;
    }
}
