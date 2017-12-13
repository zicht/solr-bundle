# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## 4.0.0
### Changed
- Started using Guzzle 6
- Changed the scope of `protected` to `public` for `AbstractDataMapper::mapDocument` and changed the scope of `protected` to `public` for `SolrManager::getMapper`.
This is a breaking change because it changes the public interface of this bundle. Changes are made to prevent duplication of logic and making it possible to re-use mappingresults on other places, not only in the Doctrine-subscriber. 

## 3.1
* Added SolrDataCollector to show request send from debug toolbar

## 3.0

* Dependency on Solarium was removed
* New Client wrapper 
* Mapping is now done using an array containing all fields in stead of a Solarium Document object. This means that the API of the `mapDocument()` was changed such that it now must return an array containing key/value pairs for all fields. The document as the second parameter was dropped.
* `updateFieldValues()` in SolrManager was moved to `Client` and renamed to `update` 
* Several useful parameters were added to the `zicht:solr:select` 
* `zicht:solr:set` was renamed to `zicht:solr:update`
* `zicht:solr:purge` was renamed to `zicht:solr:delete`. For backwards compatibility, the `zicht:solr:purge` was kept as an alias for `zicht:solr:delete`

