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

class Bridge_Api_Flickr extends Bridge_Api_Abstract implements Bridge_Api_Interface
{
    /**
     *
     * @var Phlickr_Api
     */
    protected $_api;

    const ELEMENT_TYPE_PHOTO = 'photo';
    const CONTAINER_TYPE_PHOTOSET = 'photoset';
    const AUTH_TYPE = 'Flickr';
    const AUTH_PHOTO_SIZE = 15728640; //15 mo
    const UPLOAD_STATE_DONE = 'done';
    const UPLOAD_STATE_FAILED = 'failed';
    const UPLOAD_STATE_FAILED_CONVERTING = 'failed_converting';
    const UPLOAD_STATE_NOT_COMPLETED = 'not_completed';

    /**
     *
     * @return Array
     */
    public function connect()
    {
        $response = parent::connect();
        $this->_api->setAuthToken($response['auth_token']);

        return $response;
    }

    /**
     *
     * @return Bridge_Api_Flickr
     */
    public function disconnect()
    {
        parent::disconnect();
        $this->_api->setAuthToken(null);

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_user_name()
    {
        $response = $this->_api->executeMethod('flickr.auth.checkToken');
        if ( ! $response->isOk()) {
            throw new Bridge_Exception_ApiConnectorRequestFailed('Unable to retrieve FlickR username');
        }

        return (string) $response->xml->auth->user['username'];
    }

    /**
     *
     * @return string
     */
    public function get_user_id()
    {
        return $this->_api->getUserId();
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return 'Flickr';
    }

    /**
     *
     * @return string
     */
    public function get_icon_url()
    {
        return '/assets/common/images/icons/flickr-small.gif';
    }

    /**
     *
     * @return string
     */
    public function get_image_url()
    {
        return '/assets/common/images/icons/flickr.gif';
    }

    /**
     *
     * @return string
     */
    public function get_terms_url()
    {
        return 'https://secure.flickr.com/services/api/tos/';
    }

    /**
     *
     * @return string
     */
    public function get_url()
    {
        return 'https://secure.flickr.com/';
    }

    /**
     *
     * @return string
     */
    public function get_infos()
    {
        return $this->translator->trans('Ce produit utilise l\'API Flickr mais n\'est ni soutenu, ni certifie par Flickr');
    }

    /**
     *
     * @return string
     */
    public function get_default_element_type()
    {
        return self::ELEMENT_TYPE_PHOTO;
    }

    /**
     *
     * @return string
     */
    public function get_default_container_type()
    {
        return self::CONTAINER_TYPE_PHOTOSET;
    }

    /**
     *
     * @param  type $element_id
     * @param  type $object
     * @return type
     */
    public function get_element_from_id($element_id, $object)
    {
        switch ($object) {
            case self::ELEMENT_TYPE_PHOTO:
                $params = ['photo_id'   => $element_id];
                $th_response = $this->_api->executeMethod('flickr.photos.getInfo', $params);

                if ( ! $th_response->isOk())
                    throw new Bridge_Exception_ApiConnectorRequestFailed('Unable to retrieve element infos for ' . $object . ' ' . $element_id);

                $th_xml = $th_response->getXml();

                return new Bridge_Api_Flickr_Element($th_xml, $this->get_user_id(), $object, false);
                break;
            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $object);
                break;
        }

        return null;
    }

    /**
     *
     * @param  type $object
     * @param  type $element_id
     * @return type
     */
    public function get_container_from_id($object, $element_id)
    {
        switch ($object) {
            case self::CONTAINER_TYPE_PHOTOSET:

                $params = ['photoset_id' => $element_id];
                $response = $this->_api->executeMethod('flickr.photoset.getInfo', $params);

                if ( ! $response->isOk())
                    throw new Bridge_Exception_ApiConnectorRequestFailed('Unable to retrieve photoset infos for ' . $object);

                $xml = $response->getXml();
                $primary_photo = $this->get_element_from_id((string) $xml->photo['id'], self::ELEMENT_TYPE_PHOTO);

                return new Bridge_Api_Flickr_Container($xml, $this->get_user_id(), $object, $primary_photo->get_thumbnail());
                break;
            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $object);
                break;
        }

