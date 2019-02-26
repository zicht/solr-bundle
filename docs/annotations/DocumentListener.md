## [DocumentListener](../../src/Zicht/Bundle/SolrBundle/Mapping/DocumentListener.php)

This can be used to register pre/post mapping listeners similar as doctrine EntityListeners. 

The listener should implement either [PostMapInterface](../../src/Zicht/Bundle/SolrBundle/Mapping/PostMapInterface.php) or [PreMapInterface](../../src/Zicht/Bundle/SolrBundle/Mapping/PreMapInterface) or both when needed. 

When the service container is needed the service should be tagged with `zicht_solr.document.listener`. 

#### Example

the entity:

```
use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\SolrBundle\Mapping as Solr;

/**
 * @Solr\Document
 * @Solr\DocumentListener({
 *      "Zicht\Bundle\ExampleBundle\Solr\Listener\ExampleListener"
 * })
 * @ORM\Entity
 */
class NewsPage extends Page {

...
```

the listener:

```
class ExampleListener implements PostMapInterface
{
    /** @var TranslatorInterface */
    private $translator;


    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }


    /**
     * @param $object
     * @param array $map
     */
    public function postMap($object, array &$map) :void
    {
...
```

and the service definition:

```
        <service class="Zicht\Bundle\ExampleBundle\Solr\Listener\ExampleListener" id="zicht.solr_listener.example_listener">
            <argument id="translator" type="service"/>
            <tag name="zicht_solr.document.listener"/>
        </service>
```