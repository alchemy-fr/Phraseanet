<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

use Alchemy\Phrasea\Application;

/**
 * LazaretCheck
 */
class LazaretCheck
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $checkClassname;

    /**
     * @var \Entities\LazaretFile
     */
    private $lazaretFile;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set checkClassname
     *
     * @param  string       $checkClassname
     * @return LazaretCheck
     */
    public function setCheckClassname($checkClassname)
    {
        $this->checkClassname = $checkClassname;

        return $this;
    }

    /**
     * Get checkClassname
     *
     * @return string
     */
    public function getCheckClassname()
    {
        return $this->checkClassname;
    }

    /**
     * Set lazaretFile
     *
     * @param  \Entities\LazaretFile $lazaretFile
     * @return LazaretCheck
     */
    public function setLazaretFile(LazaretFile $lazaretFile = null)
    {
        $this->lazaretFile = $lazaretFile;

        return $this;
    }

    /**
     * Get lazaretFile
     *
     * @return \Entities\LazaretFile
     */
    public function getLazaretFile()
    {
        return $this->lazaretFile;
    }

    /**
     * Get checker message
     *
     * @return string
     */
    public function getMessage()
    {
        $className = $this->getCheckClassname();

        if (method_exists($className, "getMessage")) {
            return $className::getMessage();
        } else {
            return '';
        }
    }

    /**
     * @return string  the reason why a record is in lazaret
     */
    public function getReason()
    {
        $className = $this->getCheckClassname();

        if (method_exists($className, "getReason")) {
            return $className::getReason();
        } else {
            return '';
        }
    }

    /**
     * @param Application $app
     * @return \record_adapter[]  the records conflicting with this check
     */
    public function listConflicts(Application $app)
    {
        $className = $this->getCheckClassname();

        if (method_exists($className, "listConflicts")) {
            return $className::listConflicts($app, $this->lazaretFile);
        } else {
            return [];
        }
    }
}
