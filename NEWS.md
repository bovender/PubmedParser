Version 4.0.2 (2017-03-19)
------------------------------------------------------------------------

- Fix: Prevent log flooding with warnings in certain situations.

* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 


Version 4.0.1 (2016-11-09)
------------------------------------------------------------------------

- IMPROVEMENT: Use only https to access the Pubmed API.

* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 


Version 4.0.0 (2016-11-05)
------------------------------------------------------------------------

- FIX: Prevent database errors on updating a citation with 'reload'.
- FIX: The caching table in the database is now created in a database transaction.
- FIX: The caching table in the database now uses the custom table prefix (you may want to manually rename an existing Pubmed table to include the prefix before running the new version of the extension.)
- FIX: Undefined variable errors.
- NEW: Compatibility with MediaWiki 1.25 and newer. The extension is not compatible with MediaWiki 1.24 and older.

* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 


Version 3.2.1 (2016-06-04)
------------------------------------------------------------------------

- FIX: Do not crash on database access (due to call to ignoreErrors function that has been turned into a protected function in newer MediaWiki versions).

* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 


Version 3.2.0 (2016-06-04)
------------------------------------------------------------------------

- FIX: Prevent "instance of MySqlUpdater" error.
- NEW: Ability to give PMC ID as an alternative to PMID ('PMC1234567').
- NEW: Ability to output PubmedCentral ID.

* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 



Version 3.1.0 (2014-10-15)
------------------------------------------------------------------------

* Change: The extension requires PHP 5.3.0 or later.
* Change: Use persistent database connections to increase performance.
* Changed: The name of the 'reload' option can be customized in the system messages.
* New: The template name to use for the citation can now be given as a parameter (with a leading '#').

* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 



Version 3.0.0 (2014-09-10)
------------------------------------------------------------------------



* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
