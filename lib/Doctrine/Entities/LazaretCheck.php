<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entities\LazaretCheck
 */
class LazaretCheck
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $check
     */
    private $check;

    /**
     * @var Entities\LazaretFile
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
     * Set check
     *
     * @param string $check
     * @return LazaretCheck
     */
    public function setCheck($check)
    {
        $this->check = $check;
        return $this;
    }

    /**
     * Get check
     *
     * @return string 
     */
    public function getCheck()
    {
        return $this->check;
    }

    /**
     * Set lazaretFile
     *
     * @param Entities\LazaretFile $lazaretFile
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
     * @return Entities\LazaretFile 
     */
    public function getLazaretFile()
    {
        return $this->lazaretFile;
    }
}