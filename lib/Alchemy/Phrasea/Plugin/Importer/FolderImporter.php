<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin\Importer;

use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Alchemy\Phrasea\Plugin\Exception\ImportFailureException;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FsException;

// use Symfony\Component\Filesystem\Filesystem;

class FolderImporter implements ImporterInterface
{
    private $fs;

    public function __construct(Filesystem $fs)
    {
        $this->fs = $fs;
    }

    /**
     * {@inheritdoc}
     */
    public function import($source, $target)
    {
        try {
            $this->fs->mirror($source, $target);
        } catch (FsException $e) {
            try {
                $this->fs->remove($target);
            } catch (FsException $e) {

            }

            throw new ImportFailureException(sprintf('Unable to import from `%s` to `%s`', $source, $target), $e->getCode(), $e);
        }
    }
}
