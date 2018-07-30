## [Fields](../../src/Zicht/Bundle/SolrBundle/Mapping/Fields.php)

This can be used to add document field/values, the value should be an array where the keys are the document field name and the value the value of that document field. The value can als be an [Marshaller](Marshaller.md) to let an external service generate the value.

#### Example

1. [Static fields](#static-fields)
2. [Dynamic fields](#dynamic-fields)

##### Static fields

```
use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\SolrBundle\Mapping as Solr;

/**
 * @Solr\Document
 * @Solr\Fields({
 *    "page_type": "news_page"
 * })
 * @ORM\Entity
 */
class NewsPage extends Page {

...
```

##### Dynamic fields
  
```
use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\SolrBundle\Mapping as Solr;

/**
 * @Solr\Document
 * @Solr\Fields({
 *    "page_type": "news_page"
 *    "page_url": @Solr\Marshaller({"PageMarshaller", "getPageUrl"})
 * })
 * @ORM\Entity
 */
class NewsPage extends Page {

...
```  