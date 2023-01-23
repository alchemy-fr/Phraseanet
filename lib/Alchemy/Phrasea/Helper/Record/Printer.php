<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\Record;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Helper\Record\Helper as RecordHelper;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Phrasea\Out\Module\PDFRecords;
use Cocur\Slugify\Slugify;
use Symfony\Component\HttpFoundation\Request;

class Printer extends RecordHelper
{
    protected $flatten_groupings = true;
    private $thumbnailName = 'thumbnail';
    private $previewName = 'preview';
    private $urlTtl = null;
    private $titleAsDownloadName = true;
    private $slugify;

    /**
     * @var \ACL
     */
    private $userACL;

    /**
     *
     * @param Application $app
     * @param Request     $Request
     *
     * @return Helper
     */
    public function __construct(Application $app, Request $Request)
    {
        parent::__construct($app, $Request);
        $this->userACL = $app->getAclForUser($app->getAuthenticatedUser());

        $grep = function (\record_adapter $record) {
                try {
                    return $record->get_thumbnail()->get_type() == \media_subdef::TYPE_IMAGE ||
                        $record->get_preview()->get_type() == \media_subdef::TYPE_IMAGE;
                } catch (\Exception $e) {
                    return false;
                }
            };

        $this->grep_records($grep);
    }

    public function get_count_preview()
    {
        $n = 0;
        foreach ($this->get_elements() as $element) {
            try {
                $element->get_preview()->get_type() == \media_subdef::TYPE_IMAGE;
                $n ++;
            } catch (\Exception $e) {

            }
        }

        return $n;
    }

    public function get_count_thumbnail()
    {
        $n = 0;
        foreach ($this->get_elements() as $element) {
            try {
                $element->get_thumbnail()->get_type() == \media_subdef::TYPE_IMAGE;
                $n ++;
            } catch (\Exception $e) {

            }
        }

        return $n;
    }

    /**
     * Get count of available subdef with image printable
     *
     * @return array
     * @throws \Exception
     */
    public function getSubdefImageCount()
    {
        $countSubdefs = [];
        foreach ($this->get_elements() as $element) {
            foreach ($this->getAvailableSubdefName(true) as $subdefName) {
                if (!isset($countSubdefs[$subdefName])) {
                    $countSubdefs[$subdefName] = 0;
                }
                if (
                    ($subdefName == 'document' && $element->getType() == 'image' && $element->get_subdef($subdefName)->is_physically_present() &&
                    $this->userACL->has_right_on_base($element->getBaseId(), \ACL::CANDWNLDHD))
                    ||
                    ($subdefName != 'document' && $element->has_subdef($subdefName) &&
                    $element->get_subdef($subdefName)->get_type() == \media_subdef::TYPE_IMAGE &&
                    $this->userACL->has_access_to_subdef($element, $subdefName) &&
                    $element->get_subdef($subdefName)->is_physically_present())
                )
                {
                    $countSubdefs[$subdefName] ++;
                }
            }
        }

        return $countSubdefs;
    }

    /**
     * Get count of available subdef
     *
     * @return array
     * @throws \Exception
     */
    public function getSubdefCount()
    {
        $countSubdefs = [];
        foreach ($this->get_elements() as $element) {
            foreach ($this->getAvailableSubdefName() as $subdefName) {
                if (!isset($countSubdefs[$subdefName])) {
                    $countSubdefs[$subdefName] = 0;
                }
                if (
                    ($subdefName == 'document' &&
                    $this->userACL->has_right_on_base($element->getBaseId(), \ACL::CANDWNLDHD))
                    ||
                    ($subdefName != 'document' && $element->has_subdef($subdefName) &&
                    $this->userACL->has_right_on_base($element->getBaseId(), \ACL::CANDWNLDPREVIEW) &&
                    $this->userACL->has_access_to_subdef($element, $subdefName) &&
                    $element->get_subdef($subdefName)->is_physically_present())
                )
                {
                    $countSubdefs[$subdefName] ++;
                }
            }
        }

        return $countSubdefs;
    }

    public function getAvailableSubdefName($isForImage = false)
    {
        $databoxes = $this->app->getApplicationBox()->get_databoxes();
        $availableSubdefName[] = 'document';

        foreach ($this->selection->get_distinct_sbas_ids() as $sbasId) {
            if (isset($databoxes[$sbasId])) {
                /** @var \databox $databox */
                $databox = $databoxes[$sbasId];
                foreach ($databox->get_subdef_structure() as $subdefGroup) {
                    /** @var \databox_subdef $subdef */
                    foreach ($subdefGroup as $subdef) {
                        if ($isForImage && $subdef->getSubdefType()->getType() == Subdef::TYPE_IMAGE) {
                            $availableSubdefName[] = $subdef->get_name();
                        } elseif (!$isForImage) {
                            $availableSubdefName[] = $subdef->get_name();
                        }
                    }
                }
            }
        }

        return array_unique($availableSubdefName);
    }

    public function setPreviewName($previewName)
    {
        $this->previewName = $previewName;
    }

    public function setThumbnailName($thumbnailName)
    {
        $this->thumbnailName = $thumbnailName;
    }

    public function setUrlTtl($urlTtl)
    {
        $this->urlTtl = $urlTtl;
    }

    public function setTitleAsDownloadName($titleAsDownloadName)
    {
        $this->titleAsDownloadName = !!$titleAsDownloadName;
    }

    public function getUrlTtl()
    {
        return $this->urlTtl;
    }

    public function getPreviewName()
    {
        return $this->previewName;
    }

    public function getThumbnailName()
    {
        return $this->thumbnailName;
    }

    public function getTitleAsDownloadName()
    {
        return $this->titleAsDownloadName;
    }

    public function sanitizeString($string)
    {
        return str_replace(['/', '\\'], '', $string);
    }

    public function normalizeString($string)
    {
        if (!isset($this->slugify)) {
            $this->slugify = new Slugify();
        }

        $string = $this->sanitizeString($string);

        return mb_substr($this->slugify->slugify($string, '-'), 0, PDFRecords::$maxFilenameLength);
    }

}
