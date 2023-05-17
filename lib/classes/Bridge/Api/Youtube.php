<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

class Bridge_Api_Youtube extends Bridge_Api_Abstract implements Bridge_Api_Interface
{
    /**
     *
     * @var Zend_Gdata_YouTube
     */
    protected $_api;

    const OAUTH2_AUTHORIZE_ENDPOINT = 'https://accounts.google.com/o/oauth2/auth';
    const OAUTH2_TOKEN_ENDPOINT = 'https://accounts.google.com/o/oauth2/token';
    const UPLOAD_URL = 'http://uploads.gdata.youtube.com/feeds/api/users/default/uploads';
    const CATEGORY_URL = 'http://gdata.youtube.com/schemas/2007/categories.cat';
    const AUTH_VIDEO_DURATION = 900;
    const AUTH_VIDEO_SIZE = 68719476736; //in bytes = 64GB
    const ELEMENT_TYPE_VIDEO = 'video';
    const CONTAINER_TYPE_PLAYLIST = 'playlist';
    const AUTH_TYPE = 'Youtube';
    const UPLOAD_STATE_PROCESSING = 'processing';
    const UPLOAD_STATE_RESTRICTED = 'restricted';
    const UPLOAD_STATE_DONE = 'done';
    const UPLOAD_STATE_DELETED = 'deleted';
    const UPLOAD_STATE_REJECTED = 'rejected';
    const UPLOAD_STATE_FAILED = 'failed';

    /**
     *
     * @return Array
     */
    public function connect()
    {
        $response = parent::connect();
        $this->_api->getHttpClient()->setAuthSubToken($response['auth_token']);

        return $response;
    }

    /**
     *
     * @return Bridge_Api_Youtube
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
        return $this->_api->getUserProfile('default')->getUsername();
    }

    /**
     *
     * @return string
     */
    public function get_user_name()
    {
        return $this->_api->getUserProfile('default')->getUsername();
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return 'Youtube';
    }

    /**
     *
     * @return string
     */
    public function get_icon_url()
    {
        return '/assets/common/images/icons/youtube-small.gif';
    }

    /**
     *
     * @return string
     */
    public function get_image_url()
    {
        return '/assets/common/images/icons/youtube-white.gif';
    }

    /**
     *
     * @return string
     */
    public function get_terms_url()
    {
        return 'https://code.google.com/apis/youtube/terms.html';
    }

    /**
     *
     * @return string
     */
    public function get_url()
    {
        return 'https://www.youtube.com/';
    }

    /**
     *
     * @return string
     */
    public function get_infos()
    {
        return 'www.youtube.com';
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
     *
     * @param  string                       $object
     * @param  int                          $offset_start
     * @param  int                          $quantity
     * @return Bridge_Api_ElementCollection
     */
    public function list_elements($object, $offset_start = 0, $quantity = 10)
    {
        switch ($object) {
            case self::ELEMENT_TYPE_VIDEO:
                $video_feed = $this->get_user_object_list_feed($object, $offset_start, $quantity);

                $element_collection = new Bridge_Api_ElementCollection();
                $element_collection->set_items_per_page($video_feed->getItemsPerPage()->getText());

                $total = $video_feed->getTotalResults()->getText();
                $current_page = floor((int) $video_feed->getStartIndex()->getText() / (int) $video_feed->getItemsPerPage()->getText()) + 1;
                $total_page = ceil((int) $total / (int) $video_feed->getItemsPerPage()->getText());

                $element_collection->set_total_items($total);
                $element_collection->set_current_page($current_page);
                $element_collection->set_total_page($total_page);

                foreach ($video_feed as $entry) {
                    $element_collection->add_element(new Bridge_Api_Youtube_Element($entry, $object));
                }

                return $element_collection;
                break;

            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $object);
                break;
        }
    }

