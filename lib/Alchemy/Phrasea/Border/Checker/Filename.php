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
 * Checks if a file with the same filename already exists in the destination databox
 */
class Filename extends AbstractChecker
{
    protected $sensitive;

    /**
     * Constructor
     *
     * @param Application $app
     * @param array       $options An array of options. available : 'sensitive' (false by default)
     */
    public function __construct(Application $app, array $options = [])
    {
        if (!isset($options['sensitive'])) {
            $options['sensitive'] = false;
        }

        $this->sensitive = (boolean) $options['sensitive'];
        parent::__construct($app);
    }

    /**
     * {@inheritdoc}
     */
    public function check(EntityManager $em, File $file)
    {
        $excludedCollIds = [];
        if (!empty($this->compareIgnoreCollections)) {
            foreach ($this->compareIgnoreCollections as $collection) {
                // use only collection in the same databox and retrieve the coll_id
                if ($collection->get_sbas_id() === $file->getCollection()->get_sbas_id()) {
                    $excludedCollIds[] = $collection->get_coll_id();
                }
            }
        }

        $boolean = empty(\record_adapter::getRecordsByOriginalnameWithExcludedCollIds(
            $file->getCollection()->get_databox(), $file->getOriginalName(), $this->sensitive, 0, 1, $excludedCollIds
        ));

        return new Response($boolean, $this);
    }

    /**
     * @param Application $app
     * @param LazaretFile $file
     * @return \record_adapter[]
     */
    public static function listConflicts(Application $app, LazaretFile $file)
    {
        return \record_adapter::get_records_by_originalname(
            $file->getCollection($app)->get_databox(), $file->getOriginalName(), false, 0, 1000
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getReason(TranslatorInterface $translator)
    {
        return $translator->trans('same filename');
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(TranslatorInterface $translator)
    {
        return $translator->trans('A file with the same filename already exists in database');
    }
}
