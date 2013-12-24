<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

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
    public function get_databoxes(Request $request);

    /**
     * Route /databoxes/DATABOX_ID/collections/FORMAT/
     *
     * Method : GET
     *
     * Parameters ;
     *    DATABOX_ID : required INT
     */
    public function get_databox_collections(Request $request, $databox_id);

    /**
     * Route /databoxes/DATABOX_ID/status/FORMAT/
     *
     * Method : GET
     *
     * Parameters ;
     *    DATABOX_ID : required INT
     */
    public function get_databox_status(Request $request, $databox_id);

    /**
     * Route /databoxes/DATABOX_ID/metadatas/FORMAT/
     *
     * Method : GET
     *
     * Parameters ;
     *    DATABOX_ID : required INT
     */
    public function get_databox_metadatas(Request $request, $databox_id);

    /**
     * Route /databoxes/DATABOX_ID/termsOfUse/FORMAT/
     *
     * Method : GET
     *
     * Parameters ;
     *    DATABOX_ID : required INT
     */
    public function get_databox_terms(Request $request, $databox_id);

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
    public function search_records(Request $request);

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
    public function get_record_related(Request $request, $databox_id, $record_id);

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
    public function get_record_metadatas(Request $request, $databox_id, $record_id);

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
    public function get_record_status(Request $request, $databox_id, $record_id);

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
    public function get_record_embed(Request $request, $databox_id, $record_id);

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
    public function set_record_metadatas(Request $request, $databox_id, $record_id);

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
    public function set_record_status(Request $request, $databox_id, $record_id);

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
    public function set_record_collection(Request $request, $databox_id, $record_id);

    /**
     * Route : /baskets/list/FORMAT/
     *
     * Method : POST
     *
     * Parameters :
     *
     */
    public function search_baskets(Request $request);

    /**
     * Route : /baskets/add/FORMAT/
     *
     * Method : POST
     *
     * Parameters :
     *
     */
    public function create_basket(Request $request);

    /**
     * Route : /baskets/BASKET_ID/delete/FORMAT/
     *
     * Method : POST
     *
     * Parameters :
     *    BASKET_ID : required INT
     *
     */
    public function delete_basket(Request $request, Basket $basket);

    /**
     * Route : /baskets/BASKET_ID/content/FORMAT/
     *
     * Method : POST
     *
     * Parameters :
     *    BASKET_ID : required INT
     *
     */
    public function get_basket(Request $request, Basket $basket);

    /**
     * Route : /baskets/BASKET_ID/title/FORMAT/
     *
     * Method : GET
     *
     * Parameters :
     *    BASKET_ID : required INT
     *
     */
    public function set_basket_title(Request $request, Basket $basket);

    /**
     * Route : /baskets/BASKET_ID/description/FORMAT/
     *
     * Method : POST
     *
     * Parameters :
     *    BASKET_ID : required INT
     *
     */
    public function set_basket_description(Request $request, Basket $basket);

    /**
     * Route : /publications/list/FORMAT/
     *
     * Method : POST
     *
     * Parameters :
     *
     */
    public function search_publications(Request $request, User $user);

    /**
     * Route : /publications/PUBLICATION_ID/remove/FORMAT/
     *
     * Method : GET
     *
     * Parameters :
     *    PUBLICATION_ID : required INT
     *
     */
    public function remove_publications(Request $request, $publication_id);

    /**
     * Route : /publications/PUBLICATION_ID/content/FORMAT/
     *
     * Method : GET
     *
     * Parameters :
     *    PUBLICATION_ID : required INT
     *
     */
    public function get_publication(Request $request, $publication_id, User $user);

    public function get_publications(Request $request, User $user);

    public function get_feed_entry(Request $request, $entry, User $user);
    /**
     * Route : /users/search/FORMAT/
     *
     * Method : POST-GET
     *
     * Parameters :
     *
     */
    public function search_users(Request $request);

    /**
     * Route : /users/USER_ID/access/FORMAT/
     *
     * Method : GET
     *
     * Parameters :
     *    USER_ID : required INT
     *
     */
    public function get_user_acces(Request $request, $usr_id);

    public function add_record(Application $app, Request $request);

    /**
     * Route : /users/add/FORMAT/
     *
     * Method : POST
     *
     * Parameters :
     *
     */
    public function add_user(Request $request);

    public function get_error_message(Request $request, $error, $message);

    public function get_error_code(Request $request, $code);
}
