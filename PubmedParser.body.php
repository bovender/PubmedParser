<?php
/*
 *      \file PubmedParser.body.php
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
  class Pubmed
	{
		/*! Constructor.
				$pmid is the unique Pubmed identifier; if given, the instance
				of the class will immediately retrieve the article information
				from pubmed. */
		function __construct($pmid = '')
		{
			/* As long as there we have not retrieved any valid data, the
			 * status needs to be set to something other than 'ok'; since
			 * it is the uninitialized $pmid that is the reason for not
			 * having any data, setting the status to PUBMEDPARSER_INVALIDPMID
			 * makes sense.
			 */
			$this->status = PUBMEDPARSER_INVALIDPMID;
			if ( $pmid ) {
				$this->lookUp( $pmid );
			}
		}

		/*! Retrieves article data for PMID from PubMed.
		 *  The function first attempts to locate a local copy of the XML
		 *  data for the requested PMID. If not found, it checks Pubmed online.
		 *	For this to work, the EUtilities application must be up and running
		 *	on the NCBI server.
		 *  \param $pmid [in] PMID (unique Pubmed identifier)
		 */
		function lookUp( $pmid )
		{
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
				if ( is_readable( $cacheFile )) {
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
		 */
		function authors()
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
				$numauthors = count($this->article->AuthorList->Author);
				if ( $numauthors > 2 ) {
					$a = $this->article->AuthorList->Author[0]->LastName
						. " " . wfMsg( 'pubmedparser-etal' );
				} elseif ( $numauthors = 2 ) {
					$a = $this->article->AuthorList->Author[0]->LastName
						. " " . wfMsg( 'pubmedparser-and' ) . " "
						. $this->article->AuthorList->Author[1]->LastName;
				} else {
					$a = $this->article->AuthorList->Author[0];
				}
				return $a;
			} // if ( $this->medline )
		}

		/// Returns a list of all authors of this article.
		function allAuthors()
		{
			if ( $this->medline ) {
				$numauthors = count( $this->article->AuthorList->Author );
				for ( $i=0; $i < $numauthors-1; $i++ ) {
					$a .= $this->authorName( $this->article->AuthorList->Author[$i] ) . ", ";
				}
				$a .= wfMsg( 'pubmedparser-and' ) . " "
						. $this->authorName( $this->article->AuthorList->Author[$i] );
				return $a;
			} // if ( $this->medline )
		}

		/// Returns the last name of the article's first author.
		function firstAuthor()
		{
			if ( $this->medline ) {
				return $this->article->AuthorList->Author[0]->LastName;
			}
		}

		/// Returns the title of the article. A trailing period will be removed.
		function title()
		{
			if ( $this->medline ) {
				return rtrim($this->article->ArticleTitle, '.');
			}
		}

		/// Returns the journal name as stored in Pubmed.
		function journal()
		{
			if ( $this->medline ) {
				return $this->article->Journal->Title;
			}
		}

		/// Returns the ISO abbreviation for the journal, with periods.
		function journalAbbrev()
		{
			if ( $this->medline ) {
				return $this->article->Journal->ISOAbbreviation;
			}
		}

		/// Returns the year the article was published.
		function year()
		{
			if ( $this->medline ) {
				return $this->medline->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year;
			}
		}

		/// Returns the volume of the journal that the article was published in.
		function volume()
		{
			if ( $this->medline ) {
				return $this->medline->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Volume;
			}
		}

		/// Returns the pagination of the article.
		function pages()
		{
			if ( $this->medline ) {
				return $this->medline->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn;
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
					return $s . wfMsg( 'pubmedparser-error-nodata' ) . ' (PMID: ' . $this->id . ')';
				default:
					return 'Status code: #' . $this->status;
			}
		}

		/*! A private function that returns either the author's last name or
		 *  the "CollectiveName" is the author is a group.
		 *  \param   $author must be an instance of SimpleXMLElement
		 *  \returns         either the author's last name, or the group's collective name 
		 */
		private function authorName( $author ) {
			if ( $author instanceof SimpleXMLElement ) {
				$n = $author->LastName;
				if ( $n ) {
					return $n;
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
