# 3.2 #
* Added Extract, uses SOLR's capability to index Rich Document Formats, PDF, DOC, etc.

# 3.1 #
* Added SolrDataCollector to show request send from debug toolbar

# 3.0 #

* Dependency on Solarium was removed
* New Client wrapper 
* Mapping is now done using an array containing all fields in stead of a Solarium Document object. This means that the API of the `mapDocument()` was changed such that it now must return an array containing key/value pairs for all fields. The document as the second parameter was dropped.
* `updateFieldValues()` in SolrManager was moved to `Client` and renamed to `update` 
* Several useful parameters were added to the `zicht:solr:select` 
* `zicht:solr:set` was renamed to `zicht:solr:update`
* `zicht:solr:purge` was renamed to `zicht:solr:delete`. For backwards compatibility, the `zicht:solr:purge` was kept as an alias for `zicht:solr:delete`

