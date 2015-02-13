<?php
/**
 * @author    Gerard van Helden / Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Manager;

use \Doctrine\Bundle\DoctrineBundle\Registry;
use \Solarium\Core\Client\Client;

class SolrManager
{
	/**
	 * @var Client
	 */
	protected $client = null;
    protected $enabled = true;

    /**
     * @var DataMapperInterface[]
     */
    protected $mappers = array();


    /**
     * @param Registry $doctrine1
     * @param Client $client
     */
    public function __construct(Client $client)
    {
		$this->client = $client;
        $this->mappers = array();
	}


    /**
     * @param $dataMapper
     */
    public function addMapper($dataMapper)
    {
        $this->mappers[]= $dataMapper;
    }


    /**
     * @param $entity
     * @return bool
     */
    public function update($entity)
    {
        if (!$this->enabled) {
            return false;
        }

        if ($mapper = $this->getMapper($entity)) {
            $mapper->update($this->client, $entity);
            return true;
        }
        return false;
    }


    /**
     * @param $entity
     * @return bool
     */
    public function delete($entity)
    {
        if (!$this->enabled) {
            return false;
        }

        if ($mapper = $this->getMapper($entity)) {
            $mapper->delete($this->client, $entity);
            return true;
        }
        return false;
    }

    /**
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }


    /**
     * @param mixed $entity
     * @return DataMapperInterface
     */
    protected function getMapper($entity)
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($entity)) {
                return $mapper;
            }
        }

        return null;
    }
}
