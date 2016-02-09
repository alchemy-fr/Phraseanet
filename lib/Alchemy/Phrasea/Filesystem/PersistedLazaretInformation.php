<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Filesystem;

class PersistedLazaretInformation
{
    /**
     * @var string
     */
    private $fileCopyRealPath;

    /**
     * @var string
     */
    private $thumbnailRealPath;

    public function __construct($fileCopyRealPath, $thumbnailRealPath)
    {
        $this->fileCopyRealPath = $fileCopyRealPath;
        $this->thumbnailRealPath = $thumbnailRealPath;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return pathinfo($this->fileCopyRealPath, PATHINFO_BASENAME);
    }

    /**
     * @return string
     */
    public function getThumbnailFilename()
    {
        return pathinfo($this->thumbnailRealPath, PATHINFO_BASENAME);
    }
}
