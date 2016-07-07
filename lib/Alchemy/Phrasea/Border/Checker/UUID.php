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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\File;
use Doctrine\ORM\EntityManager;
use Entities\LazaretFile;

/**
 * Checks if a file with the same UUID already exists in the destination databox
 */
class UUID extends AbstractChecker
{

    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    /**
     * {@inheritdoc}
     */
    public function check(EntityManager $em, File $file)
    {
        $boolean = ! count(\record_adapter::get_record_by_uuid(
                    $this->app, $file->getCollection()->get_databox(), $file->getUUID()
                ));

        return new Response($boolean, $this);
    }

    /**
     * @param LazaretFile $file
     * @return \record_adapter[]
     */
    public static function listConflicts(Application $app, LazaretFile $file)
    {
        return \record_adapter::get_record_by_uuid(
            $app, $file->getCollection($app)->get_databox(), $file->getUUID()
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getReason()
    {
        return _('same UUID');
    }

    /**
     * {@inheritdoc}
     */
    public static function getMessage()
    {
        return _('A file with the same UUID already exists in database');
    }
}
