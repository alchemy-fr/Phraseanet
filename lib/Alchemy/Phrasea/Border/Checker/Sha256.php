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
 * Checks if a file with the same Sha256 checksum already exists in the
 * destination databox
 */
class Sha256 extends AbstractChecker
{

    /**
     * {@inheritdoc}
     */
    public function check(EntityManager $em, File $file)
    {
        $boolean = ! count(\record_adapter::get_record_by_sha(
                    $file->getCollection()->get_databox()->get_sbas_id(), $file->getSha256()
                ));

        return new Response($boolean, $this);
    }

    /**
     * {@inheritdoc}
     */
    public static function getMessage()
    {
        return _('A file with the same checksum already exists in database');
    }
}
