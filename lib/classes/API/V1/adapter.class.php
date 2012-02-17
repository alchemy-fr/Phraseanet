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
use Symfony\Component\HttpFoundation\Request;

class API_V1_adapter extends API_V1_Abstract
{

  /**
   * API Version
   *
   * @var string
   */
  protected $version = '1.1';

  /**
   * Appbox where the API works
   *
   * @var appbox
   */
  protected $appbox;

  /**
   * Phraseanet Core
   *
   * @var \Alchemy\Phrasea\Core
   */
  protected $core;

  /**
   * API constructor
   *
   * @param string $auth_token Authentification Token
   * @param appbox $appbox Appbox object
   * @return API_V1_adapter
   */
  public function __construct($auth_token, appbox &$appbox, Alchemy\Phrasea\Core $core)
  {
    $this->appbox = $appbox;
    $this->core = $core;

    return $this;
  }

  /**
   * Retrieve  http status error code according to the message
   * @param Request $request
   * @param string $error
   * @return API_V1_result `
   */
  public function get_error_message(Request $request, $error)
  {
    $result = new API_V1_result($request, $this);
    $result->set_error_message($error);

    return $result;
  }

  /**
   * Retrieve  http status error message according to the http status error code
   * @param Request $request
   * @param int $code
   * @return API_V1_result
   */
  public function get_error_code(Request $request, $code)
  {
    $result = new API_V1_result($request, $this);
    $result->set_error_code($code);

    return $result;
  }

  /**
   * Get the current version
   *
   * @return string
   */
  public function get_version()
  {
    return $this->version;
  }

  /**
   * Get an API_V1_result containing the databoxes
   *
   * @param Request $request
   * @param string $response_type
   * @return API_V1_result
   */
  public function get_databoxes(Request $request)
  {
    $result = new API_V1_result($request, $this);

    $result->set_datas(array("databoxes" => $this->list_databoxes()));

    return $result;
  }

  /**
   * Get an API_V1_result containing the collections of a databox
   *
   * @param Request $request
   * @param int $databox_id
   * @param string $response_type
   * @return API_V1_result
   */
  public function get_databox_collections(Request $request, $databox_id)
  {
    $result = new API_V1_result($request, $this);

    $result->set_datas(
      array(
        "collections" => $this->list_databox_collections(
          $this->appbox->get_databox($databox_id)
        )
      )
    );

    return $result;
  }

  /**
   * Get an API_V1_result containing the status of a databox
   *
   * @param Request $request
   * @param int $databox_id
   * @param string $response_type
   * @return API_V1_result
   */
  public function get_databox_status(Request $request, $databox_id)
  {
    $result = new API_V1_result($request, $this);

    $result->set_datas(
      array(
        "status" =>
        $this->list_databox_status(
          $this->appbox->get_databox($databox_id)->get_statusbits()
        )
      )
    );

    return $result;
  }

  /**
   * Get an API_V1_result containing the metadatas of a databox
   *
   * @param Request $request
   * @param int $databox_id
   * @param string $response_type
   * @return API_V1_result
   */
  public function get_databox_metadatas(Request $request, $databox_id)
  {
    $result = new API_V1_result($request, $this);

    $result->set_datas(
      array(
        "metadatas" =>
        $this->list_databox_metadatas_fields(
          $this->appbox->get_databox($databox_id)
            ->get_meta_structure()
        )
      )
    );

    return $result;
  }

  /**
   * Get an API_V1_result containing the terms of use of a databox
   *
   * @param Request $request
   * @param int $databox_id
   * @param string $response_type
   * @return API_V1_result
   */
  public function get_databox_terms(Request $request, $databox_id)
  {
    $result = new API_V1_result($request, $this);

    $result->set_datas(
      array(
        "termsOfUse" =>
        $this->list_databox_terms($this->appbox->get_databox($databox_id))
      )
    );

    return $result;
  }

