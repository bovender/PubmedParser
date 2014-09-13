<?php
/*
 *      \file PubmedParserFetcher.body.php
 *      
 *      Copyright 2011-2012 Daniel Kraus <krada@gmx.net>
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
		/** Constructor.
		 * @param int $pmid Pubmed identifier.
		 * @param boolean $reload Whether or not to force fetching data from Pubmed
		 */
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

		/** Retrieves article data for PMID from PubMed.
		 *  The function first attempts to locate a local copy of the XML
		 *  data for the requested PMID. If not found, it checks Pubmed online.
		 *	For this to work, the EUtilities application must be up and running
		 *	on the NCBI server.
		 *  @param $pmid [in] PMID (unique Pubmed identifier)
		 */
		function lookUp( $pmid = 0, $reload = false ) {
			$this->pmid = $pmid;

			// First, let's check if the PMID consists of digits only
			// This check is also important to prevent SQL injections!
			if ( !ctype_digit2( $pmid ) ) {
				$this->status = PUBMEDPARSER_INVALIDPMID;
				return;
			}

			$this->status = PUBMEDPARSER_OK;
			if ( ! $reload ) {
				$xml = self::fetchFromDb( $pmid );
				if ( $this->status != PUBMEDPARSER_OK ) {
					return;
				}
			};

			if ( is_null( $xml ) ) {
				// fetch the article information from PubMed in XML format
				// note: it's important to have retmode=xml, not rettype=xml!
				// rettype=xml returns an HTML page with formatted XML-like text;
				// retmode=xml returns raw XML.
				$url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&id=$pmid&retmode=xml";
				try {
					$xml = file_get_contents( $url );
				}
				catch (Exception $e) {
					try {
						$curl = curl_init( $url );
						curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
						$xml = curl_exec( $curl );
						curl_close( $curl );
					}
					catch (Exception $e) {
						$this->status = PUBMEDPARSER_CANNOTDOWNLOAD;
						return;
				   	}
				}

				/* Now that we have the data, let's attempt to store it locally
				 * in the cache.
				 */
				$this->storeInDb( $pmid, $xml );
			} // if no xml in database
			
			if ( $xml ) {
				$this->article = new PubmedArticle( $pmid, $xml );
				if ( $this->article->xml ) {
					$this->status = PUBMEDPARSER_INVALIDXML;
				}
			}
			else {
				$this->status = PUBMEDPARSER_NODATA;
			}
		}

		// *************************************************************
		// Database caching

		/// Fetches a PMID record from the wiki database, if available.
		/// @param $pmid Pubmed ID to look up. 
		/// @return XML string containing the Pubmed record, of null if the 
		/// record was not found.
		/// @note $pmid must be an integer to prevent SQL injections. Since 
		/// it is a scalar, specifying a typed parameter in the function 
		/// signature does not work. This is a private method that is called 
		/// by PubmedParserFetcher::lookUp() which ensures that PMIDs are 
		/// integers.
		private function fetchFromDb( $pmid ) {
			$dbr = wfGetDB( DB_SLAVE );
			$dbr->ignoreErrors( true );
			$res = $dbr->select( 
				'pubmed', 
				'xml', 
				'pmid = ' . $pmid, 
				__METHOD__
			);
			if ( $dbr->lastErrno() == 0 ) {
				if ( $res->numRows() == 1 ) {
					$xml = $res->fetchObject()->xml;
					return $xml;
				} else {
					return null;
				}
			} else {
				$this->status = PUBMEDPARSER_DBERROR;
			}
		}

		/// Stores the current PMID record in the wiki database.
		private function storeInDb( $pmid, $xml ) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->insert( 'pubmed', array(
				'pmid' => $pmid,
				'xml' => $xml
				)
			);
		}

		/// Returns the status of the object
		function statusCode() {
			return $this->status;
		}

		/// Returns the status message text
		function statusMsg() {
			$s = wfMessage( 'pubmedparser-error' )->text() . ': ';
			switch ( $this->status ) {
				case PUBMEDPARSER_INVALIDPMID:
					return $s . wfMessage( 'pubmedparser-error-invalidpmid' )->text();
				case PUBMEDPARSER_NODATA:
					return $s . wfMessage( 'pubmedparser-error-nodata' )->text()
						. ' (PMID: [http://pubmed.gov/' . $this->pmid . ' '
						. $this->pmid . '])';
				case PUBMEDPARSER_DBERROR:
					return $s . wfMessage( 'pubmedparser-error-dberror' )->text();
				case PUBMEDPARSER_OK:
					return $s . 'ok'; // no i18n since this message will never be shown to the user
				default:
					return 'Status code: #' . $this->status;
			}
		}

		public $pmid;     ///< holds the PMID
		public $article;  ///< instance of PubmedArticle
		private $status;  ///< holds status information (0 if everything is ok)
	}
// vim: sw=4:ts=4:sts=4:noet:comments^=\:///
