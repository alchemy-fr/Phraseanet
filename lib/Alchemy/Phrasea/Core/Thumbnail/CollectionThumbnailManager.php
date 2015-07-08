<?php

namespace Alchemy\Phrasea\Core\Thumbnail;

use Alchemy\Phrasea\Application;
use MediaAlchemyst\Specification\Image as ImageSpecification;
use Symfony\Component\HttpFoundation\File\File;

class CollectionThumbnailManager extends AbstractThumbnailManager implements ThumbnailManager
{

    /**
     * @param ThumbnailedElement $element
     * @param $thumbnailType
     * @param File $file
     */
    public function setThumbnail(ThumbnailedElement $element, $thumbnailType, File $file = null)
    {
        $filename = null;

        if (!is_null($file)) {
            $this->validateFileMimeType($file);
            $filename = $this->generateThumbnail($thumbnailType, $file);
        }

        $logoFile = $this->rootPath . '/config/' . $thumbnailType . '/' . $element->getRootIdentifier();
        $custom_path = $this->rootPath . '/www/custom/' . $thumbnailType . '/' . $element->getRootIdentifier();

        foreach ([$logoFile, $custom_path] as $target) {
            $this->copyFile($target, $filename);
        }

        $element->updateThumbnail($thumbnailType, $file);
    }


    /**
     * @param $thumbnailType
     * @param File $file
     * @return string
     */
    protected function generateThumbnail($thumbnailType, File $file)
    {
        $filename = $file->getPathname();
        $imageSpec = new ImageSpecification();

        if ($thumbnailType === ThumbnailManager::TYPE_LOGO) {
            //resize collection logo
            $media = $this->application->getMediaFromUri($filename);

            if ($this->shouldResize($media, 120, 24)) {
                $this->setSpecificationSize($imageSpec, 120, 24);
            }

            $filename = $this->resizeMediaFile($file, $imageSpec);

            return $filename;
        } elseif ($thumbnailType === ThumbnailManager::TYPE_PRESENTATION) {
            //resize collection logo
            $this->setSpecificationSize($imageSpec, 650, 200);

            $filename = $this->resizeMediaFile($file, $imageSpec);

            return $filename;
        }

        return $filename;
    }
}
