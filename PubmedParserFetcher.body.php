<?php
/*
 *      \file PubmedParserFetcher.body.php
 *      
 *      Copyright 2011 Daniel Kraus <krada@gmx.net>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */
 
  if ( !defined( 'MEDIAWIKI' ) ) {
    die( 'Not an entry point.' );
  }

	/*! Replacement for ctype_digit, to properly handle (via return value false) nulls,
	 *  booleans, objects, resources, etc.
	 *  Taken from: http://www.php.net/manual/en/function.ctype-digit.php#87113
	 */
	function ctype_digit2 ($str) { 
		 return (is_string($str) || is_int($str) || is_float($str)) && 
				 preg_match('/^\d+\z/', $str); 
	}

	/*! The central PubmedParser class.
	 *  Fetches article information in XML format from Pubmed.gov, and
	 *  provides an interface to the article properties.
	 */
  class PubmedParserFetcher
	{
		/*! Constructor.
				$pmid is the unique Pubmed identifier; if given, the instance
				of the class will immediately retrieve the article information
				from pubmed. */
		function __construct( $pmid = 0, $reload = false )
		{
			/* As long as there we have not retrieved any valid data, the
			 * status needs to be set to something other than 'ok'; since
			 * it is the uninitialized $pmid that is the reason for not
			 * having any data, setting the status to PUBMEDPARSER_INVALIDPMID
			 * makes sense.
			 */
			$this->status = PUBMEDPARSER_INVALIDPMID;
			if ( $pmid ) {
				$this->lookUp( $pmid, $reload );
			}
		}

		/*! Retrieves article data for PMID from PubMed.
		 *  The function first attempts to locate a local copy of the XML
		 *  data for the requested PMID. If not found, it checks Pubmed online.
		 *	For this to work, the EUtilities application must be up and running
		 *	on the NCBI server.
		 *  \param $pmid [in] PMID (unique Pubmed identifier)
		 */
		function lookUp( $pmid = 0, $reload = false ) {
			global $wgPubmedParserCache; ///< reference to the global variable;

			$this->id = $pmid;

			// First, let's check if the PMID consists of digits only
			if ( !ctype_digit2( $pmid ) ) {
				$this->status = PUBMEDPARSER_INVALIDPMID;
				return;
			}
			
			$cacheFile = rtrim( $wgPubmedParserCache, '/' ) . '/' . $pmid;

			if ( $pmid )
			{
				// Attempt to load the information from cache, but only if the
				// $reload parameter is not true
				if ( is_readable( $cacheFile ) && !$reload ) {
					// a cache file was found; so use it.
					$this->medline = simplexml_load_file( $cacheFile );
				} else {
					// fetch the article information from PubMed in XML format
					// note: it's important to have retmode=xml, not rettype=xml!
					// rettype=xml returns an HTML page with formatted XML-like text;
					// retmode=xml returns raw XML.
					$this->medline = simplexml_load_file("http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&id=$pmid&retmode=xml");

					/* Check if Pubmed returned valid article data: If the PMID does not exist,
					 * Pubmed will deliver invalid XML ("<ERROR>Empty id list - nothing todo</ERROR>").
					 * In this case, the medline object will return false.
					 */
					if ( !$this->medline ) {
						$this->status = PUBMEDPARSER_NODATA;
						return;
					}

					/* Now that we have the data, let's attempt to store it locally
					 * in the cache.
					 */
					if ( is_writable( $wgPubmedParserCache ) ) {
						$this->medline->asXML( $cacheFile );
					}
				} // if file_exists
				
				if ( $this->medline ) {
					$this->article = $this->medline->PubmedArticle->MedlineCitation->Article;
				}

				// If Pubmed did not return data (e.g., the server is down or the
				// PMID does not exist), $this->article will not be set, and we
				// set the status code to PUBMEDPARSER_NODATA
				if ( !isset($this->article) ) {
					$this->status = PUBMEDPARSER_NODATA;
					unset ( $this->medline );
				} else {
					$this->status = PUBMEDPARSER_OK;
				}
				
			} // if ($pmid)
		}

		// *************************************************************
		// The following functions all return information on the article.

		/*! Returns an abbreviated list of the authors. If there are two
		 *  authors, it returns something like "Miller & Thomas"; with more
		 *  than two authors, it returns "Miller et al."
		 *  \param $useInitials [in] Boolean; if True, initials will be appended
		 */
		function authors( $useInitials = false ) {
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
				$numauthors = count( $this->article->AuthorList->Author );
				if ( $numauthors > 2 ) {
					$a = $this->authorName( $this->article->AuthorList->Author[0], $useInitials )
						. " " . wfMsg( 'pubmedparser-etal' );
				} elseif ( $numauthors = 2 ) {
					/* Sometimes, the number of authors is incorrectly given as 2,
					 * even though there is only 1 author (cf. PMID 19782018).
					 * As a workaround, if we are told there are 2 authors, we
					 * check that the presumptive second author's name is not null.
					 */
					$a = $this->authorName( $this->article->AuthorList->Author[1], $useInitials );
					if ( $a != '' ) {
						$a = $this->authorName( $this->article->AuthorList->Author[0], $useInitials )
							. ' ' . wfMsg( 'pubmedparser-and' ) . ' '	. $a;
					} else {
						$a = $this->authorName( $this->article->AuthorList->Author[0], $useInitials );
					}
				} else {
					$a = $this->authorName( $this->article->AuthorList->Author[0], $useInitials );
				}
				return $a;
			} // if ( $this->medline )
		}

		/*! Returns a list of all authors of this article.
		 *  \param $useInitials [in] Boolean; if True, initials will be appended
		 */
		function allAuthors( $useInitials = false )	{
			if ( $this->medline ) {
				$numauthors = count( $this->article->AuthorList->children() );

				if ( $numauthors > 1 ) {
					for ( $i=0; $i < $numauthors-1; $i++ ) {
						$a .= $this->authorName( $this->article->AuthorList->Author[$i], $useInitials ) . ', ';
					}
					$a = substr( $a, 0, strlen( $a )-2 ); // cut off the last ', '
					$a .= ' ' . wfMsg( 'pubmedparser-and' ) . ' '
							. $this->authorName( $this->article->AuthorList->Author[$i], $useInitials );
				} else { // only 1 author:
					$a = $this->authorName( $this->article->AuthorList->Author, $useInitials );
				}
				return $a;
			} // if ( $this->medline )
		}

		/// Returns the last name of the article's first author.
		function firstAuthor() {
			if ( $this->medline ) {
				return $this->article->AuthorList->Author[0]->LastName;
			}
		}

		/// Returns the title of the article. A trailing period will be removed.
		function title() {
			if ( $this->medline ) {
				return rtrim($this->article->ArticleTitle, '.');
			}
		}

		/// Returns the journal name as stored in Pubmed.
		function journal() {
			if ( $this->medline ) {
				return $this->article->Journal->Title;
			}
		}

		/// Returns the journal name with all words capitalized.
		function journalCaps() {
			if ( $this->medline ) {
				return ucwords( $this->article->Journal->Title );
			}
		}

		/// Returns the ISO abbreviation for the journal, with periods.
		function journalAbbrev() {
			if ( $this->medline ) {
				return $this->article->Journal->ISOAbbreviation;
			}
		}

		function journalAbbrevNoPeriods() {
			if ( $this->medline ) {
				$j = $this->article->Journal->ISOAbbreviation;
				$jwords = explode(' ', $j);
				foreach ( $jwords as &$word ) { // note the ampersand!
					$word = rtrim( $word, '.' );
				}
				$j = implode( ' ', $jwords );
				return $j;
			}
		}

		/// Returns the year the article was published.
		function year() {
			if ( $this->medline ) {
				return $this->medline->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year;
			}
		}

		/// Returns the volume of the journal that the article was published in.
		function volume() {
			if ( $this->medline ) {
				return $this->medline->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Volume;
			}
		}

		/// Returns the pagination of the article.
		function pages() {
			if ( $this->medline ) {
				return $this->medline->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn;
			}
		}

		/// Returns the first page of the article.
		function firstPage() {
			if ( $this->medline ) {
				$fp = explode('-', $this->medline->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn);
				return $fp[0];
			}
		}

		/// Returns the PMID of the article.
		function pmid()
		{
			return $this->id;
		}

		/*! Returns the digital object identifier (DOI).
		 *  Note that not all Pubmed citations have this information.
		 */
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

		/*! Returns the abstract of the article
		 *  Note the naming of the function: abstr(); we cannot use abstract
		 *  as it is a PHP keyword.
		 */
		function abstr() {
			foreach ( $this->medline->PubmedArticle->MedlineCitation->Article->Abstract->AbstractText as $p ) {
					// Abstract paragraphs may be preceded by a label.
					// The label is given as an XML parameter, e.g.:
					//  <AbstractText Label="BACKGROUND" NlmCategory="BACKGROUND"></AbstractText>
					$label = $p['Label'];
					if ( strlen( $label ) ) {
						$label .= ': ';
					}
					$a .= $label . $p . ' ';
				}
			return trim( $a );
		}
		

		/// Returns the citation data as formatted XML.
		function dumpData() {
			if ( $this->medline ) {
				return $this->medline->asXML();
			}
		}

		/// Returns the status of the object
		function statusCode() {
			return $this->status;
		}

		/// Returns the status message text
		function statusMsg() {
			$s = wfMsg( 'pubmedparser-error' ) . ': ';
			switch ( $this->status ) {
				case PUBMEDPARSER_OK:
					return $s . 'ok'; // no i18n since this message will never be shown to the user
				case PUBMEDPARSER_INVALIDPMID:
					return $s . wfMsg( 'pubmedparser-error-invalidpmid' );
				case PUBMEDPARSER_NODATA:
					return $s . wfMsg( 'pubmedparser-error-nodata' )
						. ' (PMID: [http://pubmed.gov/' . $this->id . ' ' . $this->id . '])';
				default:
					return 'Status code: #' . $this->status;
			}
		}

		/*! A private function that returns either the author's last name or
		 *  the "CollectiveName" is the author is a group.
		 *  \param   $author must be an instance of SimpleXMLElement
		 *  \returns         either the author's last name, or the group's collective name 
		 */
		private function authorName( $author, $useInitial = false ) {
			if ( $author instanceof SimpleXMLElement ) {
				$n = $author->LastName;
				if ( $n ) {
					if ( $useInitial ) {
						$i = $author->Initials;      // Initials is the concatenated string of initials
						$iarray = str_split($i, 1);  // get the single initials
						$i = implode( wfMsg( 'pubmedparser-initialperiod' ), $iarray)
							. wfMsg( 'pubmedparser-initialperiod' );
						$n = trim( $n . wfMsg( 'pubmedparser-initialseparator' ) . ' ' . $i, ' ');
						return $n;
					} else {
						return $n;
					}
				} else {
					return $author->CollectiveName;
				}
			}
		}

		// =======================
		// Private class elements
		private $id;			///< holds the PMID
		private $medline; ///< a SimpleXMLElement object that holds the Medline Data
		private $article; ///< $medline->PubmedArticle->MedlineCitation->Article
		private $status;  ///< holds status information (0 if everything is ok)
	}
