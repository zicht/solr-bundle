## [Field](../../src/Zicht/Bundle/SolrBundle/Mapping/Field.php)

This annotation is used to register an field and should be used on an property like you do with an `Doctrine\ORM\Mapping\Column` annotation.

#### Attributes

| Name | Default | Required | Description
--- | --- | --- | ---
name | null | false | this will be the property name in the document, if none is given it will use the defined naming strategy to create a mapping name.

#### Example  

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
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;

...

```