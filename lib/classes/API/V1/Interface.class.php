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
 * @package     APIv1
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface API_V1_Interface
{
  public function get_version();

  /**
   * Route : /databoxes/list/FORMAT/
   *
   * Method : GET
   *
   * Parameters :
   *
   */
  public function get_databoxes(\Symfony\Component\HttpFoundation\Request $request);

  /**
   * Route /databoxes/DATABOX_ID/collections/FORMAT/
   *
   * Method : GET
   *
   * Parameters ;
   *    DATABOX_ID : required INT
   */
  public function get_databox_collections(\Symfony\Component\HttpFoundation\Request $request, $databox_id);

  /**
   * Route /databoxes/DATABOX_ID/status/FORMAT/
   *
   * Method : GET
   *
   * Parameters ;
   *    DATABOX_ID : required INT
   */
  public function get_databox_status(\Symfony\Component\HttpFoundation\Request $request, $databox_id);

  /**
   * Route /databoxes/DATABOX_ID/metadatas/FORMAT/
   *
   * Method : GET
   *
   * Parameters ;
   *    DATABOX_ID : required INT
   */
  public function get_databox_metadatas(\Symfony\Component\HttpFoundation\Request $request, $databox_id);

  /**
   * Route /databoxes/DATABOX_ID/termsOfUse/FORMAT/
   *
   * Method : GET
   *
   * Parameters ;
   *    DATABOX_ID : required INT
   */
  public function get_databox_terms(\Symfony\Component\HttpFoundation\Request $request, $databox_id);

  /**
   * Route : /records/search/FORMAT/
   *
   * Method : GET or POST
   *
   * Parameters :
   *    bases[] : array
   *    status[] : array
   *    fields[] : array
   *    record_type : boolean
   *    media_type : string
   *
   * Response :
   *    Array of record objects
   *
   */
  public function search_records(\Symfony\Component\HttpFoundation\Request $request);

  /**
   * Route : /records/DATABOX_ID/RECORD_ID/related/FORMAT/
   *
   * Method : GET
   *
   * Parameters :
   *    DATABOX_ID : required INT
   *    RECORD_ID : required INT
   *
   */
  public function get_record_related(\Symfony\Component\HttpFoundation\Request $request, $databox_id, $record_id);

  /**
   * Route : /records/DATABOX_ID/RECORD_ID/metadatas/FORMAT/
   *
   * Method : GET
   *
   * Parameters :
   *    DATABOX_ID : required INT
   *    RECORD_ID : required INT
   *
   */
  public function get_record_metadatas(\Symfony\Component\HttpFoundation\Request $request, $databox_id, $record_id);

  /**
   * Route : /records/DATABOX_ID/RECORD_ID/status/FORMAT/
   *
   * Method : GET
   *
   * Parameters :
   *    DATABOX_ID : required INT
   *    RECORD_ID : required INT
   *
   */
  public function get_record_status(\Symfony\Component\HttpFoundation\Request $request, $databox_id, $record_id);

  /**
   * Route : /records/DATABOX_ID/RECORD_ID/embed/FORMAT/
   *
   * Method : GET
   *
   * Parameters :
   *    DATABOX_ID : required INT
   *    RECORD_ID : required INT
   *
   */
  public function get_record_embed(\Symfony\Component\HttpFoundation\Request $request, $databox_id, $record_id);

  /**
   * Route : /records/DATABOX_ID/RECORD_ID/setmetadatas/FORMAT/
   *
   * Method : POST
   *
   * Parameters :
   *    DATABOX_ID : required INT
   *    RECORD_ID : required INT
   *
   */
  public function set_record_metadatas(\Symfony\Component\HttpFoundation\Request $request, $databox_id, $record_id);

  /**
   * Route : /records/DATABOX_ID/RECORD_ID/setstatus/FORMAT/
   *
   * Method : POST
   *
   * Parameters :
   *    DATABOX_ID : required INT
   *    RECORD_ID : required INT
   *
   */
  public function set_record_status(\Symfony\Component\HttpFoundation\Request $request, $databox_id, $record_id);

  /**
   * Route : /records/DATABOX_ID/RECORD_ID/setcollection/FORMAT/
   *
   * Method : POST
   *
   * Parameters :
   *    DATABOX_ID : required INT
   *    RECORD_ID : required INT
   *
   */
  public function set_record_collection(\Symfony\Component\HttpFoundation\Request $request, $databox_id, $record_id);

  /**
   * Route : /records/DATABOX_ID/RECORD_ID/addtobasket/FORMAT/
   *
   * Method : POST
   *
   * Parameters :
   *    DATABOX_ID : required INT
   *    RECORD_ID : required INT
   *
   */
  public function add_record_tobasket(\Symfony\Component\HttpFoundation\Request $request, $databox_id, $record_id);

  /**
   * Route : /baskets/list/FORMAT/
   *
   * Method : POST
   *
   * Parameters :
   *
   */
  public function search_baskets(\Symfony\Component\HttpFoundation\Request $request);

  /**
   * Route : /baskets/add/FORMAT/
   *
   * Method : POST
   *
   * Parameters :
   *
   */
  public function create_basket(\Symfony\Component\HttpFoundation\Request $request);

  /**
   * Route : /baskets/BASKET_ID/delete/FORMAT/
   *
   * Method : POST
   *
   * Parameters :
   *    BASKET_ID : required INT
   *
   */
  public function delete_basket(\Symfony\Component\HttpFoundation\Request $request, $basket_id);

  /**
   * Route : /baskets/BASKET_ID/content/FORMAT/
   *
   * Method : POST
   *
   * Parameters :
   *    BASKET_ID : required INT
   *
   */
  public function get_basket(\Symfony\Component\HttpFoundation\Request $request, $basket_id);

  /**
   * Route : /baskets/BASKET_ID/title/FORMAT/
   *
   * Method : GET
   *
   * Parameters :
   *    BASKET_ID : required INT
   *
   */
  public function set_basket_title(\Symfony\Component\HttpFoundation\Request $request, $basket_id);

  /**
   * Route : /baskets/BASKET_ID/description/FORMAT/
   *
   * Method : POST
   *
   * Parameters :
   *    BASKET_ID : required INT
   *
   */
  public function set_basket_description(\Symfony\Component\HttpFoundation\Request $request, $basket_id);

  /**
   * Route : /publications/list/FORMAT/
   *
   * Method : POST
   *
   * Parameters :
   *
   */
  public function search_publications(\Symfony\Component\HttpFoundation\Request $request, User_Adapter &$user);

  /**
   * Route : /publications/PUBLICATION_ID/remove/FORMAT/
   *
   * Method : GET
   *
   * Parameters :
   *    PUBLICATION_ID : required INT
   *
   */
  public function remove_publications(\Symfony\Component\HttpFoundation\Request $request, $publication_id);

  /**
   * Route : /publications/PUBLICATION_ID/content/FORMAT/
   *
   * Method : GET
   *
   * Parameters :
   *    PUBLICATION_ID : required INT
   *
   */
  public function get_publication(\Symfony\Component\HttpFoundation\Request $request, $publication_id, User_Adapter &$user);

  /**
   * Route : /users/search/FORMAT/
   *
   * Method : POST-GET
   *
   * Parameters :
   *
   */
  public function search_users(\Symfony\Component\HttpFoundation\Request $request);

  /**
   * Route : /users/USER_ID/access/FORMAT/
   *
   * Method : GET
   *
   * Parameters :
   *    USER_ID : required INT
   *
   */
  public function get_user_acces(\Symfony\Component\HttpFoundation\Request $request, $usr_id);

  /**
   * Route : /users/add/FORMAT/
   *
   * Method : POST
   *
   * Parameters :
   *
   */
  public function add_user(\Symfony\Component\HttpFoundation\Request $request);

  public function get_error_message(\Symfony\Component\HttpFoundation\Request $request, $error);

  public function get_error_code(\Symfony\Component\HttpFoundation\Request $request, $code);
}
