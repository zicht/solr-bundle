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

}