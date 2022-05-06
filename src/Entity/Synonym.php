<?php
namespace Zicht\Bundle\SolrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Synonym
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
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $identifier;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $value;

    /**
     * Maps within the rest API to the "identifier" of the collection.
     *
     * @var string|null
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
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string|null $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
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