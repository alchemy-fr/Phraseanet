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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\File;
use Doctrine\ORM\EntityManager;

/**
 * Checks if a file with the same filename already exists in the destination databox
 */
class Filename extends AbstractChecker
{
    protected $sensitive;

    /**
     * Constructor
     *
     * @param boolean $sensitive Toggle case-sensitive mode, default : false
     */
    public function __construct(Application $app, array $options = array())
    {
        if ( ! isset($options['sensitive'])) {
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
        $boolean = ! count(\record_adapter::get_records_by_originalname(
                    $file->getCollection()->get_databox(), $file->getOriginalName(), $this->sensitive, 0, 1
                ));

        return new Response($boolean, $this);
    }

    /**
     * {@inheritdoc}
     */
    public static function getMessage()
    {
        return _('A file with the same filename already exists in database');
    }
}
