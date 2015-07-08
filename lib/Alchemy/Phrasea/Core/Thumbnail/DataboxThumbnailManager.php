<?php

namespace Alchemy\Phrasea\Core\Thumbnail;

use Alchemy\Phrasea\Application;
use MediaAlchemyst\Specification\Image as ImageSpecification;
use Symfony\Component\HttpFoundation\File\File;

class DataboxThumbnailManager extends AbstractThumbnailManager implements ThumbnailManager
{
    public function setThumbnail(ThumbnailedElement $element, $thumbnailType, File $file = null)
    {
        $filename = null;

        if ($thumbnailType !== ThumbnailManager::TYPE_PDF) {
            throw new \InvalidArgumentException('Unsupported thumbnail type.');
        }

        if (!is_null($file)) {
            $this->validateFileMimeType($file);
            $filename = $this->generateThumbnail($file);
        }

        $logoFile = $this->rootPath . '/config/minilogos/' . $thumbnailType . '_' . $element->getRootIdentifier() . '.jpg';
        $custom_path = $this->rootPath . '/www/custom/minilogos/' . $thumbnailType . '_' . $element->getRootIdentifier() . '.jpg';

        foreach ([$logoFile, $custom_path] as $target) {
            $this->copyFile($target, $filename);
        }

        $element->updateThumbnail($thumbnailType, $file);
    }

    /**
     * @param File $file
     * @return string
     */
    protected function generateThumbnail(File $file)
    {
        $imageSpec = new ImageSpecification();

        $this->setSpecificationSize($imageSpec, 120, 35);

        $filename = $this->resizeMediaFile($file, $imageSpec);

        return $filename;
    }
}
