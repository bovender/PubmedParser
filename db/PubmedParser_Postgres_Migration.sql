BEGIN;
CREATE TABLE IF NOT EXISTS /*_*/pubmed (
	pmid BIGINT NOT NULL PRIMARY KEY,
	xml TEXT NOT NULL
	);
COMMENT ON TABLE /*_*/pubmed IS "Caching table for PubmedParser extension";
COMMIT;
