<?php

namespace Alchemy\Phrasea\Controller\Api\V3;


use ACL;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Media\MediaSubDefinitionUrlGenerator;
use caption_field;
use databox_status;
use media_Permalink_Adapter;
use media_subdef;
use record_adapter;
use Symfony\Component\HttpFoundation\Request;


class V3ResultHelpers
{
    /** @var PropertyAccess */
    private $conf;

    /** @var MediaSubDefinitionUrlGenerator */
    private $urlgenerator;

    /** @var Authenticator */
    private $authenticator;


    public function __construct($conf, $urlgenerator, Authenticator $authenticator)
    {
        $this->urlgenerator = $urlgenerator;
        $this->conf = $conf;
        $this->authenticator = $authenticator;
    }

    /**
     * Retrieve detailed information about one status
     *
     * @param record_adapter $record
     * @return array
     */
    public function listRecordStatus(record_adapter $record)
    {
        $ret = [];
        foreach ($record->getStatusStructure() as $bit => $status) {
            $ret[] = [
                'bit'   => $bit,
                'state' => databox_status::bitIsSet($record->getStatusBitField(), $bit),
            ];
        }

        return $ret;
    }

    public function listEmbeddableMedia(Request $request, record_adapter $record, media_subdef $media, ACL $acl)
    {
        if (!$media->is_physically_present()) {
            return null;
        }

        if ($this->getAuthenticator()->isAuthenticated()) {
            if ($media->get_name() !== 'document'
                && false === $acl->has_access_to_subdef($record, $media->get_name())
            ) {
                return null;
            }
            if ($media->get_name() === 'document'
                && !$acl->has_right_on_base($record->getBaseId(), ACL::CANDWNLDHD)
                && !$acl->has_hd_grant($record)
            ) {
                return null;
            }
        }

        if ($media->get_permalink() instanceof media_Permalink_Adapter) {
            $permalink = $this->listPermalink($media->get_permalink());
        } else {
            $permalink = null;
        }

        $urlTTL = (int) $request->get(
            'subdef_url_ttl',
            $this->getConf()->get(['registry', 'general', 'default-subdef-url-ttl'])
        );
        if ($urlTTL < 0) {
            $urlTTL = -1;
        }
        $issuer = $this->getAuthenticator()->getUser();

        return [
            'name' => $media->get_name(),
            'permalink' => $permalink,
            'height' => $media->get_height(),
            'width' => $media->get_width(),
            'filesize' => $media->get_size(),
            'devices' => $media->getDevices(),
            'player_type' => $media->get_type(),
            'mime_type' => $media->get_mime(),
            'substituted' => $media->is_substituted(),
            'created_on'  => $media->get_creation_date()->format(DATE_ATOM),
            'updated_on'  => $media->get_modification_date()->format(DATE_ATOM),
            'url' => $this->urlgenerator->generate($issuer, $media, $urlTTL),
            'url_ttl' => $urlTTL,
        ];
    }

    /**
     * @param media_Permalink_Adapter $permalink
     * @return array
     *
     * @todo         fix duplicated code
     * @noinspection DuplicatedCode
     */
    public function listPermalink(media_Permalink_Adapter $permalink)
    {
        $downloadUrl = $permalink->get_url();
        $downloadUrl->getQuery()->set('download', '1');

        return [
            'created_on'   => $permalink->get_created_on()->format(DATE_ATOM),
            'id'           => $permalink->get_id(),
            'is_activated' => $permalink->get_is_activated(),
            'label'        => $permalink->get_label(),
            'updated_on'   => $permalink->get_last_modified()->format(DATE_ATOM),
            'page_url'     => $permalink->get_page(),
            'download_url' => (string)$downloadUrl,
            'url'          => (string)$permalink->get_url(),
        ];
    }

