<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

return (new Application\ApiApplicationLoader())->buildApplication(
    isset($environment) ? $environment : Application::ENV_PROD,
    isset($forceDebug) ? $forceDebug : false
);
