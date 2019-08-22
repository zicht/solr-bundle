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
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private $value;

    /**
     * Maps within the rest API to the "identifier" of the collection.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $managed;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string|null $value
     */
    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getManaged(): ?string
    {
        return $this->managed;
    }

    /**
     * @param string|null $managed
     */
    public function setManaged(?string $managed): void
    {
        $this->managed = $managed;
    }
}