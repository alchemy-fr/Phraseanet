<?php

namespace Alchemy\Phrasea\Core\Thumbnail;

use Symfony\Component\HttpFoundation\File\File;

interface ThumbnailManager
{

    const TYPE_LOGO = 'minilogos';

    const TYPE_PDF = 'logopdf';

    const TYPE_WM = 'wm';

    const TYPE_STAMP = 'stamp';

    const TYPE_PRESENTATION = 'presentation';

    public function setThumbnail(ThumbnailedElement $element, $thumbnailType, File $file = null);
}
