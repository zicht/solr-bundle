<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadata;

/**
 * Class MapEvent
 * @package Zicht\Bundle\SolrBundle\Event
 */
class MapEvent extends Event implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /** @var array  */
    private $data;
    /** @var DocumentMapperMetadata  */
    private $metadata;
    /** @var object  */
    private $entity;

    /**
     * @param array $data
     * @param object $entity
     * @param DocumentMapperMetadata $metadata
     */
    public function __construct(array $data, $entity, DocumentMapperMetadata $metadata)
    {
        $this->metadata = $metadata;
        $this->data = $data;
        $this->entity = $entity;
    }

    /** @inheritdoc */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /** @inheritdoc */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /** @inheritdoc */
    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /** @inheritdoc */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /** @inheritdoc */
    public function count()
    {
        return count($this->data);
    }

    /** @return DocumentMapperMetadata */
    public function getMetadata() :DocumentMapperMetadata
    {
        return $this->metadata;
    }

    /** @return array */
    public function getData() :array
    {
        return $this->data;
    }

    /** @inheritdoc */
    public function getIterator()
    {
        foreach ($this->data as $key => $value) {
            yield $key => $value;
        }
    }

    /** @return object */
    public function getEntity()
    {
        return $this->entity;
    }
}