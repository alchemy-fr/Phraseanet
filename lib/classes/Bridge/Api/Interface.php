<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Request;

interface Bridge_Api_Interface
{
    const OBJECT_CLASS_ELEMENT = 'element';
    const OBJECT_CLASS_CONTAINER = 'container';

    /**
     *
     * @return Array
     */
    public function connect();

    /**
     *
     * @return Bridge_Api_Interface
     */
    public function reconnect();

    /**
     *
     * @return Bridge_Api_Interface
     */
    public function disconnect();

    /**
     *
     * @return boolean
     */
    public function is_configured();

    /**
     *
     * @return boolean
     */
    public function is_connected();

    /**
     *
     * @return Bridge_Api_Interface
     */
    public function set_locale($locale);

    /**
     *
     * @return Bridge_Api_Interface
     */
    public function set_auth_settings(Bridge_AccountSettings $settings);

    /**
     *
     * @return string
     */
    public function get_name();

    /**
     *
     * @return string
     */
    public function get_icon_url();

    /**
     *
     * @return string
     */
    public function get_auth_url();

    /**
     *
     * @return string
     */
    public function get_image_url();

    /**
     *
     * @return string
     */
    public function get_terms_url();

    /**
     *
     * @return string
     */
    public function get_url();

    /**
     *
     * @return string
     */
    public function get_infos();

    public function get_object_class_from_type($type);

    public function get_default_element_type();

    public function get_default_container_type();

    public function get_element_types();

    public function get_container_types();

    public function get_element_from_id($element_id, $object);

    public function get_container_from_id($element_id, $object);

    public function get_category_list();

    public function get_user_name();

    public function get_user_id();

    public function list_elements($type, $offset_start = 0, $quantity = 10);

    public function list_containers($type, $offset_start = 0, $quantity = 10);

    public function update_element($object, $object_id, Array $datas);

    public function create_container($container_type, Request $request);

    public function add_element_to_container($element_type, $element_id, $destination, $container_id);

    public function delete_object($object, $object_id);

    /**
     *
     * @return Closure
     */
    public function acceptable_records();

    public function get_element_status(Bridge_Element $element);

    public function map_connector_to_element_status($status);

    public function get_error_message_from_status($connector_status);

    public function upload(record_adapter $record, array $options = []);

    public function is_valid_object_id($object_id);

    /**
     *
     * @return boolean
     */
    public function is_multiple_upload();

    /**
     *
     * @return array
     */
    public function check_upload_constraints(Array $datas, record_adapter $record);

    public function get_upload_datas(Request $request, record_adapter $record);

    public function get_update_datas(Request $request);

    public function check_update_constraints(Array $datas);
}
