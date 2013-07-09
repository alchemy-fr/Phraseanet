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
    /**
     * @var array
     */
    protected $errors = array();

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
        $failures = 0;
        $this->errors = array();

        foreach ($files as $lessFile => $target) {
            $this->filesystem->mkdir(dirname($target));

            try {
                $this->compiler->compile($target, $lessFile);
            } catch (\Exception $e) {
                $failures++;
                $this->errors[] = $e->getMessage();
            }
        }

        return $this->hasErrors();
    }

    public function hasErrors()
    {
        return count($this->errors) === 0;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
