<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Message;

/**
 * Trait HeaderTrait
 *
 * @package Zicht\Http\Message
 */
trait HeaderTrait
{
    /** @var array  */
    private $header;

    /**
     * @param string $name
     * @return \Generator|string[]
     */
    private function getHeadersByName($name)
    {
        foreach ($this->header as $header => $values) {
            if (0 === strcasecmp($header, $name)) {
                yield $header => $values;
            }
        }
    }

    /**
     * @{inheritDoc}
     */
    public function getHeaders()
    {
        return $this->header;
    }

    /**
     * @{inheritDoc}
     */
    public function hasHeader($name)
    {
        foreach (array_keys($this->header) as $header) {
            if (0 === strcasecmp($header, $name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @{inheritDoc}
     */
    public function getHeader($name)
    {
        $values = [];

        foreach ($this->getHeadersByName($name) as $data) {
            $values = array_merge($values, $data);
        }

        return $values;
    }

    /**
     * @{inheritDoc}
     */
    public function getHeaderLine($name)
    {
        return implode(',', $this->getHeader($name));
    }

    /**
     * @{inheritDoc}
     */
    public function withHeader($name, $value)
    {
        $instance = $this->withoutHeader($name);
        $instance->header[$name] = [$value];
        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function withAddedHeader($name, $value)
    {
        $instance = clone $this;

        if (isset($instance->header[$name])) {
            $instance->header[$name][] = $value;
        } else {
            $instance->header[$name] = [$value];
        }

        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function withoutHeader($name)
    {
        $instance = clone $this;
        foreach (array_keys($instance->header) as $header) {
            if (0 === strcasecmp($header, $name)) {
                unset($instance->header[$header]);
            }
        }
        return $instance;
    }
}