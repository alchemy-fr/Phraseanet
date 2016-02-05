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
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Exception\ExceptionInterface;
use MediaAlchemyst\Specification\Image as ImageSpecification;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class LazaretFilesystemService
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $tmpPath;

    /**
     * @var Alchemyst
     */
    private $alchemyst;

    public function __construct(Filesystem $filesystem, $tmpPath, Alchemyst $alchemyst)
    {
        $this->filesystem = $filesystem;
        $this->tmpPath = $tmpPath;
        $this->alchemyst = $alchemyst;
    }

    /**
     * Write a file in storage and mark it lazaret
     * @param File $file
     * @return PersistedLazaretInformation
     */
    public function writeLazaret(File $file)
    {
        $lazaretPathname = $this->bookLazaretPathfile($file->getOriginalName());
        $this->filesystem->copy($file->getFile()->getRealPath(), $lazaretPathname, true);

        $lazaretPathnameThumb = $this->bookLazaretPathfile($file->getOriginalName(), 'thumb');
        try {
            $this->alchemyst->turnInto($file->getFile()->getPathname(), $lazaretPathnameThumb, $this->createThumbnailSpecification());
        } catch (ExceptionInterface $e) {
            // Ignore, an empty file should be present
        }

        return new PersistedLazaretInformation($lazaretPathname, $lazaretPathnameThumb);
    }

    private function bookLazaretPathfile($filename, $suffix = '')
    {
        $output = $this->tmpPath .'/lzrt_' . substr($filename, 0, 3) . '_' . $suffix . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        $infos = pathinfo($output);
        $n = 0;

        while (true) {
            $output = sprintf('%s/%s-%d%s', $infos['dirname'], $infos['filename'],  ++ $n, (isset($infos['extension']) ? '.' . $infos['extension'] : ''));

            try {
                if (! $this->filesystem->exists($output)) {
                    $this->filesystem->touch($output);
                    break;
                }
            } catch (IOException $e) {

            }
        }

        return realpath($output);
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
