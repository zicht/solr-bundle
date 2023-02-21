<?php

namespace Zicht\Bundle\SolrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class StopWord
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=false)
     */
    private $value;

    /**
     * @var string|null Maps within the rest API to the "identifier" of the collection.
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $managed;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string|null $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getManaged()
    {
        return $this->managed;
    }

    /**
     * @param string|null $managed
     */
    public function setManaged($managed)
    {
        $this->managed = $managed;
    }
}
