## [Marshaller](../../src/Zicht/Bundle/SolrBundle/Mapping/Marshaller.php)

This annotation can be used to bind an callback on an [field](Field.md) where the method gets the property value as argument or with the [fields](Fields.md) as an value and will get the entity ar argument.

You can tag your marshaller (with `zicht_solr.mapping.marshaller`) and they will automatically be added to the resolver.

#### Attributes

| Name | Default | Required | Description
--- | --- | --- | ---
value | null | true | The callback class or an array with the class and method


When no method name is given it will prefix the field name with `from` and create and create an camecase string from the property name. So for example when the field name is `an_field_name` it will try to call `fromAnFieldName`.

#### Example

1. [field](#field)
2. [fields](#fields)
2. [tag](#tag)

##### field

```

use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\SolrBundle\Mapping as Solr;

/**
 * @Solr\Document
 * @ORM\Entity
 */
class Page {

    ...
    /**
     * @Solr\Field
     * @Solr\Marshaller("ReverseMarshaller")
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;

...

```

And the `ReverseMarshaller` could be something like:

```
class ReverseMarshaller 
{
    public function fromTitle($title)
    {
        return strrev($title);
    }
}
```

##### fields

```

use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\SolrBundle\Mapping as Solr;

/**
 * @Solr\Document
 * @Solr\Fields({
 *      "reverse_title": @Solr\Marshaller({"ReverseMarshaller", "reversePage"})   
 * })
 * @ORM\Entity
 */
class Page {

    ...
    /**
     * @Solr\Field
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;

...

```

And the `ReverseMarshaller` could be something like:

```
class ReverseMarshaller 
{
    public function reversePage(Page $page)
    {
        return strrev($page->getTitle());
    }
}
```

##### tag

```
    <service id="marshaler.page_marshaler" class="PageMarshaller">
        <argument type="service" id="doctrine" />
        <tag name="zicht_solr.mapping.marshaller"/>
    </service>
```


```
class ReverseMarshaller 
{
    private $doctrine;
    
    public function __construct(Register $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    ...
}
```