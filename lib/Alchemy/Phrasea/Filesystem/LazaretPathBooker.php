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

use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

// use Symfony\Component\Filesystem\Filesystem;


class LazaretPathBooker
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
     * @var callable
     */
    private $pathResolver;

    /**
     * @param Filesystem $filesystem
     * @param string $tmpPath
     * @param callable|string $pathResolver
     */
    public function __construct(Filesystem $filesystem, $tmpPath, $pathResolver = 'realpath')
    {
        $this->filesystem = $filesystem;
        $this->tmpPath = $tmpPath;

        if (!is_callable($pathResolver)) {
            throw new \LogicException('pathResolver should be callable');
        }

        $this->pathResolver = $pathResolver;
    }

    /**
     * @param string $filename
     * @param string $suffix
     * @return string
     */
    public function bookFile($filename, $suffix = '')
    {
        // stripped all non-alpha-numeric in filename
        $filename = preg_replace("/[^a-zA-Z0-9-_.]/", '', $filename);

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

        return $this->resolvePath($output);
    }

    /**
     * @param string $path
     * @return string
     */
    private function resolvePath($path)
    {
        $callable = $this->pathResolver;

        return $callable($path);
    }
}
