<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

interface API_OAuth2_Exception_Interface
{

    public function getError();

    public function getHttp_code();

    public function getError_description();

    public function getError_uri();
}
