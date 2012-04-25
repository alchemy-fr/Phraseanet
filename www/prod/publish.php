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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once __DIR__ . "/../../lib/bootstrap.php";

$message = '';
try {
    $pub = new action_publish(http_request::getInstance());
} catch (Exception $e) {
    $message = $e->getMessage();
}
try {
    $pub->render($message);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
