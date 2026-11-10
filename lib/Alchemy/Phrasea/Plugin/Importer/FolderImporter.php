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
        // PhraseanetFilesystem::copy() writes "<file>.<unixtime>-<rand>" temp files next to their
        // destination and renames them atomically. A process killed mid-copy (e.g. a worker crash)
        // leaves those temps behind; on a later import they pollute the plugin and make mirror()
        // choke ("Unable to guess file type"). Purge such orphan temps from the source first.
        $this->purgeOrphanCopyTemps($source);

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

    /**
     * Removes leftover atomic-copy temp files ("<name>.<unixtime>-<rand>") under $dir.
     *
     * These are only ever created transiently by PhraseanetFilesystem::copy(); any that survive
     * are orphans from an interrupted copy and must not be imported.
     *
     * @param string $dir
     */
    private function purgeOrphanCopyTemps($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.\d{9,}-\d{1,4}$/', $file->getFilename())) {
                try {
                    $this->fs->remove($file->getPathname());
                } catch (FsException $e) {
                    // best-effort cleanup
                }
            }
        }
    }
}
