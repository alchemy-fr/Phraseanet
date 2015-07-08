<?php

namespace Alchemy\Phrasea\Core\Thumbnail;

use Symfony\Component\HttpFoundation\File\File;

interface ThumbnailedElement
{

    public function getRootIdentifier();

    public function updateThumbnail($thumbnailType, File $file = null);
}