    /**
     * Retrieve detailed information about one record
     *
     * @param Request $request
     * @param record_adapter $record
     * @param ACL $aclforuser
     * @return array
     */
    public function listRecord(Request $request, record_adapter $record, ACL $aclforuser)
    {
        $technicalInformation = [];
        foreach ($record->get_technical_infos()->getValues() as $name => $value) {
            $technicalInformation[] = ['name' => $name, 'value' => $value];
        }

        $data = [
            'databox_id'             => $record->getDataboxId(),
            'record_id'              => $record->getRecordId(),
            'mime_type'              => $record->getMimeType(),
            'title'                  => $record->get_title(),
            'original_name'          => $record->get_original_name(),
            'updated_on'             => $record->getUpdated()->format(DATE_ATOM),
            'created_on'             => $record->getCreated()->format(DATE_ATOM),
            'collection_id'          => $record->getCollectionId(),
            'base_id'                => $record->getBaseId(),
            'sha256'                 => $record->getSha256(),
            'thumbnail'              => $this->listEmbeddableMedia($request, $record, $record->get_thumbnail(), $aclforuser),
            'technical_informations' => $technicalInformation,
            'phrasea_type'           => $record->getType(),
            'uuid'                   => $record->getUuid(),
        ];

        if ($request->attributes->get('_extended', false)) {
            $data = array_merge($data, [
                'subdefs' => $this->listRecordEmbeddableMedias($request, $record, $aclforuser),
                'metadata' => $this->listRecordMetadata($record, $aclforuser),
                'status' => $this->listRecordStatus($record),
                'caption' => $this->listRecordCaption($record, $aclforuser),
            ]);
        }

        return $data;
    }

    /**
     * @param Request $request
     * @param record_adapter $record
     * @return array
     */
    private function listRecordEmbeddableMedias(Request $request, record_adapter $record, ACL $acl)
    {
        $subdefs = [];

        foreach ($record->get_embedable_medias([], []) as $name => $media) {
            if (null !== $subdef = $this->listEmbeddableMedia($request, $record, $media, $acl)) {
                $subdefs[] = $subdef;
            }
        }

        return $subdefs;
    }

    /**
     * List all fields of given record
     *
     * @param record_adapter $record
     * @param ACL $acl
     * @return array
     */
    private function listRecordMetadata(record_adapter $record, ACL $acl)
    {
        $includeBusiness = $acl->can_see_business_fields($record->getDatabox());

        return $this->listRecordCaptionFields($record->get_caption()->get_fields(null, $includeBusiness));
    }

    /**
     * @param caption_field[] $fields
     * @return array
     */
    private function listRecordCaptionFields($fields)
    {
        $ret = [];

        foreach ($fields as $field) {
            $databox_field = $field->get_databox_field();

            $fieldData = [
                'meta_structure_id' => $field->get_meta_struct_id(),
                'name' => $field->get_name(),
                'labels' => [
                    'fr' => $databox_field->get_label('fr'),
                    'en' => $databox_field->get_label('en'),
                    'de' => $databox_field->get_label('de'),
                    'nl' => $databox_field->get_label('nl'),
                ],
            ];

            foreach ($field->get_values() as $value) {
                $data = [
                    'meta_id' => $value->getId(),
                    'value' => $value->getValue(),
                ];

                $ret[] = $fieldData + $data;
            }
        }

        return $ret;
    }

    /**
     * @param record_adapter $record
     * @param ACL $acl
     * @return array
     */
    private function listRecordCaption(record_adapter $record, ACL $acl)
    {
        $includeBusiness = $acl->can_see_business_fields($record->getDatabox());

        $caption = [];

        foreach ($record->get_caption()->get_fields(null, $includeBusiness) as $field) {
            $caption[] = [
                'meta_structure_id' => $field->get_meta_struct_id(),
                'name' => $field->get_name(),
                'value' => $field->get_serialized_values(';'),
            ];
        }

        return $caption;
    }


    ////////////////////////
    private function getAuthenticator()
    {
        return $this->authenticator;
    }

    protected function getConf()
    {
        return $this->conf;
    }

}
