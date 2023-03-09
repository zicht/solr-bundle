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
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    private $value;

    /**
     * @var string Maps within the rest API to the "identifier" of the collection.
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $managed;

    public function __construct(string $managed, string $value)
    {
        $this->managed = $managed;
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     * @psalm-mutation-free
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     * @psalm-mutation-free
     */
    public function getManaged()
    {
        return $this->managed;
    }

    /**
     * @param string $managed
     */
    public function setManaged($managed)
    {
        $this->managed = $managed;
    }
}