  /**
   * Get an API_V1_result containing the results of a records search
   *
   * @param Request $request
   * @param int $databox_id
   * @param string $response_type
   * @return API_V1_result
   */
  public function search_records(Request $request)
  {
    $session  = $this->appbox->get_session();
    $user     = User_Adapter::getInstance($session->get_usr_id(), $this->appbox);
    $registry = $this->appbox->get_registry();
    $result   = new API_V1_result($request, $this);

    $search_type = ($request->get('search_type')
      && in_array($request->get('search_type'), array(0, 1))) ?
      $request->get('search_type') : 0;

    $record_type = ($request->get('record_type')
      && in_array(
        $request->get('record_type')
        , array('audio', 'video', 'image', 'document', 'flash'))
      ) ?
      $request->get('record_type') : '';

    $params = array(
      'fields' => is_array($request->get('fields')) ? $request->get('fields') : array()
      , 'status' => is_array($request->get('status')) ? $request->get('status') : array()
      , 'bases' => is_array($request->get('bases')) ? $request->get('bases') : array()
      , 'search_type' => $search_type
      , 'recordtype'  => $record_type
      , 'datemin'     => $request->get('datemin') ? : ''
      , 'datemax'     => $request->get('datemax') ? : ''
      , 'datefield'   => $request->get('datefield') ? : ''
      , 'sort'        => $request->get('sort') ? : ''
      , 'ord'         => $request->get('ord') ? : ''
      , 'stemme'      => $request->get('stemme') ? : ''
      , 'per_page'    => $request->get('per_page') ? : 10
      , 'query'       => $request->get('query') ? : ''
      , 'page'        => (int) ($request->get('page') ? : 0)
    );

    if (is_array($request->get('bases')) === false)
    {
      $params['bases'] = array();
      foreach ($this->appbox->get_databoxes() as $databox)
      {
        foreach ($databox->get_collections() as $collection)
          $params['bases'][] = $collection->get_base_id();
      }
    }

    $options = new searchEngine_options();

    $options->set_bases($params['bases'], $user->ACL());
    if (!is_array($params['fields']))
      $params['fields'] = array();
    $options->set_fields($params['fields']);
    if (!is_array($params['status']))
      $params['status'] = array();
    $options->set_status($params['status']);
    $options->set_search_type($params['search_type']);
    $options->set_record_type($params['recordtype']);
    $options->set_min_date($params['datemin']);
    $options->set_max_date($params['datemax']);
    $options->set_date_fields(explode('|', $params['datefield']));
    $options->set_sort($params['sort'], $params['ord']);
    $options->set_use_stemming($params['stemme']);

    $perPage       = $params['per_page'];
    $search_engine = new searchEngine_adapter($registry);
    $search_engine->set_options($options);


    /**
     * @todo ameliorer this shit
     */
    if ($params['page'] < 1)
    {
      $search_engine->set_is_first_page(true);
      $params['page'] = 1;
    }

    $search_engine->reset_cache();

    $search_result = $search_engine->query_per_page($params['query'], $params["page"], $perPage);

    $ret = array(
      'total_pages'       => $search_result->get_total_pages()
      , 'current_page'      => $search_result->get_current_page()
      , 'available_results' => $search_result->get_count_available_results()
      , 'total_results'     => $search_result->get_count_total_results()
      , 'error'             => $search_result->get_error()
      , 'warning'           => $search_result->get_warning()
      , 'query_time'        => $search_result->get_query_time()
      , 'search_indexes'    => $search_result->get_search_indexes()
      , 'suggestions'       => $search_result->get_suggestions()
      , 'results'           => array()
      , 'query' => $search_engine->get_query()
    );

    foreach ($search_result->get_datas()->get_elements() as $record)
    {
      $ret['results'][] = $this->list_record($record);
    }

    /**
     * @todo donner des highlights
     */
    $result->set_datas($ret);

    return $result;
  }

  /**
   * Get an API_V1_result containing the baskets where the record is in
   *
   * @param Request $request
   * @param int $databox_id
   * @param int $record_id
   * @param string $response_type
   * @return API_V1_result
   */
  public function get_record_related(Request $request, $databox_id, $record_id)
  {
    $result = new API_V1_result($request, $this);

    $containers = $this->appbox
      ->get_databox($databox_id)
      ->get_record($record_id)
      ->get_container_baskets();

    $ret = array();
    foreach ($containers as $basket)
    {
      $ret[$basket->getId()] = $this->list_basket($basket);
    }

    $result->set_datas(array("baskets" => $ret));

    return $result;
  }

