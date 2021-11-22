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

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Exception\ExceptionInterface;
use MediaAlchemyst\Specification\Image as ImageSpecification;

// use Symfony\Component\Filesystem\Filesystem;


class LazaretFilesystemService
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Alchemyst
     */
    private $alchemyst;

    /**
     * @var LazaretPathBooker
     */
    private $booker;

    public function __construct(Filesystem $filesystem, $tmpPath, Alchemyst $alchemyst)
    {
        $this->filesystem = $filesystem;
        $this->alchemyst = $alchemyst;

        $this->booker = new LazaretPathBooker($filesystem, $tmpPath);
    }

    /**
     * Write a file in storage and mark it lazaret
     * @param File $file
     * @return PersistedLazaretInformation
     */
    public function writeLazaret(File $file)
    {
        $lazaretPathname = $this->booker->bookFile($file->getOriginalName());
        $this->filesystem->copy($file->getFile()->getRealPath(), $lazaretPathname, true);

        $lazaretPathnameThumb = $this->booker->bookFile($file->getOriginalName(), 'thumb');
        try {
            $this->alchemyst->turnInto($file->getFile()->getPathname(), $lazaretPathnameThumb, $this->createThumbnailSpecification());
        } catch (ExceptionInterface $e) {
            // Ignore, an empty file should be present
        }

        return new PersistedLazaretInformation($lazaretPathname, $lazaretPathnameThumb);
    }

    /**
     * @return ImageSpecification
     */
    private function createThumbnailSpecification()
    {
        $spec = new ImageSpecification();

        $spec->setResizeMode(ImageSpecification::RESIZE_MODE_INBOUND_FIXEDRATIO);
        $spec->setDimensions(375, 275);

        return $spec;
    }
}
