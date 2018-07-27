## [IdGenerator](../../src/Zicht/Bundle/SolrBundle/Mapping/IdGenerator.php)

By default the the [IdGeneratorDefault](../../src/Zicht/Bundle/SolrBundle/Mapping/IdGeneratorDefault.php) is used to generate an document id but when you want to use an custom generator you can use this annotation to define an class (that implements [IdGeneratorInterface](../../src/Zicht/Bundle/SolrBundle/Mapping/IdGeneratorInterface.php)) as and id generator.

You can tag your id generator (with `zicht_solr.document.id_generator`) and they will automatically be added to the resolver.

#### Example  


An simple id generator that returns the entity id:

```
namespace Example\Bundle\ExampleBundle\Solr\Mapping

use Zicht\Bundle\SolrBundle\Mapping\PropertyValueTrait;

class EntityIdGenerator implements IdGeneratorInterface
{
    use PropertyValueTrait;

    public function generate($object)
    {
        return $this->resolveProperty($object, ...$meta->getIdField());
    }
}
```


To use this you can define it as follow:

```
use Zicht\Bundle\SolrBundle\Mapping as Solr;

/**
 * @Solr\Document
 * @Solr\IdGenerator("Example\Bundle\ExampleBundle\Solr\Mapping\EntityIdGenerator")
 */
class Foo {

...
```

When you have an generator with an dependency on other service you van register you generator and tag him:

```
    <service id="zicht_solr.default_document_id_generator" class="Zicht\Bundle\SolrBundle\Mapping\IdGeneratorDefault" public="false">
        <argument type="service" id="zicht_solr.mapper.document_metadata_factory"/>
        <tag name="zicht_solr.document.id_generator"/>
    </service>
```

So all entities with `Zicht\Bundle\SolrBundle\Mapping\IdGeneratorDefault` as generator will get that instance.