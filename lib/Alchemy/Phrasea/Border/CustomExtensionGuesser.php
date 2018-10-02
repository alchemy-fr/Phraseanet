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

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

class CustomExtensionGuesser implements MimeTypeGuesserInterface
{
    private $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function guess($path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (isset($this->mapping[$extension])) {
            return $this->mapping[$extension];
        }
    }
}
