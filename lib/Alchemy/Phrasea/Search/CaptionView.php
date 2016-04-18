<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Search;

class CaptionView
{
    /**
     * @var \caption_record
     */
    private $caption;

    public function __construct(\caption_record $caption)
    {
        $this->caption = $caption;
    }

    /**
     * @return \caption_record
     */
    public function getCaption()
    {
        return $this->caption;
    }
}
