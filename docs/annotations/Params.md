## [Params](../../src/Zicht/Bundle/SolrBundle/Mapping/Params.php)

This will give the possibility to add extra params that will be added to the query. And use case could be to add a boost.

#### Example  

```
use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\SolrBundle\Mapping as Solr;

/**
 * @Solr\Document
 * @Solr\Params({
 *    "boost": 100
 * })
 * @ORM\Entity
 */
class Page {

...