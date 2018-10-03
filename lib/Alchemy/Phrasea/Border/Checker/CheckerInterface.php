<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * The checker interface
 */
interface CheckerInterface
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
     * Checks if a Checker is applicable on a file
     *
     * @param  File    $file The file package object
     * @return Boolean
     */
    public function isApplicable(File $file);

    /**
     * Get a localized message about the Checker
     *
     * @param TranslatorInterface $translator A translator
     *
     * @return string
     */
    public function getMessage(TranslatorInterface $translator);
}
