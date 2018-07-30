# Table of Contents

1. [Installing](#installing)
2. [Config](#config)
3. [Mapping](#mapping)
4. [Caching](#caching)
5. [Http](#http)
6. [Annotations](#annotations)
7. [Events](#)
7. [Events Listeners](#)

## Installing

To add this bundle to you project you can use composer: 

```
composer require zicht/solr-bundle:^5
```

And register this bundle in your kernel:

```
# app/AppKernel.php

    ...
    
    public function registerBundles()
    {
        return array(
            ...            
            new Zicht\Bundle\SolrBundle\ZichtSolrBundle(),
        );
    }
    
    ...
```

## Config

example config:

```
# Default configuration for "ZichtSolrBundle"
zicht_solr:
    mapper:
        # This should be a service id of a class that implements 
        # 'Psr\SimpleCache\CacheInterface'
        cache:                zicht_solr.cache.filesystem
        # This should be a service id of a class that implements 
        # "Doctrine\ORM\Mapping\NamingStrategy" and will be used 
        # for generating the solr field then no name is provided
        naming_strategy:      doctrine.orm.naming_strategy.underscore
    scheme:               http
    port:                 8983
    host:                 ~
    path:                 /solr
    core:                 ~
    uri:                  null


```  

The config now support one uri instead of defining all components separate. It still support the old config but will transform this to the new uri format to keep BC.

So the old way was to define an host like:

```
zicht_solr:
    port:                 8983
    host:                 example.com
    path:                 /solr
    core:                 example
``` 

This could be replaced by:

```
zicht_solr:
    uri:    http://example.com:8983/solr/example
```

This also make it possible to define https or user/password for when solr is behind and htaccess.


## Mapping

The [DocumentMapperMetadataFactory](../src/Zicht/Bundle/SolrBundle/Mapping/DocumentMapperMetadataFactory.php) uses the doctrine [MappingDriver](https://www.doctrine-project.org/api/orm/latest/Doctrine/ORM/Mapping/Driver/MappingDriver.html) to get all registered entities and checks for the existence of an [Document](../src/Zicht/Bundle/SolrBundle/Mapping/DocumentMapperMetadataFactory.php) class annotation. Those classes will be checked for annotations that will be used to create an [DocumentMapperMetadata](../src/Zicht/Bundle/SolrBundle/Mapping/DocumentMapperMetadata.php) which will be used to map an entity to an solr document. So you don`t need to register you entities as long the are managed by doctrine the will be picked up automatically.

To check all registered entities you van use the `zicht:solr:list-entities` command and to debug the mapping of an specific entity you can use the `zicht:solr:inspect-entity` which will disply all mappings and config. 

So to demonstrate we have an page entity:

```
/**
 * @Solr\Document
 * @ORM\Entity
 */
class Page {

    /**
     * @Solr\Field
     * @ORM\Column(type="string", nullable=true)
     */         
    private $body;
    
    /**
     * @Solr\Field
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;
    
    public function __construct($title, $body)
    {
        $this->title = $title;
        $this->body = $body;
    }
}

```


```
$page = new Page();
$page->setTitle('exmaple title');
$page->setBody('exmaple body...');

$data = []; 
$manager = $this->getContainer()->get('zicht_solr.manager');

foreach ($manager->getDocumentMapperMetadata($page)->getMapping() as $mapping) {
    $mapping->append(null, $entity, $data);
}

var_dump($data);

// will print:
// 
// array(2) {
//     'title' =>
//     string(13) "exmaple title"
//     'body' =>
//     string(15) "exmaple body..."
// }

```

To map classes not managed by doctrine you could use the `solr.metadata_post_build_entities_list` event to append classes and the `solr.metadata_load_document_mapper` event to dynamically add or alter mappings.

## Caching

This bundle has some cache implementations in the `Zicht/SimpleCache` namespace and could be replaced (when available) with the [symfony cache](https://symfony.com/doc/current/components/cache.html) as it both implements the [PSR-16](https://www.php-fig.org/psr/psr-16/).


Currently this bundle support [array cache](../src/Zicht/SimpleCache/ArrayCache.php) and [filesystem cache](../src/Zicht/SimpleCache/ArrayCache.php) where the filesystem cache is default for the DocumentMapperMetadataFactory.   
This bundle uses caching for the DocumentMapperMetadataFactory for caching the build entity lists and the entity mapper metadata. By default it will use the file system cache and defined as:

```
    <service id="zicht_solr.cache.filesystem" class="Zicht\SimpleCache\FilesystemCache" public="false">
        <argument>%kernel.cache_dir%/solr/</argument>
    </service>
```

To use the array cache (for development) you can set in the config cache to `zicht_solr.cache.array`

```
zicht_solr:
    mapper:
        cache: zicht_solr.cache.array
            
```

## Http

This bundle comes with an light http client (with no hard dependencies) what is build around the [PSR-7](https://www.php-fig.org/psr/psr-7) and uses the [fsockopen](http://php.net/manual/en/function.fsockopen.php) as transport layer but you could easily create handlers for curl etc.

At the moment there is no client/request-factory psr so whe using the [Zicht\Http\ClientInterface](../src/Zicht/Http/ClientInterface.php) and that is for now an hard dependency in the solr bundle and should be easly bridged to for example to guzzle. 

## Annotations

1. [Document](./annotations/Document.md)
2. [NoDocument](./annotations/NoDocument.md)
3. [Field](./annotations/Field.md) 
4. [Fields](./annotations/Fields.md) 
5. [IdGenerator](./annotations/IdGenerator.md) 
6. [Marshaller](./annotations/Marshaller.md) 
7. [Params](./annotations/Params.md) 

 
