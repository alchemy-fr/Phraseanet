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

trait CaptionAware
{
    /**
     * @var CaptionView
     */
    private $caption;

    /**
     * @param CaptionView $caption
     */
    public function setCaption(CaptionView $caption)
    {
        $this->caption = $caption;
    }

    /**
     * @return CaptionView
     */
    public function getCaption()
    {
        return $this->caption;
    }
}