        return null;
    }

    /**
     *
     * @param type $object
     * @param type $offset_start
     * @param type $quantity
     */
    public function list_containers($object, $offset_start = 0, $quantity = 10)
    {
        switch ($object) {
            case self::CONTAINER_TYPE_PHOTOSET:
                $params = [];
                if ($quantity)
                    $params['per_page'] = $quantity;
                $params['page'] = $quantity != 0 ? floor($offset_start / $quantity) + 1 : 1;
                $params['user_id'] = $user_id = $this->get_user_id();
                $response = $this->_api->executeMethod('flickr.photosets.getList', $params);

                if ( ! $response->isOk())
                    throw new Bridge_Exception_ApiConnectorRequestFailed('Unable to retrieve container list ' . $object);

                $photosets = new Bridge_Api_ContainerCollection();
                $xml = $response->getXml();

                $photosets->set_current_page((int) $xml->photosets['page'])
                    ->set_items_per_page((int) $xml->photosets['perpage'])
                    ->set_total_items((int) $xml->photosets['total'])
                    ->set_total_page((int) $xml->photosets['pages']);

                foreach ($xml->photosets->children() as $child) {
                    $primary_photo = $this->get_element_from_id((string) $child['primary'], self::ELEMENT_TYPE_PHOTO);
                    $photosets->add_element(new Bridge_Api_Flickr_Container($child, $user_id, $object, $primary_photo->get_thumbnail()));
                }
                $photosets->set_total_items(count($photosets->get_elements()));

                return $photosets;
                break;
            default:
                throw new Bridge_Exception_ObjectUnknown('Unknown object ' . $object);
                break;
        }
    }

    /**
     *
     * @param string $object
     * @param string $object_id
     * @param array  $datas
     */
    public function update_element($object, $object_id, Array $datas)
    {
        $required_fields = ["title"];
        foreach ($required_fields as $field) {
            if ( ! array_key_exists($field, $datas) || trim($datas[$field]) === '')
                throw new Bridge_Exception_ActionMandatoryField("Le paramétre " . $field . " est manquant");
        }

        $params = [
            'title'       => $datas["title"]
            , 'photo_id'    => $object_id
            , 'description' => $datas["description"]
        ];

        switch ($object) {
            case self::ELEMENT_TYPE_PHOTO :
                $response = $this->_api->executeMethod('flickr.photos.setMeta', $params);

                if ( ! $response->isOk())
                    throw new Bridge_Exception_ApiConnectorRequestFailed();

                break;
            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $object);
                break;
        }

        return $this;
    }

    /**
     *
     * @param  string                      $container_type
     * @param  Request                     $request
     * @return Bridge_Api_Flickr_Container
     */
    public function create_container($container_type, Request $request)
    {
        switch ($container_type) {
            case self::CONTAINER_TYPE_PHOTOSET:
                $pid = $request->get('f_container_primary_photo');
                if (is_null($pid))
                    throw new Bridge_Exception_ActionMandatoryField('You must define a default photo for the photoset');

                $params = [
                    'title'            => $request->get('title')
                    , 'primary_photo_id' => $pid
                    , 'description'      => $request->get('description')
                ];

                $response = $this->_api->executeMethod('flickr.photosets.create', $params);

                if ( ! $response->isOk())
                    throw new Bridge_Exception_ApiConnectorRequestFailed();

                $user_id = $this->get_user_id();
                $xml = $response->getXml();

                $photoset = $xml->photoset;
                $primary_photo = $this->get_element_from_id($pid, self::ELEMENT_TYPE_PHOTO);

                return new Bridge_Api_Flickr_Container($photoset, $user_id, $container_type, $primary_photo->get_thumbnail());
                break;
            default:
                throw new Bridge_Exception_ObjectUnknown('Unknown object ' . $container_type);
                break;
        }
    }

    /**
     *
     * @param  string $element_type
     * @param  string $element_id
     * @param  string $destination
     * @param  string $container_id
     * @return Void
     */
    public function add_element_to_container($element_type, $element_id, $destination, $container_id)
    {
        switch ($element_type) {
            case self::ELEMENT_TYPE_PHOTO:
                switch ($destination) {
                    case self::CONTAINER_TYPE_PHOTOSET:
                        $params = ['photo_id'    => $element_id, 'photoset_id' => $container_id];
                        $response = $this->_api->executeMethod('flickr.photosets.addPhoto', $params);

                        if ( ! $response->isOk()) {
                            //Already exists in photoset
                            if ($response->err_code === 3) {
                                return;
                            }

                            throw new Bridge_Exception_ApiConnectorRequestFailed();
                        }
                        break;
                    default:
                        throw new Bridge_Exception_ObjectUnknown('Unknown object ' . $destination);
                        break;
                }
                break;
            default:
                throw new Bridge_Exception_ObjectUnknown('Unknown object ' . $element_type);
                break;
        }

        return;
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
            case self::ELEMENT_TYPE_PHOTO:
                $response = $this->_api->executeMethod(
                    'flickr.photos.delete'
                    , ['photo_id' => $object_id]
                );
                if ( ! $response->isOk())
                    throw new Bridge_Exception_ApiConnectorRequestFailed();
                break;
            case self::CONTAINER_TYPE_PHOTOSET:
                $response = $this->_api->executeMethod(
                    'flickr.photosets.delete'
                    , ['photoset_id' => $object_id]
                );
                if ( ! $response->isOk())
                    throw new Bridge_Exception_ApiConnectorRequestFailed();
                break;
            default:
                throw new Bridge_Exception_ObjectUnknown('Unknown object ' . $object);
                break;
        }

        return;
    }

    /**
     *
     * @param  string                       $type
     * @param  int                          $offset_start
     * @param  int                          $quantity
     * @return Bridge_Api_ElementCollection
     */
    public function list_elements($type, $offset_start = 0, $quantity = 10)
    {
        switch ($type) {
            case self::ELEMENT_TYPE_PHOTO:
                $params = [];
                //info to display during search
                $extras = [
                    'description'
                    , 'license'
                    , 'date_upload'
                    , 'date_taken'
                    , 'owner_name'
                    , 'last_update'
                    , 'tags'
                    , 'views'
                    , 'url_sq'
                    , 'url_t'
                ];

                $params['user_id'] = $this->get_user_id();
                $params['extras'] = implode(",", $extras);

                if ($quantity)
                    $params['per_page'] = $quantity;
                $params['page'] = $quantity != 0 ? floor($offset_start / $quantity) + 1 : 1;
                $response = $this->_api->executeMethod('flickr.photos.search', $params);

                $photos = new Bridge_Api_ElementCollection();

                if ( ! $response->isOk())
                    throw new Bridge_Exception_ApiConnectorRequestFailed('Unable to retrieve element list ' . $type);
                $xml = $response->getXml();
                $photos->set_current_page((int) $xml->photos['page'])
                    ->set_items_per_page((int) $xml->photos['perpage'])
                    ->set_total_items((int) $xml->photos['total'])
                    ->set_total_page((int) $xml->photos['pages']);
                foreach ($xml->photos->children() as $child) {
                    $photos->add_element(new Bridge_Api_Flickr_Element($child, $params['user_id'], $type));
                }

                return $photos;
                break;
            default:
                throw new Bridge_Exception_ElementUnknown('Unknown element ' . $type);
                break;
        }
    }

    public function get_element_status(Bridge_Element $element)
    {
        try {
            $ticket = $this->checkTicket($element->get_dist_id(), $element->get_type());
            if ($ticket["status"] == self::UPLOAD_STATE_DONE) {
                $this->get_element_from_id($ticket["dist_id"], $element->get_type());
                $element->set_dist_id($ticket["dist_id"]);
            }
        } catch (\Exception $e) {
            return self::UPLOAD_STATE_FAILED;
        }

        return $ticket["status"];
    }

    public function map_connector_to_element_status($status)
    {
        switch ($status) {
            case self::UPLOAD_STATE_DONE:
                return Bridge_Element::STATUS_DONE;
                break;
            case self::UPLOAD_STATE_FAILED:
                return Bridge_Element::STATUS_ERROR;
                break;
            default:
                return null;
                break;
        }
    }

    public function get_error_message_from_status($connector_status)
    {
        switch ($connector_status) {
            case self::UPLOAD_STATE_FAILED:
                return $this->translator->trans('L\'upload a echoue');
            default:
            case self::UPLOAD_STATE_DONE:
                return '';
        }
    }

    /**
     *
     * @param  record_adapter $record
     * @param  array          $options
     * @return string         The new distant Id
     */
    public function upload(record_adapter $record, Array $options = [])
    {
        $uploader = new Phlickr_Uploader($this->_api);

        $privacy = $this->get_default_privacy();
        $uploader->setPerms($privacy['public'], $privacy['friends'], $privacy['family']);
        $type = $record->getType() == 'image' ? self::ELEMENT_TYPE_PHOTO : $record->getType();
        switch ($type) {
            case self::ELEMENT_TYPE_PHOTO :
                return $uploader->upload($record->get_hd_file()->getRealPath(), $options['title'], $options['description'], $options['tags'], true);
                break;
            default:
                throw new Bridge_Exception_InvalidRecordType('Unknown format');
                break;
        }
    }

    /**
     *
     * @return Closure
     */
    public function acceptable_records()
    {
        return function (record_adapter $record) {
                return in_array($record->getType(), ['image']);
            };
    }

    protected function get_default_privacy()
    {
        $privacy = null;
        $response = $this->_api->executeMethod('flickr.prefs.getPrivacy');
        if ( ! $response->isOk())
            throw new Bridge_Exception_ApiConnectorRequestFailed('Unable to retrieve default privacy settings');
        $xml = $response->getXml();
        $privacy = (string) $xml->person['privacy'];
        switch ($privacy) {
            case '1':
            default:
                $public = true;
                $friends = $family = false;
                break;
            case '2':
                $friends = true;
                $public = $family = false;
                break;
            case '3':
                $family = true;
                $public = $friends = false;
                break;
            case '4':
                $friends = $family = true;
                $public = false;
                break;
            case '5':
                $family = $friends = $public = false;
                break;
        }
        $ret = ['friends' => $friends, 'family'  => $family, 'public'  => $public];

        return $ret;
    }

    /**
     *
     * @param  string $type
     * @return string
     */
    public function get_object_class_from_type($type)
    {
        switch ($type) {
            case self::ELEMENT_TYPE_PHOTO:
                return self::OBJECT_CLASS_ELEMENT;
                break;
            case self::CONTAINER_TYPE_PHOTOSET:
                return self::OBJECT_CLASS_CONTAINER;
                break;
            default:
                throw new Exception('Unknown type');
                break;
        }

        return;
    }

    /**
     *
     * @return Array
     */
    public function get_element_types()
    {
        return [self::ELEMENT_TYPE_PHOTO => $this->translator->trans('Photos')];
    }

    /**
     *
     * @return Array
     */
    public function get_container_types()
    {
        return [self::CONTAINER_TYPE_PHOTOSET => $this->translator->trans('Photosets')];
    }

    /**
     * Returns all categories for elements
     * But there's not categories in FlickR.
     * Just to satisfy the interface
     *
     * @return bridge_request_result;
     */
    public function get_category_list()
    {
        return [];
    }

    public function is_configured()
    {
        if (!$this->conf->get(['main', 'bridge', 'flickr', 'enabled'])) {
            return false;
        }
        if ('' === trim($this->conf->get(['main', 'bridge', 'flickr', 'client_id']))) {
            return false;
        }
        if ('' === trim($this->conf->get(['main', 'bridge', 'flickr', 'client_secret']))) {
            return false;
        }

        return true;
    }

    /**
     *
     * @return Bridge_Api_Flickr
     */
    protected function initialize_transport()
    {
        $this->_api = new Phlickr_Api(
            $this->conf->get(['main', 'bridge', 'flickr', 'client_id']),
            $this->conf->get(['main', 'bridge', 'flickr', 'client_secret'])
        );

        return $this;
    }

    /**
     *
     * @return Bridge_Api_Flickr
     */
    protected function set_transport_authentication_params()
    {
        if ($this->_auth->is_connected()) {
            $signatures = $this->_auth->get_auth_signatures();
            $this->_api->setAuthToken($signatures['auth_token']);
        }

        return $this;
    }

    /**
     *
     * @return Bridge_Api_Flickr
     */
    protected function set_auth_params()
    {
        $this->_auth->set_parameters(
            [
                'flickr_client_id'     => $this->conf->get(['main', 'bridge', 'flickr', 'client_id'])
                , 'flickr_client_secret' => $this->conf->get(['main', 'bridge', 'flickr', 'client_secret'])
                , 'permissions'          => 'delete'
            ]
        );

        return $this;
    }

    /**
     * Not implmented
     * @param  array          $datas
     * @param  record_adapter $record
     * @return array
     */
    public function check_upload_constraints(array $datas, record_adapter $record)
    {
        $errors = $this->check_record_constraints($record);
        $check = function ($field) use (&$errors, $datas, $record) {
                $key = $record->getId();
                $name = $field['name'];
                $length = (int) $field['length'];
                $required = ! ! $field['required'];

                if ( ! isset($datas[$name]) || trim($datas[$name]) === '') {
                    if ($required)
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

                if ( ! isset($datas[$name]) || trim($datas[$name]) === '') {
                    if ($required)
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
            'title'       => $request->get('modif_title', ''),
            'description' => $request->get('modif_description', '')
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
     *
     * @return boolean
     */
    public function is_multiple_upload()
    {
        return true;
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
                'required' => true
            ]
            , [
                'name'     => 'description',
                'length'   => '500',
                'required' => false
            ]
            , [
                'name'     => 'tags',
                'length'   => '200',
                'required' => false
            ]
        ];
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
        if ( ! $record->get_hd_file() instanceof \SplFileInfo) {
            $errors["file_size"] = $this->translator->trans("Le record n'a pas de fichier physique");
        }

        $size = $record->get_technical_infos('size');
        $size = $size ? $size->getValue() : PHP_INT_MAX;
        if ($size > self::AUTH_PHOTO_SIZE) {
            $errors["size"] = $this->translator->trans("Le poids maximum d'un fichier est de %size%", ['%size%' => p4string::format_octets(self::AUTH_VIDEO_SIZE)]);
        }

        return $errors;
    }

    private function checkTicket($ticketsId, $type)
    {
        $return = ["status"  => self::UPLOAD_STATE_FAILED, "dist_id" => null];
        $response = $this->_api->executeMethod("flickr.photos.upload.checkTickets", ["tickets" => $ticketsId]);
        if ( ! $response->isOk())
            throw new Bridge_Exception_ApiConnectorRequestFailed('Unable to retrieve element list ' . $type);

        $xml = $response->getXml();
        $complete = isset($xml->uploader->ticket["complete"]) ? (string) $xml->uploader->ticket["complete"] : null;

        if ($complete) {
            if ((int) $complete == 0)
                $return["status"] = self::UPLOAD_STATE_NOT_COMPLETED;
            elseif ((int) $complete == 2)
                $return["status"] = self::UPLOAD_STATE_FAILED_CONVERTING;
            else {
                $return["dist_id"] = (string) $xml->uploader->ticket["photoid"];
                $return["status"] = self::UPLOAD_STATE_DONE;
            }
        }

        return $return;
    }
}
