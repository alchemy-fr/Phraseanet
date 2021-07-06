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
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Checks if a file with the same Sha256 checksum already exists in the
 * destination databox
 */
class Sha256 extends AbstractChecker
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
        file_put_contents($GLOBALS['app']['root.path'].'/logs/trace.txt', sprintf("\n%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("into checker sha256")
        ), FILE_APPEND | LOCK_EX);

        $excludedCollIds = [];
        if (!empty($this->compareIgnoreCollections)) {
            foreach ($this->compareIgnoreCollections as $collection) {
                // use only collection in the same databox and retrieve the coll_id
                if ($collection->get_sbas_id() === $file->getCollection()->get_sbas_id()) {
                    $excludedCollIds[] = $collection->get_coll_id();
                }
            }
        }

        $boolean = empty($file->getCollection()->get_databox()->getRecordRepository()->findBySha256WithExcludedCollIds($file->getSha256(), $excludedCollIds));

        file_put_contents($GLOBALS['app']['root.path'].'/logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("return from checker sha256")
        ), FILE_APPEND | LOCK_EX);

        return new Response($boolean, $this);
    }

    /**
     * @param Application $app
     * @param LazaretFile $file
     * @return \record_adapter[]
     */
    public static function listConflicts(Application $app, LazaretFile $file)
    {
        $databox = $file->getCollection($app)->get_databox();

        return $databox->getRecordRepository()->findBySha256($file->getSha256());
    }

    /**
     * {@inheritdoc}
     */
    public static function getReason(TranslatorInterface $translator)
    {
        return $translator->trans('same checksum');
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(TranslatorInterface $translator)
    {
        return $translator->trans('A file with the same checksum already exists in database');
    }
}
