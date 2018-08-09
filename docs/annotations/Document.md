## [Document](../../src/Zicht/Bundle/SolrBundle/Mapping/Document.php)

This annotation is used for marking an managed entity as an solr document

#### Attributes

| Name | Default | Required | Description
--- | --- | --- | ---
repository | null | false | a repository class that implements SearchDocumentRepository
child_inheritance | false | false | if true then a instance of comparison is done instead of a strict comparison (===). So all child classes inherit the same annotations (except entities with the [NoDocument](NoDocument.md))
transformers | [DateTimeTransformer](../../src/Zicht/Bundle/SolrBundle/Mapping/DateTimeTransformer.php) for all doctrine date fields | false | Global defined transformers based on type, the array should be a transformer class as key and type match for value or an array with weight and type match.

When no transformers are defined the following will be set:

```
[ DateTimeTransformer::class => '/^(?:date(?:time(?:z)?)?|time)(?:_immutable)?$/' ]

```

That means that when you define you own this one will not be set! This is so you can set an empty transform when you do`nt want this.

#### Example

```

use Zicht\Bundle\SolrBundle\Mapping as Solr;

/**
 * @Solr\Document
 */
class Foo {

...

```

When you want the child classes to inherit the same mapping without the need to add annotations to them you could set the strict to false.



```

use Zicht\Bundle\SolrBundle\Mapping as Solr;

/**
 * @Solr\Document{child_inheritance: true}
 */
class Foo {

...


class Bar extends Foo {

...

```

And when you check the entity list you should see:


```
# php app/console zicht:solr:list-entities

● Foo
└── Bar


``` 