<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Solr\ObjectStorage;
use Zicht\Bundle\SolrBundle\Solr\SolrManager;

class StaticMethodMapper extends AbstractMapper
{
    /** @var string */
    protected $class;
    /** @var string */
    protected $method;

    /**
     * @param string $name
     * @param string $class
     * @param string $method
     */
    public function __construct($name, $class, $method)
    {
        parent::__construct($name);
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * @inheritdoc
     */
    public function append(ObjectStorage $container, $entity, array &$data)
    {
        $data[$this->name] = $container->get($this->class, SolrManager::SCOPE_MAPPING_MARSHALLER)->{$this->method}($entity);
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return sprintf('%s::%s(<object>)', $this->class, $this->method);
    }
}