# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## unreleased

### New feature

- It is now possible to configure a Pubmed `API` key when setting up the
  extension.

## [5.0.2][] - 2021-05-04

### Changed

- Extraction of the journal title is now more resilient; if no `ISOAbbreviation`
  field is present, `MedlineTA` may be used instead.

## [5.0.1][] - 2019-12-31

### Fixed

- Fix vesion number.

## [5.0.0][] - 2019-12-31

### Fixed

- Fix "Invalid server index index" error that occurred with MediaWiki version 1.34
  (issue #5). NOTE that this change now requires at least version 1.28 of MediaWiki
  (an obsolete version).

## [4.0.5][] - 2019-07-16

### Fixed

- Delay loading of Wiki messages in order to ensure compatibility with VisualEditor.

## [4.0.4][] - 2019-07-16

### Changed

- Caching is now only performed if Pubmed provided actual article data.

### Fixed

- Database migration now works with Postgres database as well.
- Fixed detection of empty data sets.

## [4.0.3][] - 2019-04-28

### Fixed

- Articles with single authors would not be formatted correctly.

## [4.0.2][] - 2017-03-19

### Fixed

- Prevent log flooding with warnings in certain situations.

## [4.0.1][] - 2016-11-09

### Changed

- Use only https to access the Pubmed API.

## [4.0.0][] - 2016-11-05

### Changed

- Compatibility with MediaWiki 1.25 and newer. The extension is not compatible with MediaWiki 1.24 and older.

### Fixed

- Prevent database errors on updating a citation with 'reload'.
- The caching table in the database is now created in a database transaction.
- The caching table in the database now uses the custom table prefix (you may want to manually rename an existing Pubmed table to include the prefix before running the new version of the extension.)
- Undefined variable errors.

## [3.2.1][] - 2016-06-04

### Fixed

- Do not crash on database access (due to call to ignoreErrors function that has been turned into a protected function in newer MediaWiki versions).

## [3.2.0][] - 2016-06-04

### Added

- Ability to give PMC ID as an alternative to PMID ('PMC1234567').
- Ability to output PubmedCentral ID.

### Fixed

- Prevent "instance of MySqlUpdater" error.

## [3.1.0][] - 2014-10-15

### Added

- The template name to use for the citation can now be given as a parameter (with a leading '#').

### Changed

- The extension requires PHP 5.3.0 or later.
- Use persistent database connections to increase performance.
- The name of the 'reload' option can be customized in the system messages.

## [3.0.0][] - 2014-09-10

[5.0.2]: https://github.com/bovender/PubmedParser/releases/tag/v5.0.2
[5.0.1]: https://github.com/bovender/PubmedParser/releases/tag/v5.0.1
[5.0.0]: https://github.com/bovender/PubmedParser/releases/tag/v5.0.0
[4.0.5]: https://github.com/bovender/PubmedParser/releases/tag/v4.0.5
[4.0.4]: https://github.com/bovender/PubmedParser/releases/tag/v4.0.4
[4.0.3]: https://github.com/bovender/PubmedParser/releases/tag/v4.0.3
[4.0.2]: https://github.com/bovender/PubmedParser/releases/tag/v4.0.2
[4.0.1]: https://github.com/bovender/PubmedParser/releases/tag/v4.0.1
[4.0.0]: https://github.com/bovender/PubmedParser/releases/tag/v4.0.0
[3.2.1]: https://github.com/bovender/PubmedParser/releases/tag/v3.2.1
[3.2.0]: https://github.com/bovender/PubmedParser/releases/tag/v3.2.0
[3.1.0]: https://github.com/bovender/PubmedParser/releases/tag/v3.1.0
[3.0.0]: https://github.com/bovender/PubmedParser/releases/tag/v3.0.0
