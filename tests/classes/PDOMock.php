<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class is used to mock PDO object with PHPUNIT mock system.
 *
 * Because __wakeup and __sleep methods are defined as final methods
 * We can not serialize a PDO object and therefore we can not mock
 * This object using PHPUnit.
 *
 * To get a mocked PDO object use it as follow :
 *
 * $mock = $this->getMock('PDOMock')
 */
class PDOMock extends \PDO
{
    public function __construct() {}
}