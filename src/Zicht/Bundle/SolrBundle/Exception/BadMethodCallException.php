<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Exception;

/**
 * Class BadMethodCallException
 *
 * @package Zicht\Bundle\SolrBundle\Exception
 */
class BadMethodCallException extends \BadMethodCallException implements SolrExceptionInterface
{
}