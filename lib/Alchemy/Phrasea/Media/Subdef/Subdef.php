<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Subdef;

use MediaAlchemyst\Specification\SpecificationInterface;

interface Subdef
{
    const TYPE_IMAGE = 'image';
    const TYPE_ANIMATION = 'gif';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    const TYPE_FLEXPAPER = 'flexpaper';
    const TYPE_UNKNOWN = 'unknown';

    /**
     * One of Subdef Type const
     *
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return SpecificationInterface
     */
    public function getMediaAlchemystSpec();
}
