<?php
  if ( !defined( 'MEDIAWIKI' ) ) {
    die( 'Not an entry point.' );
  }

  class Pubmed
	{
		/*! Constructor.
				$pmid is the unique Pubmed identifier; if given, the instance
				of the class will immediately retrieve the article information
				from pubmed. */
		function __construct($pmid = 0)
		{
			if ( $pmid ) {
				$this->lookUp( $pmid );
			}
		}

		/*! Retrieves article data for PMID from PubMed.
		 *  The function first attempts to locate a local copy of the XML
		 *  data for the requested PMID. If not found, it checks Pubmed online.
		 *	For this to work, the EUtilities application must be up and running
		 *	on the NCBI server.
		 */
		function lookUp( $pmid )
		{
			global $wgPubmedParserCache; // reference to the global variable;

			$this->id = $pmid;
			$cacheFile = rtrim( $wgPubmedParserCache, '/' ) . '/' . $pmid;

			if ( $pmid )
			{
				if ( is_readable( $cacheFile )) {
					// a cache file was found; so use it.
					$this->medline = simplexml_load_file( $cacheFile );
				} else {
					// fetch the article information from PubMed in XML format
					// note: it's important to have retmode=xml, not rettype=xml!
					// rettype=xml returns an HTML page with formatted XML-like text;
					// retmode=xml returns raw XML.
					$this->medline = simplexml_load_file("http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&id=$pmid&retmode=xml");

					// now that we have the data, let's attempt to store it locally
					// in the cache
					if ( is_writable( $wgPubmedParserCache )) {
						try {
							$this->medline->asXML( $cacheFile );
						}
						catch (Exception $e) {
						}
					}
				} // if file_exists
				
				if ( $this->medline ) {
					$this->article = $this->medline->PubmedArticle->MedlineCitation->Article;
				}
			} // if ($pmid)
		}

		/// The following functions all return information on the article.
		function authors()
		// returns an abbreviated list of the authors; if there are two
		// authors, it returns something like "Miller & Thomas"; with more
		// than two authors, it returns "Miller et al."
		{
			if ( $this->medline ) {
				// SimpleXMLElement::count() supposedly returns the number of
				// children of a node; however, this is not the behavior that I
				// experienced. The count() method returns the number of siblings
				// on my system! Therefore the notation AuthorList->Author->count().

				/* One additional problem: the count() method of the SimpleXMLElement
				 * does not work in PHP 5.2.x (as used by Lahno Webhosting); the
				 * workaround is given below. This was taken from the user comments
				 * on http://php.net/manual/en/simplexmlelement.count.php
				 */
				
				global $wgPubmedParserAnd, $wgPubmedParserEtAl; // need to declare global!
				$numauthors = count($this->article->AuthorList->Author);
				if ( $numauthors > 2 ) {
					$a = $this->article->AuthorList->Author[0]->LastName . " $wgPubmedParserEtAl";
				} elseif ( $numauthors = 2 ) {
					$a = $this->article->AuthorList->Author[0]->LastName
						. " $wgPubmedParserAnd "
						. $this->article->AuthorList->Author[1]->LastName;
				} else {
					$a = $this->article->AuthorList->Author[0];
				}
				return $a;
			} // if ( $this->medline )
		}

		function firstAuthor()
		{
			if ( $this->medline ) {
				return $this->article->AuthorList->Author[0]->LastName;
			}
		}

		function title()
		{
			// this function returns the article title as registered in
			// pubmed, with the trailing period removed; users can add
			// it in the MediaWiki template if desired.
			if ( $this->medline ) {
				return rtrim($this->article->ArticleTitle, '.');
			}
		}

		function journal()
		{
			if ( $this->medline ) {
				return $this->article->Journal->Title;
			}
		}

		function journalAbbrev()
		{
			if ( $this->medline ) {
				return $this->article->Journal->ISOAbbreviation;
			}
		}

		function year()
		{
			if ( $this->medline ) {
				return $this->medline->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year;
			}
		}

		function volume()
		{
			if ( $this->medline ) {
				return $this->medline->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Volume;
			}
		}

		function pages()
		{
			if ( $this->medline ) {
				return $this->medline->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn;
			}
		}

		function pmid()
		{
			return $this->id;
		}

		function doi()
		{
			if ( $this->medline ) {
				// look for the ArticleId node that has its IdType attribute
				// set to "doi". Note that not all pubmed entries have this
				// information.
				foreach ( $this->medline->PubmedArticle->PubmedData->ArticleIdList->ArticleId as $aid ) {
					if ( $aid['IdType'] == 'doi' ) {
						$doi = $aid;
					}
				}
				return $doi;
			}
		}

		function dumpData() {
			if ( $this->medline ) {
				return $this->medline->asXML();
			}
		}
		

		/// Private class elements
		private $id;			// holds the PMID
		private $medline; // a SimpleXMLElement object that holds the Medline Data
		private $article; // $medline->PubmedArticle->MedlineCitation->Article
	}
