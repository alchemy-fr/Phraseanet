<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin\Schema;

use Alchemy\Phrasea\Plugin\Exception\PluginValidationException;
use Alchemy\Phrasea\Plugin\Exception\JsonValidationException;

class PluginValidator
{
    private $manifestValidator;

    public function __construct(ManifestValidator $manifestValidator)
    {
        $this->manifestValidator = $manifestValidator;
    }

    public function validatePlugin($directory)
    {
        $this->ensureComposer($directory);
        $this->ensureManifest($directory);
        $this->ensureDir($directory . DIRECTORY_SEPARATOR . 'public');

        $manifest = $directory . DIRECTORY_SEPARATOR . 'manifest.json';
        $data = @json_decode(@file_get_contents($manifest));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new PluginValidationException(sprintf('Unable to parse file %s', $manifest));
        }

        try {
            $this->manifestValidator->validate($data);
        } catch (JsonValidationException $e) {
            throw new PluginValidationException('Manifest file is invalid', $e->getCode(), $e);
        }

        $manifest = new Manifest($this->objectToArray($data));

        foreach ($manifest->getTwigPaths() as $path) {
            $this->ensureDirIn($directory . DIRECTORY_SEPARATOR . $path, $directory);
        }

        return $manifest;
    }

    private function ensureManifest($directory)
    {
        $manifest = $directory . DIRECTORY_SEPARATOR . 'manifest.json';
        $this->ensureFile($manifest);
    }

    private function ensureComposer($directory)
    {
        $composer = $directory . DIRECTORY_SEPARATOR . 'composer.json';
        $this->ensureFile($composer);
    }

    private function ensureDir($dir, $message = 'Missing mandatory directory %s')
    {
        if (!file_exists($dir) || !is_dir($dir) || !is_readable($dir)) {
            throw new PluginValidationException(sprintf($message, $dir));
        }
    }

    private function ensureDirIn($dir, $in, $message = 'Invalid twig-path declaration ; directory %s is not a subdir of %s')
    {
        $this->ensureDir($dir);

        if (0 !== strpos(realpath($dir), realpath($in))) {
            throw new PluginValidationException(sprintf($message, $dir, $in));
        }
    }

    private function ensureFile($file)
    {
        if (!file_exists($file) || !is_file($file) || !is_readable($file)) {
            throw new PluginValidationException(sprintf('Required file %s is not present.', $file));
        }
    }

    private function objectToArray($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            return array_map([$this, 'objectToArray'], $data);
        }

        return $data;
    }
}
