<?php

namespace Entities;


/**
 * Entities\LazaretAttribute
 */
class LazaretAttribute
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var string $value
     */
    private $value;

    /**
     * @var datetime $created
     */
    private $created;

    /**
     * @var datetime $updated
     */
    private $updated;

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
     * Set name
     *
     * @param  string           $name
     * @return LazaretAttribute
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set value
     *
     * @param  string           $value
     * @return LazaretAttribute
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set created
     *
     * @param  datetime         $created
     * @return LazaretAttribute
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return datetime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param  datetime         $updated
     * @return LazaretAttribute
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return datetime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set lazaretFile
     *
     * @param  Entities\LazaretFile $lazaretFile
     * @return LazaretAttribute
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