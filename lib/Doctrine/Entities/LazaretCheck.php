<?php

namespace Entities;


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
     * @var Entities\LazaretFile
     */
    private $lazaretFile;

    /**
     * @var string $checkClassname
     */
    private $checkClassname;

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
     * Set lazaretFile
     *
     * @param  Entities\LazaretFile $lazaretFile
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
}
