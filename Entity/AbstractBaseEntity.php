<?php

namespace CuteNinja\MemoriaBundle\Entity;

/**
 * Class AbstractBaseEntity
 *
 * @package CuteNinja\MemoriaBundle\Entity
 */
abstract class AbstractBaseEntity
{
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * @var string $status
     */
    protected $status = self::STATUS_ACTIVE;

    /**
     * @var \DateTime $creation
     */
    protected $creation;

    /**
     * @var \DateTime $lastUpdate
     */
    protected $lastUpdate;

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreation()
    {
        return $this->creation;
    }

    /**
     * @param \DateTime $creation
     *
     * @return $this
     */
    public function setCreation(\DateTime $creation)
    {
        $this->creation = $creation;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * @param \DateTime $lastUpdate
     *
     * @return $this
     */
    public function setLastUpdate(\DateTime $lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    public function logCreation()
    {
        $creationDate = new \DateTime();

        $this->setCreation($creationDate);
        $this->setLastUpdate($creationDate);
    }

    public function logUpdate()
    {
        $this->setLastUpdate(new \DateTime());
    }
}