  /**
   * Get an API_V1_result containing the record metadatas
   *
   * @param Request $request
   * @param int $databox_id
   * @param int $record_id
   * @param string $response_type
   * @return API_V1_result
   */
  public function get_record_metadatas(Request $request, $databox_id, $record_id)
  {
    $result = new API_V1_result($request, $this);

    $record = $this->appbox->get_databox($databox_id)->get_record($record_id);

    $result->set_datas(
      array(
        "metadatas" => $this->list_record_caption($record->get_caption())
      )
    );

    return $result;
  }

  /**
   * Get an API_V1_result containing the record status
   *
   * @param Request $request
   * @param int $databox_id
   * @param int $record_id
   * @param string $response_type
   * @return API_V1_result
   */
  public function get_record_status(Request $request, $databox_id, $record_id)
  {
    $result = new API_V1_result($request, $this);

    $record = $this->appbox
      ->get_databox($databox_id)
      ->get_record($record_id);

    $result->set_datas(
      array(
        "status" =>
        $this->list_record_status(
          $this->appbox->get_databox($databox_id)
          , $record->get_status()
        )
      )
    );

    return $result;
  }

  /**
   * Get an API_V1_result containing the record embed files
   *
   * @param Request $request
   * @param int $databox_id
   * @param int $record_id
   * @param string $response_type
   * @return API_V1_result
   */
  public function get_record_embed(Request $request, $databox_id, $record_id)
  {

    $result = new API_V1_result($request, $this);

    $record = $this->appbox->get_databox($databox_id)->get_record($record_id);

    $ret = array();
    foreach ($record->get_embedable_medias() as $name => $media)
    {
      $ret[$name] = $this->list_embedable_media($media, $this->appbox->get_registry());
    }

    $result->set_datas(array("embed" => $ret));

    return $result;
  }

  public function set_record_metadatas(Request $request, $databox_id, $record_id)
  {
    $result = new API_V1_result($request, $this);
    $record = $this->appbox->get_databox($databox_id)->get_record($record_id);

    try
    {
      $metadatas = $request->get('metadatas');

      if (!is_array($metadatas))
      {
        throw new Exception('Metadatas should be an array');
      }

      foreach ($metadatas as $metadata)
      {
        if (!is_array($metadata))
        {
          throw new Exception('Each Metadata value should be an array');
        }
      }

      $record->set_metadatas($metadatas);
      $result->set_datas(array("metadatas" => $this->list_record_caption($record->get_caption())));
    }
    catch (Exception $e)
    {
      $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST);
    }

