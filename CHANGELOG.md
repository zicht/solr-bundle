All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added|Changed|Deprecated|Removed|Fixed|Security
- Nothing so far

## 7.1.0 - 2023-03-09
### Added
- Forward merge of v6.2.0: Reset PHP's memory peak usage in between reindexing of entities (PHP >= 8.2).
- Forward merge of v6.2.0: Duration time and byte size information in reindex command.
- Forward merge of v6.2.0: Added explicit max. memory of 50 Mb to the temp stream of the Solr update query,
  instead of relying on the (2 Mb) default.
- Forward merge of v6.2.0: Added PHP CS Fixer to lint the PHP code style.
### Changed
- Forward merge of v6.2.0: Psalm level 4 to 3 to increase Psalm's type checking strictness.
### Fixed
- Forward merge of v6.2.0: Improved texts and text colors of reindex command output.
- Fixed the PHP code style with newer PHP CS Fixer version than being used in v6.
- Forward merge of v6.2.0: Fixed the PHP code style with Zicht Standards PHP first and then with the newly added PHP CS Fixer.
- Forward merge of v6.2.0: Fixed call to Doctrine's deprecated Lifecycle event `getEntity()` method.
- Forward merge of v6.2.0: Fixed all type issues reported by Psalm after changing to level 3.
- Fixed type issues reported by Psalm here in release v7 with different dependencies.

## 7.0.1 - 2023-02-20
### Fixed
- Forward merge of 4.3.1
### Removed
- Logging queries in `ReindexCommand` with deprecated/removed EchoSQLLogger

## 7.0.0 - 2022-12-07
### Added
- Support for Symfony 5
- Support for Sonata 4
- Support for Guzzle 7
### Removed
- Support for Symfony 4
- Support for Sonata 3

## 6.2.0 - 2023-03-09
### Added
- Reset PHP's memory peak usage in between reindexing of entities (PHP >= 8.2).
- Duration time and byte size information in reindex command.
- Added explicit max. memory of 50 Mb to the temp stream of the Solr update query,
  instead of relying on the (2 Mb) default.
- Added PHP CS Fixer to lint the PHP code style.
### Changed
- Psalm level 4 to 3 to increase Psalm's type checking strictness.
### Fixed
- Improved texts and text colors of reindex command output.
- Fixed the PHP code style with Zicht Standards PHP first and then with the newly added PHP CS Fixer.
- Fixed call to Doctrine's deprecated Lifecycle event `getEntity()` method.
- Fixed all type issues reported by Psalm after changing to level 3.

## 6.1.1 - 2023-02-20
### Fixed
- Forward merge of 4.3.1

## 6.1.0 - 2022-11-28
### Added
- Forward merge of v3.5.0: Added DeleteIndexableRelationsInterface to be able to delete related entities in solr

## 6.0.0 - 2022-05-02
### Removed
- Removed support for PHP 7.1, 7.2 and 7.3
- Removed support for Symfony 3.4
- Removed unused `z2.yml` and `phpunit.xml.dist` files
### Fixed
- Added missing dependencies to composer.json of which this bundle is using classes of
- Changed use of deprecated Symfony FrameworkBundle Controller class into AbstractController class and
  inject the Solr Client into the constructor
- Pass root name to TreeBuilder constructor and call `getRootNode` instead of deprecated `->root()`
### Added
- Added autotagging of the `zicht_solr.mapper` tag for classes extending the `EntityMapper` class. So no
  _instanceof EntityMapper > tag zicht_solr.mapper_ like configuration is needed in the projects itself.
- Added a first PHPUnit test for DataMapper (added PHPUnit 9.5 as dev requirement and added
  PSR-4 dev autoloading for tests)
- Added GitHub Actions to test Q&A on incoming pull requests
- Added Vimeo Psalm (level 4) and fixed all errors
### Changed
- Overall cleanup of PHP docblocks and whitespace

## 5.1.1 - 2023-02-20
### Fixed
- Forward merge of 4.3.1

## 5.1.0 - 2022-11-28
### Added
- Forward merge of v3.5.0: Added DeleteIndexableRelationsInterface to be able to delete related entities in solr

## 5.0.8 - 2022-05-02
### Fixed
- Changed use of deprecated Sonata Admin class into AbstractAdmin class
- Fixed usage of deprecated Twig template colon path and changed into modern path
- Fixed select command
- Changed use of deprecated `->setHelps()` to setting `'help'` on the field itself for the
  Sonata Synonym admin

## 5.0.7 - 2022-04-12
### Fixed
- Point Solr Manager FQCN service aliases to the `zicht_solr.manager` service after having determined which Manager to use

## 5.0.6 - 2022-03-30
### Fixed
- Fixed use of old/deprecated Sensio Route and Doctrine Registry
### Changed
- Misc changes in routes/status controller

