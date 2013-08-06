<?php

namespace Entities;

use Alchemy\Phrasea\Application;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="Orders")
 * @ORM\Entity(repositoryClass="Repositories\OrderRepository")
 */
class Order
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
   private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $usr_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ssel_id;

    /**
     * @ORM\Column(type="string", length=2048)
     */
    private $order_usage;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $todo;

    /**
     * @ORM\Column(type="datetime")
     */
    private $deadline;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created_on;

    /**
     * @ORM\OneToMany(targetEntity="OrderElement", mappedBy="order", cascade={"ALL"})
     */
    private $elements;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->elements = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set usr_id
     *
     * @param integer $usrId
     * @return Order
     */
    public function setUsrId($usrId)
    {
        $this->usr_id = $usrId;

        return $this;
    }

    /**
     * Get usr_id
     *
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usr_id;
    }

    /**
     * Set ssel_id
     *
     * @param integer $sselId
     * @return Order
     */
    public function setSselId($sselId)
    {
        $this->ssel_id = $sselId;

        return $this;
    }

    /**
     * Get ssel_id
     *
     * @return integer
     */
    public function getSselId()
    {
        return $this->ssel_id;
    }

    /**
     * Set deadline
     *
     * @param \DateTime $deadline
     * @return Order
     */
    public function setDeadline($deadline)
    {
        $this->deadline = $deadline;

        return $this;
    }

    /**
     * Get deadline
     *
     * @return \DateTime
     */
    public function getDeadline()
    {
        return $this->deadline;
    }

    /**
     * Set created_on
     *
     * @param \DateTime $createdOn
     * @return Order
     */
    public function setCreatedOn($createdOn)
    {
        $this->created_on = $createdOn;

        return $this;
    }

    /**
     * Get created_on
     *
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->created_on;
    }

    /**
     * Add elements
     *
     * @param \Entities\OrderElement $elements
     * @return Order
     */
    public function addElement(\Entities\OrderElement $elements)
    {
        $this->elements[] = $elements;

        return $this;
    }

    /**
     * Remove elements
     *
     * @param \Entities\OrderElement $elements
     */
    public function removeElement(\Entities\OrderElement $elements)
    {
        $this->elements->removeElement($elements);
    }

    /**
     * Get elements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Returns the user matching to the usr_id property.
     *
     * @param Application $app
     *
     * @return User_Adapter
     */
    public function getUser(Application $app)
    {
        if ($this->getUsrId()) {
            return \User_Adapter::getInstance($this->getUsrId(), $app);
        }
    }

    /**
     * Set todo
     *
     * @param integer $todo
     * @return Order
     */
    public function setTodo($todo)
    {
        $this->todo = $todo;

        return $this;
    }

    /**
     * Get todo
     *
     * @return integer
     */
    public function getTodo()
    {
        return $this->todo;
    }

    /**
     * Returns the total number of elements.
     *
     * @return integer
     */
    public function getTotal()
    {
        return count($this->elements);
    }

    /**
     * Set order_usage
     *
     * @param string $orderUsage
     * @return Order
     */
    public function setOrderUsage($orderUsage)
    {
        $this->order_usage = $orderUsage;

        return $this;
    }

    /**
     * Get order_usage
     *
     * @return string
     */
    public function getOrderUsage()
    {
        return $this->order_usage;
    }
}