<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . "/../../../classes/DailymotionWithoutOauth2.php";

use Symfony\Component\HttpFoundation\Request;

class Bridge_Api_Dailymotion extends Bridge_Api_Abstract implements Bridge_Api_Interface
{
    const OAUTH2_TOKEN_ENDPOINT = "https://api.dailymotion.com/oauth/token";
    const OAUTH2_AUTHORIZE_ENDPOINT = "https://api.dailymotion.com/oauth/authorize";
    const ELEMENT_TYPE_VIDEO = 'video';
    const CONTAINER_TYPE_PLAYLIST = 'playlist';
    const AUTH_TYPE = 'OAuth2';
    const AUTH_VIDEO_DURATION = 3600;
    const AUTH_VIDEO_SIZE = 2147483648; //in bytes = 2GB
    /**
     * @see http://www.dailymotion.com/doc/api/obj-video.html
     */
    const UPLOAD_STATE_PROCESSING = 'processing';
    const UPLOAD_STATE_READY = 'ready';
    const UPLOAD_STATE_WAITING = 'waiting';
    const UPLOAD_STATE_DONE = 'published';
    const UPLOAD_STATE_DELETED = 'deleted';
    const UPLOAD_STATE_REJECTED = 'rejected';
    const UPLOAD_STATE_ENCODING_ERROR = 'encoding_error';

    /**
     *
     * @var DailymotionWithoutOauth2
     */
    protected $_api;

    /**
     * No transport for Dailymotion SDK
     * Store oauth token here once you get it
     * And pass it to the request as one of parameter
     * @var string
     */
    private $oauth_token;

    /**
     *
     * @return Array
     */
    public function connect()
    {
        $response = parent::connect();
        $this->oauth_token = $response["auth_token"]; //set token

        return $response;
    }

    /**
     *
     * @return Bridge_Api_Dailymotion
     */
    public function reconnect()
    {
        parent::reconnect();
        $this->set_transport_authentication_params();

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_user_id()
    {
        $result = $this->_api->call("/me", ['fields' => ['id']], $this->oauth_token);

        return $result["id"];
    }

    /**
     *
     * @return string
     */
    public function get_user_name()
    {
        $result = $this->_api->call("/me", ['fields' => ['username']], $this->oauth_token);

        return $result["username"];
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return 'Dailymotion';
    }

    /**
     * @todo
     * @return string
     */
    public function get_icon_url()
    {
        return '/assets/common/images/icons/dailymotion-small.gif';
    }

    /**
     * @todo
     * @return string
     */
    public function get_image_url()
    {
        return '/assets/common/images/icons/dailymotion-logo.png';
    }

    /**
     *
     * @return string
     */
    public function get_terms_url()
    {
        return 'https://www.dailymotion.com/legal/terms';
    }

    /**
     *
     * @return string
     */
    public function get_url()
    {
        return 'http://www.dailymotion.com/';
    }

    /**
     *
     * @return string
     */
    public function get_infos()
    {
        return 'http://www.dailymotion.com/';
    }

    /**
     *
     * @return string
     */
    public function get_default_element_type()
    {
        return self::ELEMENT_TYPE_VIDEO;
    }

    /**
     *
     * @return string
     */
    public function get_default_container_type()
    {
        return self::CONTAINER_TYPE_PLAYLIST;
    }

    /**
     *
     * @return Array
     */
    public function get_element_types()
    {
        return [self::ELEMENT_TYPE_VIDEO => $this->translator->trans('Videos')];
    }

    /**
     *
     * @return Array
     */
    public function get_container_types()
    {
        return [self::CONTAINER_TYPE_PLAYLIST => $this->translator->trans('Playlists')];
    }

    public function get_oauth_token()
    {
        return $this->oauth_token;
    }

    public function set_oauth_token($oauth_token)
    {
        $this->oauth_token = $oauth_token;

        return $this;
    }

    /**
     *
     * @param  string $type
     * @return string
     */
    public function get_object_class_from_type($type)
    {
        switch ($type) {
            case self::ELEMENT_TYPE_VIDEO:
                return self::OBJECT_CLASS_ELEMENT;
                break;
            case self::CONTAINER_TYPE_PLAYLIST:
                return self::OBJECT_CLASS_CONTAINER;
                break;
            default:
                throw new Exception('Unknown type');
                break;
        }
    }

    /**
     * @todo Pagination system
     *
     * @see http://www.dailymotion.com/doc/api/advanced-api.html
     * @param  string                       $object
     * @param  int                          $offset_start
     * @param  int                          $quantity
     * @return Bridge_Api_ElementCollection
     */
    public function list_elements($object, $offset_start = 0, $quantity = 10)
    {
        switch ($object) {
            case self::ELEMENT_TYPE_VIDEO:

                $result = $this->_api->call('/me/videos', ['fields' => [
                        'created_time'
                        , 'description'
                        , 'duration'
                        , 'modified_time'
                        , 'private'
                        , 'rating'
                        , 'ratings_total'
                        , 'thumbnail_small_url'
                        , 'thumbnail_medium_url'
                        , 'title'
                        , 'url'
                        , 'views_total'
                        , 'id'
                        , 'channel'
                    ],
                    'page'              => ! $offset_start ? 1 : $offset_start,
                    'limit'             => $quantity], $this->oauth_token);
                $element_collection = new Bridge_Api_ElementCollection();
                $element_collection->set_items_per_page($result["limit"]);

                $total = sizeof($result["list"]);
                $current_page = $result["page"];
                $total_page = null;

                $element_collection->set_total_items($total);
                $element_collection->set_current_page($current_page);
                $element_collection->set_total_page($total_page);

                foreach ($result["list"] as $entry) {
                    $element_collection->add_element(new Bridge_Api_Dailymotion_Element($entry, $object));
                }

                return $element_collection;
                break;

            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $object);
                break;
        }
    }

