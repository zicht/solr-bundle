<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Service\ObjectStorage;
use Zicht\Bundle\SolrBundle\Service\ObjectStorageScopes;
use Zicht\Bundle\SolrBundle\Service\SolrManager;

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
    public function append($object, array &$data, ObjectStorage $container = null)
    {
        if (is_null($container)) {
            $class = new $this->class();
        } else {
            $class = $container->get($this->class, ObjectStorageScopes::SCOPE_MAPPING_MARSHALLER);
        }

        $data[$this->name] = $class->{$this->method}($object);
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return sprintf('%s::%s(<object>)', $this->class, $this->method);
    }
}