    /**
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
                $playlist_feed = $this->get_user_object_list_feed($object, $offset_start, $quantity);
                $container_collection = new Bridge_Api_ContainerCollection();

                $container_collection->set_items_per_page($playlist_feed->getItemsPerPage()->getText());

                $total = $playlist_feed->getTotalResults()->getText();
                $current_page = floor((int) $playlist_feed->getStartIndex()->getText() / (int) $playlist_feed->getItemsPerPage()->getText());
                $total_page = ceil((int) $total / (int) $playlist_feed->getItemsPerPage()->getText());

                $container_collection->set_total_items($total);
                $container_collection->set_current_page($current_page);
                $container_collection->set_total_page($total_page);

                foreach ($playlist_feed as $entry) {
                    $playlist_video_feed = $this->_api->getPlaylistVideoFeed($entry->getPlaylistVideoFeedUrl());
                    $thumbnail = null;
                    if ( ! is_null($playlist_video_feed)) {
                        foreach ($playlist_video_feed as $entry2) {
                            $playlist_thumbnails = $entry2->getVideoThumbnails();
                            foreach ($playlist_thumbnails as $playlist_thumbnail) {
                                if (120 == $playlist_thumbnail['width'] && 90 == $playlist_thumbnail['height']) {
                                    $thumbnail = $playlist_thumbnail['url'];
                                    break;
                                }
                            }
                            break;
                        }
                    }

                    $container_collection->add_element(new Bridge_Api_Youtube_Container($entry, $object, $thumbnail));
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
     * @param  string             $object
     * @param  string             $object_id
     * @param  array              $datas
     * @return Bridge_Api_Youtube
     */
    public function update_element($object, $object_id, Array $datas)
    {
        $required_fields = ["description", "category", "tags", "title", "privacy"];
        foreach ($required_fields as $field) {
            if ( ! array_key_exists($field, $datas))
                throw new Bridge_Exception_ActionMandatoryField("Le paramétre " . $field . " est manquant");
        }

        if ( ! $this->is_valid_object_id($object_id))
            throw new Bridge_Exception_ActionInvalidObjectId($object_id);

        switch ($object) {
            case "video" :
                $videoEntry = $this->_api->getFullVideoEntry($object_id);
                if ($videoEntry->getEditLink() === null)
                    throw new Bridge_Exception_ActionForbidden("You cannot edit this video object");

                $videoEntry->setVideoDescription(trim($datas['description']));
                $videoEntry->setVideoCategory(trim($datas['category']));
                $videoEntry->setVideoTags(trim($datas['tags']));
                $videoEntry->setVideoTitle(trim($datas['title']));

                if ($datas["privacy"] == "public") {
                    $videoEntry->setVideoPublic();
                } else {
                    $videoEntry->setVideoPrivate();
                }

                $this->_api->updateEntry($videoEntry, $videoEntry->getEditLink()->getHref());
                break;

            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $object);
                break;
        }

