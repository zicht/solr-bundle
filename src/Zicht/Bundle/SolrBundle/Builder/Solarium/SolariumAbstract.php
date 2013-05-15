<?php
/**
 * @author    Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Builder\Solarium;

abstract class SolariumAbstract implements SolariumInterface
{
    const FIELD_TYPE_TXT = '_txt';
    const FIELD_TYPE_INT = '_i';
    const FIELD_UNIQUE   = '';
    const FIELD_AS_IS    = '';
    const FIELD_DATETIME = '_dt';

    protected $files = array();
    protected $fields = array();

    /**
     * @var \Solarium_Client
     */
    protected $solr = null;

    final public function createDocument($update)
    {
        $document = $update->createDocument();

        if ($this->getBoost() > 0.0) {
            $document->setBoost($this->getBoost());
        }

        foreach ($this->fields as $field_name => $field_value) {
            $document->addField($field_name, $field_value);
        }

        return $document;
    }

    /**
     * Add a field to be indexed within SOLR
     *
     * @param string $field_name
     * @param mixed  $field_value
     */
    public function addField($field_name, $field_value)
    {
        if (!is_array($field_value)) {
            $this->fields[$field_name . self::FIELD_TYPE_TXT] = $field_value;
        } else {
            $this->fields[$field_name . $field_value['type']] = $this->processValue($field_value);
        }
    }

    /**
     * Add multiple fields at once
     *
     * @param $fields
     *
     * @return void
     * @internal param string $field_name
     * @internal param mixed $field_value
     */
    public function addFields($fields)
    {
        foreach ($fields as $field_name => $field_value) {
            $this->addField($field_name, $field_value);
        }
    }

    private function processValue($value)
    {
        $return = null;

        switch ($value['type']) {
            case self::FIELD_DATETIME:
                if (ctype_digit($value['value']) || is_int($value['value'])) {
                    $return = date('Y-m-d\TH:i:s\Z', $value['value']);
                } elseif ($value['value']  instanceof \DateTime) {
                    $return = $value['value']->format('Y-m-d\TH:i:s\Z');
                } else {
                    $return = $value['value'];
                }
                break;
            case self::FIELD_TYPE_INT:
                $return = (int)$value['value'];
                break;
            case self::FIELD_TYPE_TXT:
                $return = (string)$value['value'];
                break;
            default:
                $return = $value['value'];
                break;
        }

        return $return;
    }

    public function getBoost()
    {
        return 0.0;
    }
}