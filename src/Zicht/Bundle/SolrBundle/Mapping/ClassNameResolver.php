<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Doctrine\ORM\Proxy\Proxy;

class ClassNameResolver
{
    /** @var array  */
    private $aliases;

    /**
     * @param array $aliases
     */
    public function __construct(array $aliases = [])
    {
        $this->aliases = $aliases;
    }

    /**
     * @param mixed $className
     * @return string
     */
    public function getRealClassName($className) :string
    {
        if (is_object($className)) {
            $className = get_class($className);
        }
        if (false !== $pos = strpos($className, ':')) {
            list($alias, $name) = explode(':', $className, 2);
            if (isset($this->aliases[$alias])) {
                return $this->aliases[$alias] . '\\' . $name;
            }
        } elseif (false !== $pos = strrpos($className, '\\' . Proxy::MARKER . '\\')) {
            return substr($className, $pos + Proxy::MARKER_LENGTH + 2);
        }
        return $className;
    }
}
