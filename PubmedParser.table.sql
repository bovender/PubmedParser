CREATE TABLE IF NOT EXISTS pubmed (
	pmid INTEGER UNSIGNED NOT NULL PRIMARY KEY,
	xml TEXT NOT NULL
	)
	COMMENT "Caching table for PubmedParser extension";