## 5.0.5 - 2021-10-25
### Fixed
- Added an override of generateObjectIdentity() to the EntityMapper to fix classnames when the entity is a Doctrine proxy

## 5.0.4 - 2021-05-17
### Fixed
- Changed left overs of Guzzle 5 into Guzzle 6 in Synonym and Stopword code

## 5.0.3 - 2021-03-01
### Added
- Added support for Psalm static analysis
### Fixed
- SearchFacade pager type (should be nullable)

## 5.0.2 - 2020-06-22
### Fixed
- `Select::setSort` allows for both `string` and `array` values.

## 5.0.1 - 2020-06-08
### Changed
- `SearchFacade::prepareFacetSet` internally uses already defined property `facetMinimumCount` to determine the
minimal count of results for facets to be shown.

## 5.0.0 - 2020-05-18
### Added
- Support for Symfony 4.x
### Removed
- Support for Symfony 2.x
- Support for PHP 5.6 and 7.0
- Support for Sonata Admin Bundle v2
### Changed
- Removed Zicht/Bundle/SolrBundle/ directory depth: moved all code up directly into src/

## 4.3.1 - 2023-02-20
### Fixed
- Race-condition in update/delete with same indexable relations

## 4.3.0 - 2022-11-28
### Added
- Forward merge of v3.5.0: Added DeleteIndexableRelationsInterface to be able to delete related entities in solr

## 4.2.8 - 2020-05-18
### Changed
- Switched from PSR-0 to PSR-4 autoloading (from v3.4.10)

## 4.2.7 - 2020-05-14
### Changed
- Allow solr-bundle to have either sonata major 2 or 3 (from v3.4.9)
### Fixed
- Fixes bug when receiving instances of a \DateTimeImmutable object (from v3.4.8).

## 4.2.6 - 2020-04-28
### Fixed
- Changed use of Guzzle base_url config parameter into the official base_uri parameter

## 4.2.5 - 2020-04-20
### Fixed
- Merged in v3.4.6: Catch NotFound exceptions when deleting Synonyms or stopwords and some minor cleanups

## 4.2.4 - 2020-04-09
### Fixed
- Merged in v3.4.6: Fixed checks on retrieving highlighted field for Unified Highlighter (Solr >=6.4)

## 4.2.3 - 2020-03-30
### Added
- Merged in v3.4.5: Ability to edit synonyms

## 4.2.2 - 2020-02-10
### Changed
- Improved reindex command info output

## 4.2.1 - 2020-01-17
### Fixed
- Merged v3.4.4 into v4.x:
  - Fixed: Displaying of synonyms in admin list
  - Fixed: Cleaning up synonyms on persist
  - Fixed: Add original word to sysnonyms to keep it in

## 4.2.0 - 2019-10-28
### Added
- Synonym and stop words add commands (through merging in v3.4.3)
- Method on SearchFacade to try multiple fields for highlighting. (through merging in v3.4.2)
### Fixed
- Strip slash-r from synonym list (through merging in v3.4.3)
- Managed stop words and synonyms translation messages (through merging in v3.4.3)
- Solr bundle should require Sonata Admin now that it contains "admins" (through merging in v3.4.3)
- Cleaned up composer.json (through merging in v3.4.3)

## 4.1.0 - 2019-09-20
### Added
- Ability to manage stopwords and synonyms from the CMS itself.
- Added Extract query (through merging in v3.2)
- Added Solr Entity Manager (through merging in v3.3)

## 4.0.2 - 2019-08-27
### Added
- Reindex all enities when no entity class is passed to the reindex command

## 4.0.1 - 2019-01-08
### Fixed
- Fix call to service

## 4.0.0 - 2018-12-11
### Changed
- Upgraded Guzzle from v5 to v6
### Removed
- v4 is based on v3.1, so no Extract query support and no Entity Manager

## 3.5.0 - 2020-06-08
### Added
- Added DeleteIndexableRelationsInterface to be able to delete related entities in solr

## 3.4.10 - 2020-05-18
### Changed
- Switched from PSR-0 to PSR-4 autoloading

## 3.4.9 - 2020-05-14
### Changed
- Allow solr-bundle to have either sonata major 2 or 3

## 3.4.8 - 2020-05-14
### Fixed
- Fixes bug when receiving instances of a \DateTimeImmutable object.

## 3.4.7 - 2020-04-20
### Fixed
- Catch NotFound exceptions when deleting Synonyms or stopwords
- Some minor cleanups

## 3.4.6 - 2020-04-09
### Fixed
- Fixed checks on retrieving highlighted field for Unified Highlighter (Solr >=6.4)

## 3.4.5 - 2020-03-30
### Added
- Ability to edit synonyms

## 3.4.4 - 2020-01-17
### Fixed
- Displaying of synonyms in admin list
- Cleaning up synonyms on persist
- Add original word to sysnonyms to keep it in

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