    return $result;
  }

  public function set_record_status(Request $request, $databox_id, $record_id)
  {
    $result      = new API_V1_result($request, $this);
    $databox     = $this->appbox->get_databox($databox_id);
    $record      = $databox->get_record($record_id);
    $status_bits = $databox->get_statusbits();

    try
    {
      $status = $request->get('status');

      $datas = strrev($record->get_status());

      if (!is_array($status))
        throw new API_V1_exception_badrequest();
      foreach ($status as $n => $value)
      {
        if ($n > 63 || $n < 4)
          throw new API_V1_exception_badrequest();
        if (!in_array($value, array('0', '1')))
          throw new API_V1_exception_badrequest();
        if (!isset($status_bits[$n]))
          throw new API_V1_exception_badrequest ();

        $datas = substr($datas, 0, ($n - 1)) . $value . substr($datas, ($n + 1));
      }
      $datas = strrev($datas);

      $record->set_binary_status($datas);
      $result->set_datas(array(
        "status" =>
        $this->list_record_status($databox, $record->get_status())
        )
      );
    }
    catch (Exception $e)
    {
      $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST);
    }

    return $result;
  }

  /**
   * Move a record to another collection
   *
   * @param Request $request
   * @param int $databox_id
   * @param int $record_id
   * @return API_V1_result
   */
  public function set_record_collection(Request $request, $databox_id, $record_id)
  {
    $result  = new API_V1_result($request, $this);
    $databox = $this->appbox->get_databox($databox_id);
    $record  = $databox->get_record($record_id);

    try
    {
      $collection = collection::get_from_base_id($request->get('base_id'));

      $record->move_to_collection($collection, $this->appbox);
      $result->set_datas(array("record" => $this->list_record($record)));
    }
    catch (Exception $e)
    {
      $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST);
    }

    return $result;
  }

  /**
   * Return detailed informations about one record
   *
   * @param Request $request
   * @param int $databox_id
   * @param int $record_id
   * @return API_V1_result
   */
  public function get_record(Request $request, $databox_id, $record_id)
  {
    $result  = new API_V1_result($request, $this);
    $databox = $this->appbox->get_databox($databox_id);
    try
    {
      $record = $databox->get_record($record_id);
      $result->set_datas(array('record' => $this->list_record($record)));
    }
    catch (Exception $e)
    {
      $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST);
    }

    return $result;
  }

  /**
   * @todo
   *
   * @param Request $request
   * @param int $databox_id
   * @param int $record_id
   */
  public function add_record_tobasket(Request $request, $databox_id, $record_id)
  {

  }

  /**
   * Return the baskets list of the authenticated user
   *
   * @param Request $request
   * @return API_V1_result
   */
  public function search_baskets(Request $request)
  {
    $result = new API_V1_result($request, $this);

    $usr_id  = $session = $this->appbox->get_session()->get_usr_id();

    $result->set_datas(array('baskets' => $this->list_baskets($usr_id)));

    return $result;
  }

  /**
   * Return a baskets list
   *
   * @param int $usr_id
   * @return array
   */
  protected function list_baskets($usr_id)
  {
    $em   = $this->core->getEntityManager();
    $repo = $em->getRepository('\Entities\Basket');
    /* @var $repo \Repositories\BasketRepository */

    $baskets = $repo->findActiveByUser($this->core->getAuthenticatedUser());

    $ret = array();
    foreach ($baskets as $basket)
    {
      $ret[$basket->getId()] = $this->list_basket($basket);
    }

    return $ret;
  }

  /**
   * Create a new basket
   *
   * @param Request $request
   * @return API_V1_result
   */
  public function create_basket(Request $request)
  {
    $result = new API_V1_result($request, $this);

    $name = $request->get('name');

    if (trim(strip_tags($name)) === '')
      throw new API_V1_exception_badrequest ();

    $user = $this->core->getAuthenticatedUser();

    $Basket = new \Entities\Basket();
    $Basket->setOwner($user);
    $Basket->setName($name);

    $em = $this->core->getEntityManager();
    $em->persist($Basket);
    $em->flush();

    $ret = array();
    $ret[$Basket->getId()] = $this->list_basket($Basket);
    $result->set_datas(array("basket" => $ret));

    return $result;
  }

  /**
   * Delete a basket
   *
   * @param Request $request
   * @param int $basket_id
   * @return array
   */
  public function delete_basket(Request $request, $basket_id)
  {
    $result = new API_V1_result($request, $this);

    $em         = $this->core->getEntityManager();
    $repository = $em->getRepository('\Entities\Basket');


    /* @var $repository \Repositories\BasketRepository */

    $Basket = $repository->findUserBasket($basket_id, $this->core->getAuthenticatedUser(), true);
    $em->remove($Basket);
    $em->flush();

    return $this->search_baskets($request);
  }

  /**
   * Retrieve a basket
   *
   * @param Request $request
   * @param int $basket_id
   * @return API_V1_result
   */
  public function get_basket(Request $request, $basket_id)
  {
    $result = new API_V1_result($request, $this);

    $em         = $this->core->getEntityManager();
    $repository = $em->getRepository('\Entities\Basket');

    /* @var $repository \Repositories\BasketRepository */

    $Basket = $repository->findUserBasket($basket_id, $this->core->getAuthenticatedUser(), false);

    $result->set_datas(
      array("basket_elements" =>
        array(
          $Basket->getId() => $this->list_basket_content($Basket)
        )
      )
    );

    return $result;
  }

  /**
   * Retrieve elements of one basket
   *
   * @param \Entities\Basket $Basket
   * @return type
   */
  protected function list_basket_content(\Entities\Basket $Basket)
  {
    $ret = $this->list_basket($Basket);

    $ret['basket_elements'] = array();

    foreach ($Basket->getElements() as $basket_element)
    {
      $ret['basket_elements'][$basket_element->getId()] = $this->list_basket_element($basket_element);
    }

    return $ret;
  }

  /**
   * Retrieve detailled informations about a basket element
   *
   * @param \Entities\BasketElement $basket_element
   * @return type
   */
  protected function list_basket_element(\Entities\BasketElement $basket_element)
  {
    $ret = array(
      'basket_element_id' => $basket_element->getId()
      , 'order'             => $basket_element->getOrd()
      , 'record'            => $this->list_record($basket_element->getRecord())
      , 'validation_item'   => !!$basket_element->getValidationDatas()
    );

    if ($basket_element->getValidationDatas())
    {
      $choices = array();
      $agreement = $note      = null;

      foreach ($basket_element->getValidationDatas() as $validation_datas)
      {
        $user                     = $validation_datas->getParticipant()->getUser();
        /* @var $validation_datas Entities\ValidationData */
        $choices[$user->get_id()] = array(
          'usr_id'     => $user->get_id(),
          'usr_name'   => $user->get_display_name(),
          'is_mine'    => $user->get_id() == $this->core->getAuthenticatedUser()->get_id(),
          'agreement'  => $validation_datas->getAgreement(),
          'updated_on' => $validation_datas->getUpdated()->format(DATE_ATOM),
          'note'       => $validation_datas->getNote()
        );

        if ($user->get_id() == $this->core->getAuthenticatedUser()->get_id())
        {
          $agreement = $validation_datas->getAgreement();
          $note      = $validation_datas->getNote();
        }
      }

      $ret['choices']   = $choices();
      $ret['agreement'] = $agreement();
      $ret['note']      = $note();
    }

    return $ret;
  }

  /**
   * Change the name of one basket
   *
   * @param Request $request
   * @param int $basket_id
   * @return API_V1_result
   */
  public function set_basket_title(Request $request, $basket_id)
  {
    $result = new API_V1_result($request, $this);

    $name = $request->get('name');

    $em         = $this->core->getEntityManager();
    $repository = $em->getRepository('\Entities\Basket');


    /* @var $repository \Repositories\BasketRepository */

    $Basket = $repository->findUserBasket($basket_id, $this->core->getAuthenticatedUser(), true);
    $Basket->setName($name);

    $em->merge($Basket);
    $em->flush();

    $result->set_datas(
      array(
        "basket" =>
        array($Basket->getId() => $this->list_basket_content($Basket)
        )
      )
    );

    return $result;
  }

  /**
   * Change the description of one basket
   *
   * @param Request $request
   * @param type $basket_id
   * @return API_V1_result
   */
  public function set_basket_description(Request $request, $basket_id)
  {
    $result = new API_V1_result($request, $this);

    $desc = $request->get('description');

    $em         = $this->core->getEntityManager();
    $repository = $em->getRepository('\Entities\Basket');


    /* @var $repository \Repositories\BasketRepository */

    $Basket = $repository->findUserBasket($basket_id, $this->core->getAuthenticatedUser(), true);
    $Basket->setDescription($desc);

    $em->merge($Basket);
    $em->flush();

    $result->set_datas(
      array(
        "basket" =>
        array($Basket->getId() => $this->list_basket_content($Basket)
        )
      )
    );

    return $result;
  }

  /**
   * List all avalaible feeds
   *
   * @param Request $request
   * @param User_Adapter $user
   * @return API_V1_result
   */
  public function search_publications(Request $request, User_Adapter &$user)
  {
    $result = new API_V1_result($request, $this);

    $coll = Feed_Collection::load_all($this->appbox, $user);

    $datas = array();
    foreach ($coll->get_feeds() as $feed)
    {
      $datas[$feed->get_id()] = $this->list_publication($feed, $user);
    }

    $result->set_datas(array("feeds" => $datas));

    return $result;
  }

  /**
   * @todo
   *
   * @param Request $request
   * @param int $publication_id
   */
  public function remove_publications(Request $request, $publication_id)
  {

  }

  /**
   * Retrieve one feed
   *
   * @param Request $request
   * @param int $publication_id
   * @param User_Adapter $user
   * @return API_V1_result
   */
  public function get_publication(Request $request, $publication_id, User_Adapter &$user)
  {
    $result = new API_V1_result($request, $this);

    $feed = Feed_Adapter::load_with_user($this->appbox, $user, $publication_id);

    $offset_start = (int) ($request->get('offset_start') ? : 0);
    $per_page     = (int) ($request->get('per_page') ? : 5);

    $per_page = (($per_page >= 1) && ($per_page <= 20)) ? $per_page : 5;

    $datas = array(
      'feed'         => $this->list_publication($feed, $user)
      , 'offset_start' => $offset_start
      , 'per_page'     => $per_page
      , 'entries'      => $this->list_publications_entries($feed, $offset_start, $per_page)
    );

    $result->set_datas($datas);

    return $result;
  }

  /**
   * Retrieve detailled informations about one feed
   *
   * @param Feed_Adapter $feed
   * @param type $user
   * @return array
   */
  protected function list_publication(Feed_Adapter $feed, $user)
  {
    return array(
      'id'            => $feed->get_id()
      , 'title'         => $feed->get_title()
      , 'subtitle'      => $feed->get_subtitle()
      , 'total_entries' => $feed->get_count_total_entries()
      , 'icon'          => $feed->get_icon_url()
      , 'public'        => $feed->is_public()
      , 'is_mine'       => $feed->is_owner($user)
      , 'created_on'    => $feed->get_created_on()->format(DATE_ATOM)
      , 'updated_on'    => $feed->get_updated_on()->format(DATE_ATOM)
    );
  }

  /**
   * Retrieve all entries of one feed
   *
   * @param Feed_Adapter $feed
   * @param int $offset_start
   * @param int $how_many
   * @return array
   */
  protected function list_publications_entries(Feed_Adapter $feed, $offset_start = 0, $how_many = 5)
  {

    $entries = $feed->get_entries($offset_start, $how_many)->get_entries();

    $out = array();
    foreach ($entries as $entry)
    {
      $out[$entry->get_id()] = $this->list_publication_entry($entry);
    }

    return array(
      'offset_start' => $offset_start
      , 'entries'      => $out
    );
  }

  /**
   * Retrieve detailled information about one feed entry
   *
   * @param Feed_Entry_Adapter $entry
   * @return array
   */
  protected function list_publication_entry(Feed_Entry_Adapter $entry)
  {
    $items = array();
    foreach ($entry->get_content() as $item)
    {
      $items[] = $this->list_publication_entry_item($item);
    }

    return array(
      'author_email' => $entry->get_author_email()
      , 'author_name'  => $entry->get_author_name()
      , 'created_on'   => $entry->get_created_on()->format(DATE_ATOM)
      , 'updated_on'   => $entry->get_updated_on()->format(DATE_ATOM)
      , 'title'        => $entry->get_title()
      , 'subtitle'     => $entry->get_subtitle()
      , 'items'        => $items
    );
  }

  /**
   * Retrieve detailled informations about one feed  entry item
   *
   * @param Feed_Entry_Item $item
   * @return array
   */
  protected function list_publication_entry_item(Feed_Entry_Item $item)
  {
    $datas = array(
      'item_id' => $item->get_id()
      , 'record'  => $this->list_record($item->get_record())
    );

    return $datas;
  }

  /**
   * @todo
   * @param Request $request
   */
  public function search_users(Request $request)
  {

  }

  /**
   * @todo
   * @param Request $request
   * @param int $usr_id
   */
  public function get_user_acces(Request $request, $usr_id)
  {

  }

  /**
   * @todo
   * @param Request $request
   */
  public function add_user(Request $request)
  {

  }

  /**
   * @retrieve detailled informations about one suddef
   *
   * @param media_subdef $media
   * @return array
   */
  protected function list_embedable_media(media_subdef &$media, registryInterface &$registry)
  {
    if ($media->get_permalink() instanceof media_Permalink_Adapter)
      $permalink = $this->list_permalink($media->get_permalink(), $registry);
    else
      $permalink = null;

    return array(
      'permalink'   => $permalink
      , 'height'      => $media->get_height()
      , 'width'       => $media->get_width()
      , 'filesize'    => $media->get_size()
      , 'player_type' => $media->get_type()
      , 'mime_type'   => $media->get_mime()
    );
  }

  /**
   * Retrieve detailled information about one permalink
   *
   * @param media_Permalink_Adapter $permalink
   * @param registryInterface $registry
   * @return type
   */
  protected function list_permalink(media_Permalink_Adapter &$permalink, registryInterface &$registry)
  {
    return array(
      'created_on'    => $permalink->get_created_on()->format(DATE_ATOM)
      , 'id'            => $permalink->get_id()
      , 'is_activated'  => $permalink->get_is_activated()
      , 'label'         => $permalink->get_label()
      , 'last_modified' => $permalink->get_last_modified()->format(DATE_ATOM)
      , 'page_url'      => $permalink->get_page($registry)
      , 'url'           => $permalink->get_url($registry)
    );
  }

  /**
   * Retrieve detailled information about one status
   *
   * @param databox $databox
   * @param string $status
   * @return array
   */
  protected function list_record_status(databox $databox, $status)
  {
    $status = strrev($status);
    $ret    = array();
    foreach ($databox->get_statusbits() as $bit => $status_datas)
    {
      $ret[$bit] = array('bit'   => $bit, 'state' => !!substr($status, ($bit - 1), 1));
    }

    return $ret;
  }

  /**
   * List all field about a specified caption
   *
   * @param caption_record $caption
   * @return array
   */
  protected function list_record_caption(caption_record $caption)
  {
    $ret = array();
    foreach ($caption->get_fields() as $field)
    {
      foreach ($field->get_values() as $value)
      {
        $ret[$value->getId()] = $this->list_record_caption_field($value, $field);
      }
    }

    return $ret;
  }

  /**
   * Retrieve information about a caption field
   *
   * @param caption_field $field
   * @return array
   */
  protected function list_record_caption_field(caption_Field_Value $value, caption_field $field)
  {
    /**
     * @todo  ajouter une option pour avoir les values serialisées
     *        dans un cas multi
     */

    return array(
      'meta_id'           => $value->getId()
      , 'meta_structure_id' => $field->get_meta_struct_id()
      , 'name'              => $field->get_name()
      , 'value'             => $value->getValue()
    );
  }

  /**
   * Retirve information about one basket
   *
   * @param \Entities\Basket $basket
   * @return array
   */
  protected function list_basket(\Entities\Basket $basket)
  {
    $ret = array(
      'created_on'    => $basket->getCreated()->format(DATE_ATOM)
      , 'description'   => (string) $basket->getDescription()
      , 'name'          => $basket->getName()
      , 'pusher_usr_id' => $basket->getPusherId()
      , 'ssel_id'       => $basket->getId()
      , 'updated_on'    => $basket->getUpdated()->format(DATE_ATOM)
      , 'unread'        => !$basket->getIsRead()
    );

    if ($basket->getValidation())
    {
      $users = array();

      foreach ($basket->getValidation()->getParticipants() as $participant)
      {
        /* @var $participant \Entities\ValidationParticipant */
        $user = $participant->getUser();

        $users[$user->get_id()] = array(
          'usr_id'         => $user->get_id(),
          'usr_name'       => $user->get_display_name(),
          'confirmed'      => $participant->getIsConfirmed(),
          'can_agree'      => $participant->getCanAgree(),
          'can_see_others' => $participant->getCanSeeOthers()
        );
      }

      $expires_on_atom = $basket->getValidation()->getExpires();

      if ($expires_on_atom instanceof DateTime)
        $expires_on_atom = $expires_on_atom->format(DATE_ATOM);

      $user = \User_Adapter::getInstance($this->appbox->get_session()->get_usr_id(), $this->appbox);

      $ret = array_merge(
        array(
        'validation_users'     => $users
        , 'validation_end_date'  => $expires_on_atom
        , 'validation_infos'     => $basket->getValidation()->getValidationString($user)
        , 'validation_confirmed' => $basket->getValidation()->getParticipant($user)->getIsConfirmed()
        , 'mine'                 => $basket->getValidation()->isInitiator($user)
        )
        , $ret
      );
    }

    return $ret;
  }

  /**
   * Retrieve detailled informations about one record
   *
   * @param record_adapter $record
   * @return array
   */
  protected function list_record(record_adapter $record)
  {
    return array(
      'databox_id'             => $record->get_sbas_id()
      , 'record_id'              => $record->get_record_id()
      , 'mime_type'              => $record->get_mime()
      , 'title'                  => $record->get_title()
      , 'original_name'          => $record->get_original_name()
      , 'last_modification'      => $record->get_modification_date()->format(DATE_ATOM)
      , 'created_on'             => $record->get_creation_date()->format(DATE_ATOM)
      , 'collection_id'          => phrasea::collFromBas($record->get_base_id())
      , 'sha256'                 => $record->get_sha256()
      , 'thumbnail'              => $this->list_embedable_media(
        $record->get_thumbnail(), registry::get_instance()
      )
      , 'technical_informations' => $record->get_technical_infos()
      , 'phrasea_type'           => $record->get_type()
      , 'uuid'                   => $record->get_uuid()
    );
  }

  /**
   * List all databoxes of the current appbox
   *
   * @return array
   */
  protected function list_databoxes()
  {
    $ret = array();
    foreach ($this->appbox->get_databoxes() as $databox)
    {
      $ret[$databox->get_sbas_id()] = $this->list_databox($databox);
    }

    return $ret;
  }

  /**
   * Retrieve CGU's for the specified databox
   *
   * @param databox $databox
   * @return array
   */
  protected function list_databox_terms(databox $databox)
  {
    $ret = array();
    foreach ($databox->get_cgus() as $locale => $array_terms)
    {
      $ret[$locale] = array('locale' => $locale, 'terms'  => $array_terms['value']);
    }

    return $ret;
  }

  /**
   * Retrieve detailled informations about one databox
   * @param databox $databox
   * @return array
   */
  protected function list_databox(databox $databox)
  {
    $ret = array();

    $ret['databox_id'] = $databox->get_sbas_id();
    $ret['name']       = $databox->get_viewname();
    $ret['version']    = $databox->get_version();

    return $ret;
  }

  /**
   * List all available collections for a specified databox
   *
   * @param databox $databox
   * @return array
   */
  protected function list_databox_collections(databox $databox)
  {
    $ret = array();

    foreach ($databox->get_collections() as $collection)
    {
      $ret[$collection->get_coll_id()] = $this->list_collection($collection);
    }

    return $ret;
  }

  /**
   * Retrieve detailled informations about one collection
   *
   * @param collection $collection
   * @return array
   */
  protected function list_collection(collection $collection)
  {
    $ret = array(
      'base_id'       => $collection->get_base_id()
      , 'coll_id'       => $collection->get_coll_id()
      , 'name'          => $collection->get_name()
      , 'record_amount' => $collection->get_record_amount()
    );

    return $ret;
  }

  /**
   * Retrieve informations for a list of status
   *
   * @param array $status
   * @return array
   */
  protected function list_databox_status(array $status)
  {
    $ret = array();
    foreach ($status as $n => $datas)
    {
      $ret[$n] = array(
        'bit'        => $n
        , 'label_on'   => $datas['labelon']
        , 'label_off'  => $datas['labeloff']
        , 'img_on'     => $datas['img_on']
        , 'img_off'    => $datas['img_off']
        , 'searchable' => $datas['searchable']
        , 'printable'  => $datas['printable']
      );
    }

    return $ret;
  }

  /**
   * List all metadatas field using a databox meta structure
   *
   * @param databox_descriptionStructure $meta_struct
   * @return array
   */
  protected function list_databox_metadatas_fields(databox_descriptionStructure $meta_struct)
  {
    $ret = array();
    foreach ($meta_struct as $meta)
    {
      $ret[$meta->get_id()] = $this->list_databox_metadata_field_properties($meta);
    }

    return $ret;
  }

  /**
   * Retirve informations about one databox metadata field
   *
   * @param databox_field $databox_field
   * @return array
   */
  protected function list_databox_metadata_field_properties(databox_field $databox_field)
  {
    $ret = array(
      'id'               => $databox_field->get_id()
      , 'namespace'        => $databox_field->get_metadata_namespace()
      , 'source'           => $databox_field->get_metadata_source()
      , 'tagname'          => $databox_field->get_metadata_tagname()
      , 'name'             => $databox_field->get_name()
      , 'separator'        => $databox_field->get_separator()
      , 'thesaurus_branch' => $databox_field->get_tbranch()
      , 'type'             => $databox_field->get_type()
      , 'indexable'        => $databox_field->is_indexable()
      , 'multivalue'       => $databox_field->is_multi()
      , 'readonly'         => $databox_field->is_readonly()
      , 'required'         => $databox_field->is_required()
    );

    return $ret;
  }

}
