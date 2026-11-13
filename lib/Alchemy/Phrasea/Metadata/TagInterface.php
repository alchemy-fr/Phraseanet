<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Metadata;

use PHPExiftool\Driver\TagGroupInterface;

/**
 * Source tag of a databox field: either a Phraseanet tag or an exiftool tag group.
 */
interface TagInterface extends TagGroupInterface
{
    /**
     * The source name as stored in the databox structure, e.g. "IPTC:Keywords" or "Phraseanet:tf-filename"
     */
    public function getTagname(): string;

    /**
     * The group part of the tag name, e.g. "IPTC" or "Phraseanet"
     */
    public function getGroupName(): string;

    public function getMinLength(): int;
}
