# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added|Changed|Deprecated|Removed|Fixed|Security
Nothing so far

## 3.4.4 - 2020-01-17
### Fixed
- Dsiplaying of synonyms in admin list

## 3.4.3 - 2019-10-28
### Added
- Synonym and stop words add commands
### Fixed
- Strip slash-r from synonym list
- Managed stop words and synonyms translation messages
- Solr bundle should require Sonata Admin now that it contains "admins"
- Cleaned up composer.json

## 3.4.2 - 2019-10-10
### Added
- Method on SearchFacade to try multiple fields for highlighting.

## 3.4.1 - 2019-09-20
### Fixed
- Symfony 2.x support of stopwords and synonyms management from the CMS itself (introduced in 3.4.0)

## 3.4.0 - 2019-09-20
### Added
Ability to manage stopwords and synonyms from the CMS itself.

## 3.3.0 - 2019-09-05
### Added
- Added Solr Entity Manager to better manage updates of entities and their related entities

## 3.2.x - 2017-10-04
### Added
- Added Extract Query, uses SOLR's capability to index Document Formats as PDF, DOC, etc.

## 3.1.x - 2016-09-19
### Added
- Added SolrDataCollector to show request send from debug toolbar

## 3.0.x - 2016-06-29
- Dependency on Solarium was removed
- New Client wrapper 
- Mapping is now done using an array containing all fields in stead of a Solarium Document object. This means that the API of the `mapDocument()` was changed such that it now must return an array containing key/value pairs for all fields. The document as the second parameter was dropped.
- `updateFieldValues()` in SolrManager was moved to `Client` and renamed to `update` 
- Several useful parameters were added to the `zicht:solr:select` 
- `zicht:solr:set` was renamed to `zicht:solr:update`
- `zicht:solr:purge` was renamed to `zicht:solr:delete`. For backwards compatibility, the `zicht:solr:purge` was kept as an alias for `zicht:solr:delete`

## 2.x and before - 2013-2018
No changelog was kept in these versions

