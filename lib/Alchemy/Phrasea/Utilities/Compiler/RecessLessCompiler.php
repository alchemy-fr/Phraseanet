<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Utilities\Compiler;

use Symfony\Component\Filesystem\Filesystem;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Process\ProcessBuilder;

class RecessLessCompiler
{
    public function __construct($filesystem = null)
    {
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function compile($target, $files)
    {
        $this->filesystem->mkdir(dirname($target));

        if (!$files instanceof \Traversable) {
            $files = new \ArrayObject(is_array($files) ? $files : array($files));
        }

        $files = new ArrayCollection((array) $files);

        if ($files->forAll(function($file) {
            return is_file($file);
        })) {
            throw new RuntimeException(realpath($files) . ' does not exists.');
        }

        if (!is_writable(dirname($target))) {
            throw new RuntimeException(realpath(dirname($target)) . ' is not writable.');
        }

        $builder = ProcessBuilder::create(array_merge(array(
            'recess',
            '--compile'
        ), $files->toArray()));

        $process = $builder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf('An errord occured during the build %s', $process->getErrorOutput()));
        }

        $this->filesystem->dumpFile($target, $process->getOutput());
    }
}