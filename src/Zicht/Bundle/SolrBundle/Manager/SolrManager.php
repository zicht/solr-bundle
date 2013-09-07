<?php
/**
 * @author    Gerard van Helden / Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Solarium\Core\Client\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Zicht\Bundle\SolrBundle\Builder\Indexer;

class SolrManager implements ContainerAwareInterface
{
	protected $mappings = array();
	/**
	 * @var null|\Solarium_Client
	 */
	protected $client = null;
	protected $container;
    protected $enabled = true;

	function __construct(Registry $doctrine, Client $client)
    {
        $this->doctrine = $doctrine;
		$this->em = $doctrine->getManager();
		$this->client = $client;
	}

	public function addMapping($entityName, $type, $builderClass)
    {
		$this->mappings[$entityName] = array(
            'class' => $entityName,
            'builder' => $builderClass,
            'type' => $type
        );
	}


    public function removeSolrIndex($entity)
    {
        if (!$this->enabled) {
            return;
        }
        if ($builder = $this->getBuilderForEntity(get_class($entity))) {
            $id = $builder->generateObjectIdentity($entity);

            $update = $this->client->createUpdate();
            $update->addDeleteQuery('id:' . $id);
            $update->addCommit();
        }
    }



	public function buildSolrIndex($entity)
    {
        if (!$this->enabled) {
            return false;
        }
        if ($builder = $this->getBuilderForEntity($entity))   {
			$indexer = $this->createIndexer();
			$indexer->addDocumentBuilder($builder);
			$indexer->flush();
            return true;
		}
        return false;
	}

    /**
     * @return Indexer
     */
    public function createIndexer()
    {
        return new Indexer($this->client);
    }

    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param $entity
     * @return mixed
     */
    public function getBuilderForEntity($entity)
    {
        $builder = null;
        if (is_string($entity)) {
            $entityClass = $entity;
        } else {
            $entityClass = get_class($entity);
        }

        if (isset($this->mappings[$entityClass])) {
            $mapping = $this->mappings[$entityClass];

            if (substr($mapping['builder'], 0, 1) === '@') {
                // service
                if ($entityClass === $mapping['class']) {
                    $service_name = ltrim($mapping['builder'], '@');
                    $builder      = $this->container->get($service_name);
                }
            } else {
                // classname
                $builder = new $mapping['builder']();
            }
        }
        if ($builder instanceof \Zicht\Bundle\SolrBundle\Manager\Doctrine\EntityBuilder) {
            $result = $builder->setEntity($entity);

            if (!is_bool($result)) {
                throw new \UnexpectedValueException(
                    "The setEntity method of " . get_class($builder) . " does not return a boolean, "
                    . "but it should indicate whether this entity is to be indexed"
                );
            }

            if (!$result) {
                $builder = null;
            }
        }
        return $builder;
    }


    public function setContainer(ContainerInterface $container = null) {
		$this->container = $container;
	}
}
