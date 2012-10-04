<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

/**
 * A interface to precise some specific configuration file mechanism
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface SpecificationInterface
{

    public function setConfigurations($configurations);

    public function setConnexions($connexions);

    public function setServices($services);

    public function getConfigurations();

    public function getConnexions();

    public function getServices();

    public function initialize();

    public function delete();

    public function isSetup();
}
