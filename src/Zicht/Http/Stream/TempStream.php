<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Stream;

class TempStream extends ResourceStream
{
    /**
     * TempStream constructor.
     */
    public function __construct()
    {
        parent::__construct(fopen('php://temp', 'r+'));
    }

    /**
     * @param $resource
     * @return self
     */
    public static function from($resource)
    {
        $self = new static();
        $pos = ftell($resource);
        while (!feof($resource)) {
            $self->write(fread($resource, 1024));
        }
        $self->rewind();
        fseek($resource, $pos);
        return $self;
    }

}