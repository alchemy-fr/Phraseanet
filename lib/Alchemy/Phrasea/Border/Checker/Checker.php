<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;
use Doctrine\ORM\EntityManager;

/**
 * The checker interface
 */
interface Checker
{

    /**
     * Checks constraints on the file
     *
     * @param  EntityManager $em   The entity manager
     * @param  File          $file The file package object
     * @return Response      A Response object
     */
    public function check(EntityManager $em, File $file);

    /**
     * Get a localized message about the Checker
     */
    public static function getMessage();
}
