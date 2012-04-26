<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     caption
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface caption_interface
{

    public function __construct(record_Interface &$record, databox &$databox);

    public function get_highlight_fields($highlight = '', Array $grep_fields = null, searchEngine_adapter $searchEngine = null);
}
