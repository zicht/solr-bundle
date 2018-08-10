<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Service\ObjectStorage;

/**
 * Class StaticValueMapper
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
class StaticValueMapper extends AbstractMapper
{
    /** @var mixed */
    private $value;

    /**
     * StaticMapper constructor.
     * @param string $name
     * @param string $value
     */
    public function __construct($name, $value)
    {
        parent::__construct($name);
        $this->value = $value;
    }

    /**
     * @inheritdoc
     */
    public function append($object, array &$data, ObjectStorage $container = null)
    {
        $data[$this->name] = $this->value;
    }


    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->value;
    }
}
