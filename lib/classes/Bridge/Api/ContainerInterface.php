<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

interface Bridge_Api_ContainerInterface
{

    public function get_id();

    public function get_thumbnail($width = 120, $height = 90);

    public function get_url();

    public function get_title();

    public function get_description();

    public function get_updated_on();

    public function get_created_on();

    public function get_type();
}
