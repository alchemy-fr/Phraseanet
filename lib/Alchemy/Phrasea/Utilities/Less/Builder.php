<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Utilities\Less;

use Alchemy\Phrasea\Utilities\Less\Compiler as LessCompiler;
use Symfony\Component\Filesystem\Filesystem;

class Builder
{
    /**
     * @var LessCompiler
     */
    protected $compiler;
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(LessCompiler $compiler, Filesystem $filesystem)
    {
        $this->compiler = $compiler;
        $this->filesystem = $filesystem;
    }

    /**
     * Build LESS files
     *
     * @param array $files
     */
    public function build($files)
    {
        foreach ($files as $lessFile => $target) {
            $this->filesystem->mkdir(dirname($target));
            $this->compiler->compile($target, $lessFile);
        }
    }
}