        return $this;
    }

    /**
     *
     * @param  string                       $container_type
     * @param  Request                      $request
     * @return Bridge_Api_Youtube_Container
     */
    public function create_container($container_type, Request $request)
    {
        switch ($container_type) {
            case self::CONTAINER_TYPE_PLAYLIST:
                $container_desc = $request->get('f_container_description');
                $container_title = $request->get('f_container_title');

                $new_playlist = $this->_api->newPlaylistListEntry();
                if (trim($container_desc) !== '')
                    $new_playlist->description = $this->_api->newDescription()->setText($container_desc);
                $new_playlist->title = $this->_api->newTitle()->setText($container_title);

                $post_location = 'http://gdata.youtube.com/feeds/api/users/default/playlists';
                $entry = $this->_api->insertEntry($new_playlist, $post_location);

                return new Bridge_Api_Youtube_Container($entry, $container_type, null);

                break;
            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $container_type);
                break;
        }
    }

    /**
     *
     * @param  type                         $element_type
     * @param  type                         $element_id
     * @param  type                         $destination
     * @param  type                         $container_id
     * @return Bridge_Api_Youtube_Container
     */
    public function add_element_to_container($element_type, $element_id, $destination, $container_id)
    {
        switch ($element_type) {
            case self::ELEMENT_TYPE_VIDEO:
                switch ($destination) {
                    case self::CONTAINER_TYPE_PLAYLIST:
                        $playlistEntry = $this->get_PlaylistEntry_from_Id($container_id);
                        $postUrl = $playlistEntry->getPlaylistVideoFeedUrl();

                        $videoEntryToAdd = $this->_api->getVideoEntry($element_id);
                        $newPlaylistListEntry = $this->_api->newPlaylistListEntry($videoEntryToAdd->getDOM());
                        $this->_api->insertEntry($newPlaylistListEntry, $postUrl);
                        $playlistEntry = $this->get_PlaylistEntry_from_Id($container_id);

                        return new Bridge_Api_Youtube_Container($playlistEntry, $destination, null);
                        break;
                    default:
                        throw new Bridge_Exception_ContainerUnknown('Unknown element ' . $destination);
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
        switch ($object) {
            case self::ELEMENT_TYPE_VIDEO:
                $this->_api->delete($this->_api->getFullVideoEntry($object_id));
                break;
            case self::CONTAINER_TYPE_PLAYLIST:
                $this->get_PlaylistEntry_from_Id($object_id)->delete();
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
     * @return string
     */
    public function get_element_status(Bridge_Element $element)
    {
        $this->_api->setMajorProtocolVersion(1);
        $state = $this->_api->getFullVideoEntry($element->get_dist_id())->getVideoState();

        if (is_null($state))
            $result = Bridge_Element::STATUS_DONE;
        else
            $result = $state->getName();

        $this->_api->setMajorProtocolVersion(2);

        return $result;
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
            case self::UPLOAD_STATE_RESTRICTED:
                return Bridge_Element::STATUS_ERROR;
                break;
            case self::UPLOAD_STATE_DONE:
                return Bridge_Element::STATUS_DONE;
                break;
            case self::UPLOAD_STATE_DELETED:
                return Bridge_Element::STATUS_ERROR;
                break;
            case self::UPLOAD_STATE_REJECTED:
                return Bridge_Element::STATUS_ERROR;
                break;
            case self::UPLOAD_STATE_FAILED:
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
            case self::UPLOAD_STATE_RESTRICTED:
                return $this->translator->trans('La video est restreinte');
            case self::UPLOAD_STATE_DELETED:
                return $this->translator->trans('La video a ete supprimee');
            case self::UPLOAD_STATE_REJECTED:
                return $this->translator->trans('La video a ete rejetee');
            case self::UPLOAD_STATE_FAILED:
                return $this->translator->trans('L\'upload a echoue');
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
        if ($e instanceof Zend_Gdata_App_HttpException) {
            $response = $e->getResponse();

            $http_code = $response->getStatus();
            if ($http_code == 401) {
                $e = new Bridge_Exception_ActionAuthNeedReconnect();

                return;
            }

            $message = $code = "";
            switch ($response->getStatus()) {
                case 400:
                    $message = $this->translator->trans("Erreur la requête a été mal formée ou contenait des données valides.");
                    break;
                case 401:
                    $message = $this->translator->trans("Erreur lors de l'authentification au service Youtube, Veuillez vous déconnecter, puis vous reconnecter.");
                    break;
                case 403:
                    $message = $this->translator->trans("Erreur lors de l'envoi de la requête. Erreur d'authentification.");
                    break;
                case 404:
                    $message = $this->translator->trans("Erreur la ressource que vous tentez de modifier n'existe pas.");
                    break;
                case 500:
                    $message = $this->translator->trans("Erreur YouTube a rencontré une erreur lors du traitement de la requête.");
                    break;
                case 501:
                    $message = $this->translator->trans("Erreur vous avez essayé d'exécuter une requête non prise en charge par Youtube");
                    break;
                case 503:
                    $message = $this->translator->trans("Erreur le service Youtube n'est pas accessible pour le moment. Veuillez réessayer plus tard.");
                    break;
            }

            if ($error = $this->parse_xml_error($response->getBody())) {
                $code = $error['code'];

                if ($code == "too_many_recent_calls") {
                    $this->block_api(10 * 60 * 60);
                    $e = new Bridge_Exception_ApiDisabled($this->get_api_manager());

                    return;
                }

                $reason = '';
                switch ($code) {
                    case "required":
                        $reason = $this->translator->trans("A required field is missing or has an empty value");
                        break;
                    case "deprecated":
                        $reason = $this->translator->trans("A value has been deprecated and is no longer valid");
                        break;
                    case "invalid_format":
                        $reason = $this->translator->trans("A value does not match an expected format");
                        break;
                    case "invalid_character":
                        $reason = $this->translator->trans("A field value contains an invalid character");
                        break;
                    case "too_long":
                        $reason = $this->translator->trans("A value exceeds the maximum allowable length");
                        break;
                    case "too_many_recent_calls":
                        $reason = $this->translator->trans("The Youtube servers have received too many calls from the same caller in a short amount of time.");
                        break;
                    case "too_many_entries":
                        $reason = $this->translator->trans("You are attempting to exceed the storage limit on your account and must delete existing entries before inserting new entries");
                        break;
                    case "InvalidToken";
                        $reason = $this->translator->trans("The authentication token specified in the Authorization header is invalid");
                        break;
                    case "TokenExpired";
                        $reason = $this->translator->trans("The authentication token specified in the Authorization header has expired.");
                        break;
                    case "disabled_in_maintenance_mode":
                        $reason = $this->translator->trans("Current operations cannot be executed because the site is temporarily in maintenance mode. Wait a few minutes and try your request again");
                        break;
                }

                $message .= '<br/>' . $reason . '<br/>Youtube said : ' . $error['message'];
            }

            if ($error == false && $response->getStatus() == 404) {
                $message = $this->translator->trans("Service youtube introuvable.");
            }
            $e = new Exception($message);
        }

        return;
    }

    /**
     *
     * @param  string $string
     * @return Array
     */
    protected function parse_xml_error($string)
    {
        $rs = [];
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($string);
        libxml_clear_errors();

        if (false === $xml) {
            return false;
        }

        if (isset($xml->HEAD) || isset($xml->head)) {
            return [];
        } else {
            $domaine = explode(":", (string) $xml->error[0]->domain);
            $rs['type'] = count($domaine) > 1 ? $domaine[1] : $domaine[0];
            $rs['code'] = (string) $xml->error[0]->code;
            $rs['message'] = (string) $xml->error[0]->internalReason;
        }

        libxml_use_internal_errors(false);

        return $rs;
    }

    /**
     *
     * @param  record_adapter $record
     * @param  array          $options
     * @return string         The new distant Id
     */
    public function upload(record_adapter $record, array $options = [])
    {
        switch ($record->getType()) {
            case 'video':

                $video_entry = new Zend_Gdata_YouTube_VideoEntry();

                $filesource = new Zend_Gdata_App_MediaFileSource($record->get_hd_file()->getRealPath());
                $filesource->setContentType($record->get_hd_file()->get_mime());
                $filesource->setSlug($record->get_title(['encode'=> record_adapter::ENCODE_FOR_URI]));

                $video_entry->setMediaSource($filesource);
                $video_entry->setVideoTitle($options['title']);
                $video_entry->setVideoDescription($options['description']);
                $video_entry->setVideoCategory($options['category']);
                $video_entry->SetVideoTags(explode(' ', $options['tags']));
                $video_entry->setVideoDeveloperTags(['phraseanet']);

                if ($options['privacy'] == "public")
                    $video_entry->setVideoPublic();
                else
                    $video_entry->setVideoPrivate();

                $app_entry = $this->_api->insertEntry($video_entry, self::UPLOAD_URL, 'Zend_Gdata_YouTube_VideoEntry');

                /*
                 * set major protocole version to 2 otherwise you get exception when calling getVideoId
                 * but setting setMajorProtocolVersion to 2 at the new entry introduce a new bug with getVideoState
                 * @see http://groups.google.com/group/youtube-api-gdata/browse_thread/thread/7d86cac0d3f90e3f/d9291d7314f99be7?pli=1
                 */
                $app_entry->setMajorProtocolVersion(2);

                return $app_entry->getVideoId();
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
     * @return Bridge_Api_Youtube_Element
     */
    public function get_element_from_id($element_id, $object)
    {
        switch ($object) {
            case self::ELEMENT_TYPE_VIDEO:
                return new Bridge_Api_Youtube_Element($this->_api->getVideoEntry($element_id), $object);
                break;
            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $object);
                break;
        }
    }

    /**
     * get available youtube categories as an array
     *
     * @return array
     */
    public function get_category_list()
    {
        $cat = [];
        $url_cat = sprintf('%s?hl=%s', self::CATEGORY_URL, $this->get_locale());

        if (false === $cxml = simplexml_load_file($url_cat)) {
            throw new Bridge_Exception_ApiConnectorRequestFailed('Failed to retrive youtube categories');
        }

        $cxml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
        $categories = $cxml->xpath('//atom:category');

        foreach ($categories as $c) {
            $cat[(string) $c['term']] = (string) $c['label'];
        }

        return $cat;
    }

    /**
     *
     * @param  string                       $object
     * @param  string                       $element_id
     * @return Bridge_Api_Youtube_Container
     */
    public function get_container_from_id($object, $element_id)
    {
        switch ($object) {
            case self::CONTAINER_TYPE_PLAYLIST:
                return new Bridge_Api_Youtube_Container($this->get_PlaylistEntry_from_Id($element_id), $object, null);
                break;
            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $object);
                break;
        }
    }

    /**
     *
     * @param  string $object
     * @param  int    $offset_start
     * @param  int    $quantity
     * @return string
     */
    protected function get_user_object_list_feed($object, $offset_start, $quantity)
    {
        $feed = null;
        switch ($object) {
            case self::ELEMENT_TYPE_VIDEO:
                $uri = Zend_Gdata_YouTube::USER_URI . '/default/' . Zend_Gdata_YouTube::UPLOADS_URI_SUFFIX;
                $query = new Zend_Gdata_Query($uri);
                if ($quantity !== 0)
                    $query->setMaxResults($quantity);
                $query->setStartIndex($offset_start);
                $feed = $this->_api->getUserUploads(null, $query);
                break;
            case self::CONTAINER_TYPE_PLAYLIST:
                $uri = Zend_Gdata_YouTube::USER_URI . '/default/playlists';
                $query = new Zend_Gdata_Query($uri);
                if ($quantity !== 0)
                    $query->setMaxResults($quantity);
                $query->setStartIndex($offset_start);
                $feed = $this->_api->getPlaylistListFeed(null, $query);
                break;
            default:
                throw new Bridge_Exception_ObjectUnknown('Unknown object ' . $object);
                break;
        }

        return $feed;
    }

    public function is_configured()
    {
        if (!$this->conf->get(['main', 'bridge', 'youtube', 'enabled'])) {
            return false;
        }
        if ('' === trim($this->conf->get(['main', 'bridge', 'youtube', 'client_id']))) {
            return false;
        }
        if ('' === trim($this->conf->get(['main', 'bridge', 'youtube', 'client_secret']))) {
            return false;
        }
        if ('' === trim($this->conf->get(['main', 'bridge', 'youtube', 'developer_key']))) {
            return false;
        }

        return true;
    }

    /**
     *
     * @return Bridge_Api_Youtube
     */
    protected function set_auth_params()
    {
        $this->_auth->set_parameters(
            [
                'client_id'      => $this->conf->get(['main', 'bridge', 'youtube', 'client_id'])
                , 'client_secret'  => $this->conf->get(['main', 'bridge', 'youtube', 'client_secret'])
                , 'redirect_uri'   => Bridge_Api::generate_callback_url($this->generator, $this->get_name())
                , 'scope'          => 'http://gdata.youtube.com'
                , 'response_type'  => 'code'
                , 'token_endpoint' => self::OAUTH2_TOKEN_ENDPOINT
                , 'auth_endpoint'  => self::OAUTH2_AUTHORIZE_ENDPOINT
            ]
        );

        return $this;
    }

    /**
     *
     * @return Bridge_Api_Youtube
     */
    protected function initialize_transport()
    {
        $http_client = new Zend_Gdata_HttpClient();
        $http_client->setHeaders('Accept', 'application/atom+xml');

        $this->_api = new Zend_Gdata_YouTube(
                $http_client,
                Uuid::uuid4(),
                $this->conf->get(['main', 'bridge', 'youtube', 'client_id']),
                $this->conf->get(['main', 'bridge', 'youtube', 'developer_key']));
        $this->_api->setMajorProtocolVersion(2);

        return $this;
    }

    /**
     *
     * @return Bridge_Api_Youtube
     */
    protected function set_transport_authentication_params()
    {
        if ($this->_auth->is_connected()) {
            $signatures = $this->_auth->get_auth_signatures();
            $this->_api->getHttpClient()->setAuthSubToken($signatures['auth_token']);
        }

        return $this;
    }

    /**
     *
     * @param  string                              $element_id
     * @return Zend_Gdata_YouTube_PlaylistListFeed
     */
    protected function get_PlaylistEntry_from_Id($element_id)
    {
        foreach ($this->_api->getPlaylistListFeed('default') as $playlist_entry) {
            if ($element_id == $playlist_entry->getPlaylistId()->getText()) {
                return $playlist_entry;
            }
        }

        return null;
    }

    /**
     *
     * @return string
     */
    public function get_locale()
    {
        $youtube_available_locale = [
            'zh-CN', 'zh-TW', 'cs-CZ', 'nl-NL', 'en-GB', 'en-US', 'fr-FR', 'de-DE',
            'it-IT', 'ja-JP', 'ko-KR', 'pl-PL', 'pt-PT', 'ru-RU', 'es-ES', 'es-MX',
            'sv-SE'
        ];
        if ( ! is_null($this->locale)) {
            $youtube_format_locale = str_replace('_', '-', $this->locale);
            if (in_array(trim($youtube_format_locale), $youtube_available_locale)) {
                return $this->locale;
            }
        }

        return "en-US";
    }

    /**
     * Check if data uploaded via the current connector is conform
     *
     * @param array          $datas
     * @param record_adapter $record
     *
     * @return array
     */
    public function check_upload_constraints(Array $datas, record_adapter $record)
    {
        $errors = $this->check_record_constraints($record);

        $check = function ($field) use (&$errors, $datas, $record) {
                $key = $record->getId();
                $name = $field['name'];
                $length = (int) $field['length'];
                $required = ! ! $field['required'];
                $empty = ! ! $field['empty'];

                if ( ! isset($datas[$name])) {
                    if ($required)
                        $errors[$name . '_' . $key] = $this->translator->trans("Ce champ est obligatoire");
                } elseif (trim($datas[$name]) === '') {
                    if ( ! $empty)
                        $errors[$name . '_' . $key] = $this->translator->trans("Ce champ est obligatoire");
                } elseif ($length !== 0) {
                    if (mb_strlen($datas[$name]) > $length)
                        $errors[$name . '_' . $key] = $this->translator->trans("Ce champ est trop long %length% caracteres max", ['%length%' => $length]);
                }
            };

        array_map($check, $this->get_fields());

        return $errors;
    }

    public function check_update_constraints(Array $datas)
    {
        $errors = [];
        $check = function ($field) use (&$errors, $datas) {
                $name = $field['name'];
                $length = (int) $field['length'];
                $required = ! ! $field['required'];
                $empty = ! ! $field['empty'];

                if ( ! isset($datas[$name])) {
                    if ($required)
                        $errors[$name] = $this->translator->trans("Ce champ est obligatoire");
                } elseif (trim($datas[$name]) === '') {
                    if ( ! $empty)
                        $errors[$name] = $this->translator->trans("Ce champ est obligatoire");
                } elseif ($length !== 0) {
                    if (mb_strlen($datas[$name]) > $length)
                        $errors[$name] = $this->translator->trans("Ce champ est trop long %length% caracteres max", ['%length%' => $length]);
                }
            };

        array_map($check, $this->get_fields());

        return $errors;
    }

    /**
     * Returns datas needed for an uploaded record
     *
     * @param Request $request
     *
     * @return array
     */
    public function get_update_datas(Request $request)
    {
        $datas = [
            'title'       => $request->get('modif_title'),
            'description' => $request->get('modif_description'),
            'category'    => $request->get('modif_category'),
            'tags'        => $request->get('modif_tags'),
            'privacy'     => $request->get('modif_privacy'),
        ];

        return $datas;
    }

    /**
     * Returns datas needed for an uploaded record
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
            'category'    => $request->get('category_' . $key),
            'tags'        => $request->get('tags_' . $key),
            'privacy'     => $request->get('privacy_' . $key),
        ];

        return $datas;
    }

    /**
     * @todo implements in bridge_api_interface
     * @todo write test
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
        $key = $record->getId();
        //Record must rely on real file
        if ( ! $record->get_hd_file() instanceof SplFileInfo) {
            $errors["file_size_" . $key] = $this->translator->trans("Le record n'a pas de fichier physique");
        }

        if ($record->get_duration() > self::AUTH_VIDEO_DURATION) {
            $errors["duration_" . $key] = $this->translator->trans("La taille maximale d'une video est de %duration% minutes.", ['%duration%' => self::AUTH_VIDEO_DURATION / 60]);
        }

        $size = $record->get_technical_infos('size');
        $size = $size ? $size->getValue() : PHP_INT_MAX;
        if ($size > self::AUTH_VIDEO_SIZE) {
            $errors["size_" . $key] = $this->translator->trans("Le poids maximum d'un fichier est de %size%", ['%size%' => p4string::format_octets(self::AUTH_VIDEO_SIZE)]);
        }

        return $errors;
    }

    /**
     *
     * @return array
     */
    private function get_fields()
    {
        return [
            [
                'name'     => 'title',
                'length'   => '100',
                'required' => true,
                'empty'    => false
            ]
            , [
                'name'     => 'description',
                'length'   => '2000',
                'required' => true,
                'empty'    => true
            ]
            , [
                'name'       => 'tags',
                'length'     => '500',
                'tag_length' => '30',
                'required'   => true,
                'empty'      => true
            ]
            , [
                'name'     => 'privacy',
                'length'   => '0',
                'required' => true,
                'empty'    => false
            ]
            , [
                'name'     => 'category',
                'length'   => '0',
                'required' => true,
                'empty'    => false
            ]
        ];
    }
}
