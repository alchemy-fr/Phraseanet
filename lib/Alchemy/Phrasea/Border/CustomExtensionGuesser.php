<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use Symfony\Component\Mime\MimeTypesInterface;

class CustomExtensionGuesser implements MimeTypesInterface
{
    private $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = array_change_key_case($mapping, CASE_LOWER);
    }

    public function isGuesserSupported(): bool
    {
        return true;
    }

    public function guessMimeType(string $path): ?string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $this->mapping[$extension] ?? null;
    }

    public function getExtensions(string $mimeType): array
    {
        return array_map('strval', array_keys($this->mapping, $mimeType, true));
    }

    public function getMimeTypes(string $ext): array
    {
        $ext = strtolower($ext);

        return isset($this->mapping[$ext]) ? [$this->mapping[$ext]] : [];
    }
}
