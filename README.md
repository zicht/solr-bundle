# `zicht/solr-bundle`

Provides a layer to index and search content in a SOLR instance.

## Features
* Easy bridge for syncing data from doctrine entities to SOLR
* Base class template for creating faceted search engines
* Command line interface for accessing the solr instance

### Indexing
TODO: write a small tutorial on how to setup indexing

#### Update
The `/update` endpoint of SOLR accepts a simple post of text fields to index a document. 
TODO: Add a explaining part on how to use the Update query
#### Extract
The `/update/extract` endpoint of SOLR accepts a simple post of text fields and additionally PDF, DOC, DOCX and other types. 

For deeper explanation refer to the documentation of SOLR covering this.
https://lucene.apache.org/solr/guide/6_6/uploading-data-with-solr-cell-using-apache-tika.html

In short. All the text fields covered by the `/update` endpoint are prefixed with `literal.` so `id` becomes `literal.id`.
The field to which you want SOLR to map the title and contents of the document should be declared. Our Extract query 
does default map the field `title` extracted from the document to `document_file_title` and the `content` field is 
mapped to `document_file_content`. These fields can be copied to the desired fields in the schema of your SOLR config.
The choice to seperate these fields is to give you control in terms of mapping and searching through these fields.

Use this `<copyField source="document_file_content" dest="content"/>` in your schema to define the `document_file_content`
copied to your default `content` field to say enable searching in `content` just like all other documents in a search.

### Mapping
TODO: Write documentation about mapping documents to SOLR

### Searching
TODO: Write documentation about using SOLR for searching purposes.

# Maintainer(s)
* Muhammed Akbulut <muhammed@zicht.nl>
