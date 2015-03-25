<?php

namespace Alchemy\Phrasea\Twig;

use Alchemy\Phrasea\Model\Entities\ElasticsearchRecord;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\RecordInterface;
use Silex\Application;

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
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('user_setting', array($this, 'getUserSetting')),
            new \Twig_SimpleFunction('record_thumbnail_url', array($this, 'getThumbnailUrl')),
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
            new \Twig_SimpleFunction('border_checker_from_fqcn', array($this->app['border-manager'], 'getCheckerFromFQCN')),
            new \Twig_SimpleFunction('format_duration', array($this, 'formatDuration')),
        );
    }

    public function getRecordFlags(RecordInterface $record)
    {
        $recordStatuses = [];
        $databox = $this->app['phraseanet.appbox']->get_databox($record->getDataboxId());

        $structure = $databox->getStatusStructure()->toArray();

        if (!$this->isGrantedOnCollection($record->getBaseId(), 'chgstatus')) {
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
        if (false === ($this->app['authentication']->getUser() instanceof User)) {

            return false;
        }

        $rights = (array) $rights;
        foreach ($rights as $right) {
            if (false === $this->app['acl']->get($this->app['authentication']->getUser())->has_right_on_sbas($databoxId, $right)) {

                return false;
            }
        }

        return true;
    }

    public function isGrantedOnCollection($baseId, $rights)
    {
        if (false === ($this->app['authentication']->getUser() instanceof User)) {

            return false;
        }

        $rights = (array) $rights;
        foreach ($rights as $right) {
            if (false === $this->app['acl']->get($this->app['authentication']->getUser())->has_right_on_base($baseId, $right)) {

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
        if (false === ($this->app['authentication']->getUser() instanceof User)) {

            return false;
        }

        return $this->app['acl']->get($this->app['authentication']->getUser())->has_access_to_subdef($record, $subDefinition);
    }

    public function getDoctypeIcon(RecordInterface $record)
    {
        $src = $title = '';
        if ($record->isStory()) {
            $src = '/skins/icons/icon_story.gif';
            $title = $this->app['translator']->trans('reportage');

            return sprintf('<img src="%s" title="%s" />', $src, $title);
        }

        switch ($record->getType()) {
            case 'image':
                $src = '/skins/icons/icon_image.png';
                $title = $this->app['translator']->trans('image');
                break;
            case 'document':
                $src = '/skins/icons/icon_document.png';
                $title = $this->app['translator']->trans('document');
                break;
            case 'video':
                $src = '/skins/icons/icon_video.png';
                $title = $this->app['translator']->trans('reportage');
                break;
            case 'audio':
                $src = '/skins/icons/icon_audio.png';
                $title = $this->app['translator']->trans('audio');
                break;
            case 'flash':
                $src = '/skins/icons/icon_flash.png';
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
        if ($record instanceof ElasticsearchRecord) {
            if ($record->getSubdefs()->containsKey($subdefName)) {
                $thumbnail = $record->getSubdefs()->get($subdefName);
                if (null !== $path = $thumbnail['path']) {
                    if (is_string($path) && '' !== $path) {
                        return $this->app['phraseanet.static-file']->getUrl($path);
                    }
                }
            }
        } elseif ($record instanceof \record_adapter) {
            if (null !== $thumbnail = $record->get_subdef($subdefName)) {
                if ('' !== $path = $thumbnail->get_pathfile()) {
                    return $this->app['phraseanet.static-file']->getUrl($path);
                }
            }
        }

        $path = sprintf('/skins/icons/substitution/%s.png',
            str_replace('/', '_', $record->getMimeType())
        );

        return $path;
    }

    public function getUserSetting($setting, $default = null)
    {
        if (false === ($this->app['authentication']->getUser() instanceof User)) {

            return $default;
        }

        return $this->app['settings']->getUserSetting($this->app['authentication']->getUser(), $setting, $default);
    }

    public function getName()
    {
        return 'phraseanet';
    }
    
    public function formatDuration($mediaDuration)
    {
        $duration = gmdate("H:i:s", $mediaDuration);
        return $duration;
    }

}