    /**
     * @Todo recupérer la thumbnail d'une playlist
     *
     * @param  string                         $object
     * @param  int                            $offset_start
     * @param  int                            $quantity
     * @return Bridge_Api_ContainerCollection
     */
    public function list_containers($object, $offset_start = 0, $quantity = 10)
    {
        switch ($object) {
            case self::CONTAINER_TYPE_PLAYLIST:

                $username = $this->get_user_name();

                $params = ['fields' => [
                        'description'
                        , 'id'
                        , 'name'
                    ],
                    'page' => ! $offset_start ? 1 : $offset_start];
                //add quantity
                if (! ! $quantity) {
                    $params["limit"] = $quantity;
                }
                $url = sprintf('/me/%ss', $object);
                $result = $this->_api->call($url, $params, $this->oauth_token);

                $container_collection = new Bridge_Api_ContainerCollection();

                $container_collection->set_items_per_page($result["limit"]);

                $total = sizeof($result["list"]);
                $current_page = $result["limit"];
                $total_page = null;

                $container_collection->set_total_items($total);
                $container_collection->set_current_page($current_page);
                $container_collection->set_total_page($total_page);

                foreach ($result['list'] as $entry) {
                    //get 1st image
                    $list_element = $this->list_containers_content($object, $entry['id'], ['thumbnail_medium_url'], 1);
                    $elements = $list_element->get_elements();
                    $first_element = array_shift($elements);
                    $thumbnail = $first_element instanceof Bridge_Api_Dailymotion_Element ? $first_element->get_thumbnail() : '';

                    $url = $this->get_url_playlist($entry['id'], $entry['name'], $username);

                    $container_collection->add_element(new Bridge_Api_Dailymotion_Container($entry, $object, $thumbnail, $url));
                }

                return $container_collection;
                break;

            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $object);
                break;
        }
    }

    /**
     *
     * @see http://www.dailymotion.com/doc/api/obj-video.html
     *
     * @param string $object
     * @param string $object_id
     * @param array  $datas
     *
     * @return Bridge_Api_Dailymotion
     */
    public function update_element($object, $object_id, Array $datas)
    {
        $required_fields = ["title", "description", "category", "privacy"];
        foreach ($required_fields as $field) {
            if ( ! array_key_exists($field, $datas))
                throw new Bridge_Exception_ActionMandatoryField("Le paramétre " . $field . " est manquant");
        }

        $params = [
            'title'       => $datas["title"]
            , 'description' => $datas["description"]
            , 'channel'     => $datas["category"]
            , 'private'     => ! $datas["private"]
        ];

        if ( ! $this->is_valid_object_id($object_id))
            throw new Bridge_Exception_ActionInvalidObjectId($object_id);

        switch ($object) {
            case self::ELEMENT_TYPE_VIDEO :
                $url = sprintf("POST /video/%s", $object_id);
                $this->_api->call($url, $params, $this->oauth_token);
                break;
            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $object);
                break;
        }

        return $this;
    }

    /**
     *
     * @see http://www.dailymotion.com/doc/api/obj-playlist.html
     * @param  string                           $container_type
     * @param  Request                          $request
     * @return Bridge_Api_Dailymotion_Container
     */
    public function create_container($container_type, Request $request)
    {
        switch ($container_type) {
            case self::CONTAINER_TYPE_PLAYLIST:
                $url = sprintf("POST /me/%ss", $container_type);
                $playlist = $this->_api->call($url, ['name' => $request->get("name")], $this->oauth_token);

                return $playlist["id"];
                break;
            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $container_type);
                break;
        }
    }

    /**
     * @see http://www.dailymotion.com/doc/api/obj-playlist.html
     * @param  type                             $element_type
     * @param  type                             $element_id
     * @param  type                             $destination
     * @param  type                             $container_id
     * @return Bridge_Api_Dailymotion_Container
     */
    public function add_element_to_container($element_type, $element_id, $destination, $container_id)
    {
        switch ($element_type) {
            case self::ELEMENT_TYPE_VIDEO:
                switch ($destination) {
                    case self::CONTAINER_TYPE_PLAYLIST:

                        $array = [$element_id];
                        //get containers content
                        foreach ($this->list_containers_content($destination, $container_id, ['id'])->get_elements() as $element) {
                            $array[] = $element->get_id();
                        }

                        $array = array_unique($array);

                        $url = sprintf('POST /%s/%s/%ss', $destination, $container_id, $element_type);

                        $this->_api->call($url, ['ids' => implode(",", $array)], $this->oauth_token);

                        return $this->get_container_from_id(self::CONTAINER_TYPE_PLAYLIST, $container_id);
                        break;
                    default:
                        throw new Bridge_Exception_ContainerUnknown('Unknown element ' . $container);
                        break;
                }
                break;
            default:
                throw new Bridge_Exception_ElementUnknown('Unknown container ' . $element_type);
                break;
        }
    }

    /**
     *
     * @param  string $object
     * @param  string $object_id
     * @return Void
     */
    public function delete_object($object, $object_id)
    {
        $url = sprintf("DELETE /%s/%s", $object, $object_id);
        switch ($object) {
            case self::ELEMENT_TYPE_VIDEO:
                $this->_api->call($url, [], $this->oauth_token);
                break;
            case self::CONTAINER_TYPE_PLAYLIST:
                $this->_api->call($url, [], $this->oauth_token);
                break;
            default:
                throw new Bridge_Exception_ObjectUnknown('Unknown object ' . $object);
                break;
        }

        return;
    }

    /**
     *
     * @return Closure
     */
    public function acceptable_records()
    {
        return function (record_adapter $record) {
                return $record->getType() === 'video';
            };
    }

    /**
     *
     * @param Bridge_Element $element
     *
     * @return Array
     */
    public function get_element_status(Bridge_Element $element)
    {
        $url = sprintf("/%s/%s", $element->get_type(), $element->get_dist_id());

        $result = $this->_api->call($url, ['fields' => [
                'status'
            ]], $this->oauth_token);

        return $result["status"];
    }

    /**
     *
     * @param  string $status
     * @return string
     */
    public function map_connector_to_element_status($status)
    {
        switch ($status) {
            case self::UPLOAD_STATE_PROCESSING:
                return Bridge_Element::STATUS_PROCESSING_SERVER;
                break;
            case self::UPLOAD_STATE_DONE:
            case self::UPLOAD_STATE_READY:
                return Bridge_Element::STATUS_DONE;
                break;
            case self::UPLOAD_STATE_DELETED:
            case self::UPLOAD_STATE_ENCODING_ERROR:
            case self::UPLOAD_STATE_REJECTED:
                return Bridge_Element::STATUS_ERROR;
                break;
            default:
                return null;
                break;
        }
    }

    /**
     *
     * @param  string $connector_status
     * @return string
     */
    public function get_error_message_from_status($connector_status)
    {
        switch ($connector_status) {
            case self::UPLOAD_STATE_DELETED:
                return $this->translator->trans('La video a ete supprimee');
            case self::UPLOAD_STATE_REJECTED:
                return $this->translator->trans('La video a ete rejetee');
            case self::UPLOAD_STATE_ENCODING_ERROR:
                return $this->translator->trans('Erreur d\'encodage');
            case self::UPLOAD_STATE_PROCESSING:
                return $this->translator->trans('En cours d\'encodage');
            default:
                return '';
            case self::UPLOAD_STATE_DONE:
                return $this->translator->trans('OK');
        }
    }

    /**
     * Set The exception to Bridge_Exception_ActionAuthNeedReconnect
     * if exception is instance of Zend_Gdata_App_HttpException and Http code 401
     *
     * @param  Exception $e
     * @return Void
     */
    public function handle_exception(Exception $e)
    {
        if ($e instanceof DailymotionAuthException) {
            $e = new Bridge_Exception_ActionAuthNeedReconnect($e->getMessage());
        } elseif ($e instanceof DailymotionApiException || $e instanceof DailymotionAuthRequiredException) {
            $e = new Exception($e->getMessage(), $e->getCode());
        }

        return;
    }

    /**
     *
     * @param  record_adapter $record
     * @param  array          $options
     * @return string
     */
    public function upload(record_adapter $record, array $options = [])
    {
        switch ($record->getType()) {
            case self::ELEMENT_TYPE_VIDEO :
                $url_file = $this->_api->sendFile($record->get_hd_file()->getRealPath(), $this->oauth_token);
                $options = array_merge(['url'  => $url_file], $options);
                $video = $this->_api->call('POST /me/videos', $options, $this->oauth_token);

                return $video["id"];
                break;
            default:
                throw new Bridge_Exception_InvalidRecordType('Unknown format');
                break;
        }
    }

    /**
     *
     * @param string $element_id
     * @param string $object
     *
     * @return Bridge_Api_Dailymotion_Element
     */
    public function get_element_from_id($element_id, $object)
    {
        $url = sprintf("/%s/%s", $object, $element_id);

        switch ($object) {
            case self::ELEMENT_TYPE_VIDEO:
                $entry = $this->_api->call($url, ['fields' => [
                        'created_time'
                        , 'description'
                        , 'duration'
                        , 'modified_time'
                        , 'private'
                        , 'rating'
                        , 'ratings_total'
                        , 'thumbnail_small_url'
                        , 'thumbnail_medium_url'
                        , 'title'
                        , 'url'
                        , 'views_total'
                        , 'id'
                        , 'channel'
                        , 'tags'
                    ]], $this->oauth_token);

                return new Bridge_Api_Dailymotion_Element($entry, $object);
                break;
            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $object);
                break;
        }
    }

    /**
     *
     * @param  string                           $object
     * @param  string                           $element_id
     * @return Bridge_Api_Dailymotion_Container
     */
    public function get_container_from_id($object, $element_id)
    {
        $url = sprintf("/%s/%s", $object, $element_id);

        switch ($object) {
            case self::CONTAINER_TYPE_PLAYLIST:
                $entry = $this->_api->call($url, ['fields' => [
                        'description'
                        , 'id'
                        , 'name'
                    ]], $this->oauth_token);
                /**
                 * @todo Retieve thumb
                 */

                return new Bridge_Api_Dailymotion_Container($entry, $object, '');
                break;
            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $object);
                break;
        }
    }

    public function is_configured()
    {
        if (!$this->conf->get(['main', 'bridge', 'dailymotion', 'enabled'])) {
            return false;
        }

        if ('' === trim($this->conf->get(['main', 'bridge', 'dailymotion', 'client_id']))) {
            return false;
        }

        if ('' === trim($this->conf->get(['main', 'bridge', 'dailymotion', 'client_secret']))) {
            return false;
        }

        return true;
    }

    /**
     *
     * @return Bridge_Api_Dailymotion
     */
    protected function set_auth_params()
    {
        $this->_auth->set_parameters(
            [
                'client_id'      => $this->conf->get(['main', 'bridge', 'dailymotion', 'client_id'])
                , 'client_secret'  => $this->conf->get(['main', 'bridge', 'dailymotion', 'client_secret'])
                , 'redirect_uri'   => Bridge_Api::generate_callback_url($this->generator, $this->get_name())
                , 'scope'          => ''
                , 'response_type'  => 'code'
                , 'token_endpoint' => self::OAUTH2_TOKEN_ENDPOINT
                , 'auth_endpoint'  => self::OAUTH2_AUTHORIZE_ENDPOINT
            ]
        );

        return $this;
    }

    /**
     *
     * @return Bridge_Api_Dailymotion
     */
    protected function initialize_transport()
    {
        $this->_api = new DailymotionWithoutOauth2();

        return $this;
    }

    /**
     *
     * @return Bridge_Api_Dailymotion
     */
    protected function set_transport_authentication_params()
    {
        if ($this->_auth->is_connected()) {
            $signatures = $this->_auth->get_auth_signatures();
            $this->oauth_token = $signatures['auth_token'];
        }

        return $this;
    }

    /**
     *
     * @return Array
     */
    public function get_category_list()
    {
        $locale = explode("_", $this->locale);
        $result = $this->_api->call("/channels", ["language" => $locale[0]]);

        return $result["list"];
    }

    /**
     * @Override get_auth_url
     * @param  type $supp_params
     * @return type
     */
    public function get_auth_url($supp_params = [])
    {
        $params = array_merge(['display' => 'popup', 'scope'   => 'read write delete manage_playlists'], $supp_params);

        return parent::get_auth_url($params);
    }

    /**
     *
     * @param  string                       $id
     * @return Bridge_Api_ElementCollection
     */
    protected function list_containers_content($object, $id, Array $fields = [], $iteration = 0)
    {
        $url = sprintf("/%s/%s/videos", $object, $id);
        $result = $this->_api->call($url, ['fields' => $fields], $this->oauth_token);

        $element_collection = new Bridge_Api_ElementCollection();
        $element_collection->set_items_per_page($result["limit"]);

        $total = sizeof($result["list"]);
        $current_page = $result["page"];
        $total_page = null;

        $element_collection->set_total_items($total);
        $element_collection->set_current_page($current_page);
        $element_collection->set_total_page($total_page);

        $i = 0;
        foreach ($result["list"] as $entry) {
            $i ++;
            $element_collection->add_element(new Bridge_Api_Dailymotion_Element($entry, $object));
            if ($i == $iteration)
                break;
        }

        return $element_collection;
    }

    /**
     *
     * @param  string $id
     * @param  string $playlistname
     * @param  string $username
     * @return string
     */
    protected function get_url_playlist($id, $playlistname, $username)
    {
        return sprintf("%s/playlist/%s_%s_%s/", $this->get_url(), $id, $username, $playlistname);
    }

    /**
     * @todo implement in bridge_api°interface
     *
     * Check if data uploaded via the current connector is conform
     * @param  array          $datas
     * @param  record_adapter $record
     * @return array
     */
    public function check_upload_constraints(Array $datas, record_adapter $record)
    {
        $errors = $this->check_record_constraints($record);
        $check = function ($field) use (&$errors, $datas, $record) {
                $key = $record->getId();
                $required = ! ! $field["required"];
                $name = $field["name"];
                $length = (int) $field["length"];
                $length_min = (int) $field["length_min"];

                if ( ! isset($datas[$name]) || trim($datas[$name]) === '') {
                    if ($required)
                        $errors[$name . '_' . $key] = $this->translator->trans("Ce champ est obligatoire");
                } else {
                    if ($length != 0 && mb_strlen($datas[$name]) > $length)
                        $errors[$name . '_' . $key] = $this->translator->trans("Ce champ est trop long %length% caracteres max", ['%length%' => $length]);
                    if ($length_min != 0 && mb_strlen($datas[$name]) < $length_min)
                        $errors[$name . '_' . $key] = $this->translator->trans("Ce champ est trop court %length% caracteres min", ['%length%' => $length_min]);
                }
            };

        array_map($check, $this->get_fields());

        return $errors;
    }

    public function check_update_constraints(Array $datas)
    {
        $errors = [];
        $check = function ($field) use (&$errors, $datas) {
                $required = ! ! $field["required"];
                $name = $field["name"];
                $length = (int) $field["length"];
                $length_min = (int) $field["length_min"];

                if ( ! isset($datas[$name]) || trim($datas[$name]) === '') {
                    if ($required)
                        $errors[$name] = $this->translator->trans("Ce champ est obligatoire");
                } else {
                    if ($length != 0 && mb_strlen($datas[$name]) > $length)
                        $errors[$name] = $this->translator->trans("Ce champ est trop long %length% caracteres max", ['%length%' => $length]);
                    if ($length_min != 0 && mb_strlen($datas[$name]) < $length_min)
                        $errors[$name] = $this->translator->trans("Ce champ est trop court %length% caracteres min", ['%length%' => $length_min]);
                }
            };

        array_map($check, $this->get_fields());

        return $errors;
    }

    /**
     * Returns dats needed for an uploaded record
     *
     * @param Request        $request
     * @param record_adapter $record
     *
     * @return array
     */
    public function get_upload_datas(Request $request, record_adapter $record)
    {
        $key = $record->getId();
        $datas = [
            'title'       => $request->get('title_' . $key),
            'description' => $request->get('description_' . $key),
            'tag'         => $request->get('tags_' . $key),
            'private'     => $request->get('privacy_' . $key) === 'private' ? true : false,
        ];

        return $datas;
    }

    /**
     * Returns datas needed for an uploaded record
     * @param  Request $request
     * @return array
     */
    public function get_update_datas(Request $request)
    {
        $datas = [
            'title'       => $request->get('modif_title'),
            'description' => $request->get('modif_description'),
            'tags'        => $request->get('modif_tags'),
            'private'     => $request->get('modif_privacy') === 'private' ? true : false,
        ];

        return $datas;
    }

    /**
     * @todo implements in bridge_api_interface
     * &todo write test
     * Tell if the current connector can upload multiple file
     * @return boolean
     */
    public function is_multiple_upload()
    {
        return false;
    }

    /**
     *
     * @param  record_adapter $record
     * @return array
     */
    private function check_record_constraints(record_adapter $record)
    {
        $errors = [];
        //Record must rely on real file
        if (!$record->get_hd_file() instanceof \SplFileInfo) {
            $errors["file_size"] = $this->translator->trans("Le record n'a pas de fichier physique");
        }

        if ($record->get_duration() > self::AUTH_VIDEO_DURATION) {
            $errors["duration"] = $this->translator->trans("La taille maximale d'une video est de %duration% minutes.", ['%duration%' => self::AUTH_VIDEO_DURATION / 60]);
        }

        $size = $record->get_technical_infos('size');
        $size = $size ? $size->getValue() : PHP_INT_MAX;
        if ($size > self::AUTH_VIDEO_SIZE) {
            $errors["size"] = $this->translator->trans("Le poids maximum d'un fichier est de %size%", ['%size%' => p4string::format_octets(self::AUTH_VIDEO_SIZE)]);
        }

        return $errors;
    }

    /**
     *
     * @return array
     */
    public function get_fields()
    {
        return [
            [
                'name'       => 'title',
                'length'     => '255',
                'length_min' => '5',
                'required'   => true
            ]
            , [
                'name'       => 'description',
                'length'     => '2000',
                'length_min' => '0',
                'required'   => false
            ]
            , [
                'name'       => 'tags',
                'length'     => '150',
                'length_min' => '0',
                'required'   => false
            ]
            , [
                'name'       => 'private',
                'length'     => '0',
                'length_min' => '0',
                'required'   => true
            ]
        ];
    }
}
