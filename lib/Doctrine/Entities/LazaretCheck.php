<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

use Alchemy\Phrasea\Application;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="LazaretChecks")
 * @ORM\Entity
 */
class LazaretCheck
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $checkClassname;

    /**
     * @ORM\ManyToOne(targetEntity="LazaretFile", inversedBy="checks", cascade={"persist"})
     * @ORM\JoinColumn(name="lazaret_file_id", referencedColumnName="id")
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
    public function setLazaretFile(\Entities\LazaretFile $lazaretFile = null)
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
}
