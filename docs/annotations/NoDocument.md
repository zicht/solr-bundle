## [NoDocument](../../src/Zicht/Bundle/SolrBundle/Mapping/NoDocument.php)

This can be used when an parent entity has disabled the strict modes to exclude the entity from the inheritance list.

#### Example  


```
use Zicht\Bundle\SolrBundle\Mapping as Solr;

/**
 * @Solr\Document{strict: false}
 */
class Foo {

...


class Bar extends Foo {

...

/**
 * @Solr\NoDocument
 */
class FooBar extends Foo {

...

```


And when you check the entity list you should see:


```
# php app/console zicht:solr:list-entities

● Foo
└── Bar


``` 
