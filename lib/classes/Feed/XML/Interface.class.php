<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     Feeds
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface Feed_XML_Interface
{

    public function set_title($title);

    public function set_updated_on(DateTime $datetime);

    public function set_subtitle($subtitle);

    public function set_link(Feed_Link $link);

    public function set_next_page(Feed_Link $next_page);

    public function set_previous_page(Feed_Link $previous_page);

    public function set_item(Feed_Entry_Adapter $entry);

    public function set_generator($generator);

    public function add_navigation(DOMDocument $document, DOMNode $node, $namespaced);

    public function get_mimetype();
}
