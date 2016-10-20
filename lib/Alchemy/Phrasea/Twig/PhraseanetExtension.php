<?php

namespace Alchemy\Phrasea\Twig;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Collection\CollectionHelper;
use Alchemy\Phrasea\Model\Entities\ElasticsearchRecord;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\RecordInterface;
use Alchemy\Phrasea\Http\StaticFile\StaticMode;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Flag;

class PhraseanetExtension extends \Twig_Extension
{
    /** @var Application */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('sort_collections', array(CollectionHelper::class, 'sort')),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('user_setting', array($this, 'getUserSetting')),
            new \Twig_SimpleFunction('record_thumbnail_url', array($this, 'getThumbnailUrl')),
            new \Twig_SimpleFunction('record_subdef_url', array($this, 'getSubdefUrl')),
            new \Twig_SimpleFunction('record_subdef_size', array($this, 'getSubdefSize')),
            new \Twig_SimpleFunction('record_doctype_icon', array($this, 'getDoctypeIcon'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFunction('has_access_subdef', array($this, 'hasAccessSubDefinition')),
            new \Twig_SimpleFunction('record_thumbnailgif_url', array($this, 'getThumbnailGifUrl')),
            new \Twig_SimpleFunction('granted_on_collection', array($this, 'isGrantedOnCollection')),
            new \Twig_SimpleFunction('granted_on_databox', array($this, 'isGrantedOnDatabox')),
            new \Twig_SimpleFunction('collection_logo', array($this, 'getCollectionLogo'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFunction('record_flags', array($this, 'getRecordFlags')),
            new \Twig_SimpleFunction('border_checker_from_fqcn', array($this, 'getCheckerFromFQCN')),
            new \Twig_SimpleFunction('caption_field', array($this, 'getCaptionField')),
            new \Twig_SimpleFunction('caption_field_label', array($this, 'getCaptionFieldLabel')),
            new \Twig_SimpleFunction('caption_field_order', array($this, 'getCaptionFieldOrder')),

            new \Twig_SimpleFunction('flag_slugify', array(Flag::class, 'normalizeName')),
        );
    }

    /**
     * get localized field's label
     * @param RecordInterface $record
     * @param $fieldName
     * @return string - the name label
     */
    public function getCaptionFieldLabel(RecordInterface $record, $fieldName)
    {
        if ($record) {
            /** @var \appbox $appbox */
            $appbox = $this->app['phraseanet.appbox'];
            $databox = $appbox->get_databox($record->getDataboxId());

            foreach ($databox->get_meta_structure() as $meta) {
                /** @var \databox_field $meta */
                if ($meta->get_name() === $fieldName) {
                    return $meta->get_label($this->app['locale']);
                }
            }
        }

        return '';
    }

    public function getCaptionField(RecordInterface $record, $field, $value)
    {
        if ($record instanceof ElasticsearchRecord) {
            $highlights = $record->getHighlight();
            if (false === isset($highlights[$field])) {
                return implode('; ', (array) $value);
            }

            $highlightValue = $highlights[$field];

            // if field is multivalued, merge highlighted values with captions ones
            if (is_array($value)) {
                $highlightValue = array_merge($highlightValue, array_diff($value, array_map(function($value) {
                    return str_replace(array('[[em]]', '[[/em]]'), array('', ''), $value);
                }, $highlightValue)));
            }

            return implode('; ', (array) $highlightValue);
        }

        return implode('; ', (array) $value);
    }

    /**
     * @param RecordInterface $record
     * @param bool            $businessFields
     * @return array
     */
    public function getCaptionFieldOrder(RecordInterface $record, $businessFields)
    {
        static $orders = [];

        $databoxId = $record->getDataboxId();
        $orderKey = (bool) $businessFields ? 'business' : 'public';

        if (!isset($orders[$databoxId][$orderKey])) {
            /** @var \appbox $appbox */
            $appbox = $this->app['phraseanet.appbox'];
            $databox = $appbox->get_databox($databoxId);

            $orders[$databoxId] = $this->retrieveDataboxFieldOrderings($databox);
        }

        return $orders[$databoxId][$orderKey];
    }

    public function getRecordFlags(RecordInterface $record)
    {
        $recordStatuses = [];
        /** @var \appbox $appbox */
        $appbox = $this->app['phraseanet.appbox'];
        $databox = $appbox->get_databox($record->getDataboxId());

        $structure = $databox->getStatusStructure()->toArray();

        if (!$this->isGrantedOnCollection($record->getBaseId(), \ACL::CHGSTATUS)) {
            $structure = array_filter($structure, function($status) {
                return  (bool) $status['printable'];
            });
        }

        $bitValue = $record->getStatusBitField();

        foreach ($structure as $status) {
            $on = \databox_status::bitIsSet($bitValue, $status['bit']);

            if (null === ($on ? $status['img_on'] : $status['img_off'])) {
                continue;
            }

            $recordStatuses[] = [
                'path' => ($on ? $status['img_on'] : $status['img_off']),
                'labels' => ($on ? $status['labels_on_i18n'] : $status['labels_off_i18n'])
            ];
        }

        return $recordStatuses;
    }

    public function isGrantedOnDatabox($databoxId, $rights)
    {
        if (false === ($this->app->getAuthenticatedUser() instanceof User)) {

            return false;
        }

        $rights = (array) $rights;
        foreach ($rights as $right) {
            if (false === $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_sbas($databoxId, $right)) {

                return false;
            }
        }

        return true;
    }

    public function isGrantedOnCollection($baseId, $rights)
    {
        if (false === ($this->app->getAuthenticatedUser() instanceof User)) {

            return false;
        }

        $rights = (array) $rights;
        foreach ($rights as $right) {
            if (false === $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($baseId, $right)) {

                return false;
            }
        }

        return true;
    }

    public function getCollectionLogo($baseId)
    {
        if (false === $this->app['filesystem']->exists(sprintf('%s/config/minilogos/%s', $this->app['root.path'], $baseId))) {
            return '';
        }

        return sprintf(
            '<img title="%s" src="/custom/minilogos/%s" />',
            \phrasea::bas_labels($baseId, $this->app),
            $baseId
        );
    }

    public function hasAccessSubDefinition(RecordInterface $record, $subDefinition)
    {
        if (false === ($this->app->getAuthenticatedUser() instanceof User)) {

            return false;
        }

        return $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_access_to_subdef($record, $subDefinition);
    }

    public function getDoctypeIcon(RecordInterface $record)
    {
        $src = $title = '';
        if ($record->isStory()) {
            $src = '/assets/common/images/icons/icon_story.gif';
            $title = $this->app['translator']->trans('reportage');

            return sprintf('<img src="%s" title="%s" />', $src, $title);
        }

        switch ($record->getType()) {
            case 'image':
                $src = '/assets/common/images/icons/icon_image.png';
                $title = $this->app['translator']->trans('image');
                break;
            case 'document':
                $src = '/assets/common/images/icons/icon_document.png';
                $title = $this->app['translator']->trans('document');
                break;
            case 'video':
                $src = '/assets/common/images/icons/icon_video.png';
                $title = $this->app['translator']->trans('reportage');
                break;
            case 'audio':
                $src = '/assets/common/images/icons/icon_audio.png';
                $title = $this->app['translator']->trans('audio');
                break;
            case 'flash':
                $src = '/assets/common/images/icons/icon_flash.png';
                $title = $this->app['translator']->trans('flash');
                break;
        }

        return sprintf('<img src="%s" title="%s" />', $src, $title);
    }

    public function getThumbnailUrl(RecordInterface $record)
    {
        return $this->getSubdefUrl($record, 'thumbnail');
    }

    public function getThumbnailGifUrl(RecordInterface $record)
    {
        return $this->getSubdefUrl($record, 'thumbnailgif');
    }

    public function getSubdefUrl(RecordInterface $record, $subdefName)
    {
        /** @var StaticMode $staticMode */
        $staticMode = $this->app['phraseanet.static-file'];

        if ($record instanceof ElasticsearchRecord) {
            $subdefs = $record->getSubdefs();
            if (isset($subdefs[$subdefName])) {
                $thumbnail = $subdefs[$subdefName];
                if (null !== $path = $thumbnail['path']) {
                    if (is_string($path) && '' !== $path) {
                        $etag = dechex(crc32(dechex($record->getVersion() ^ 0x5A5A5A5A)));
                        return $staticMode->getUrl($path, $etag);
                    }
                }
            }
        } elseif ($record instanceof \record_adapter) {
            if (null !== $thumbnail = $record->get_subdef($subdefName)) {
                if ('' !== $path = $thumbnail->getRealPath()) {
                    $etag = $thumbnail->getEtag();
                    return $staticMode->getUrl($path, $etag);
                }
            }
        }

        $path = sprintf('/assets/common/images/icons/substitution/%s.png',
            str_replace('/', '_', $record->getMimeType())
        );

        return $path;
    }

    public function getSubdefSize(RecordInterface $record, $subdefName)
    {
        $ret = null;

        if ($record instanceof ElasticsearchRecord) {
            $subdefs = $record->getSubdefs();
            if (isset($subdefs[$subdefName])) {
                $subdef = $subdefs[$subdefName];
                if (isset($subdef['width']) && $subdef['width'] !== null && isset($subdef['height']) && $subdef['height'] !== null) {
                    $ret = [
                        'width' => $subdef['width'],
                        'height' => $subdef['height']
                    ];
                }
            }
        } elseif ($record instanceof \record_adapter) {
            if (null !== $subdef = $record->get_subdef($subdefName)) {
                $ret = [
                    'width' => $subdef->get_width(),
                    'height' => $subdef->get_height()
                ];
            }
        }

        return $ret;
    }

    public function getUserSetting($setting, $default = null)
    {
        if (false === ($this->app->getAuthenticatedUser() instanceof User)) {

            return $default;
        }

        return $this->app['settings']->getUserSetting($this->app->getAuthenticatedUser(), $setting, $default);
    }

    public function getCheckerFromFQCN($checkerFQCN)
    {
        return $this->app['border-manager']->getCheckerFromFQCN($checkerFQCN);
    }

    public function getName()
    {
        return 'phraseanet';
    }

    /**
     * @param \databox $databox
     * @return array
     */
    private function retrieveDataboxFieldOrderings(\databox $databox)
    {
        $publicOrder = [];
        $businessOrder = [];

        foreach ($databox->get_meta_structure() as $field) {
            $fieldName = $field->get_name();

            if (!$field->isBusiness()) {
                $publicOrder[] = $fieldName;
            }

            $businessOrder[] = $fieldName;
        };

        return [
            'public'   => $publicOrder,
            'business' => $businessOrder,
        ];
    }
}
