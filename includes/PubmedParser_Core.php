<?php
/*
 *      \file PubmedParserFetcher.body.php
 *
 *      Copyright 2011-2019 Daniel Kraus <bovender@bovender.de>
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
namespace PubmedParser;

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is an extension to MediaWiki and cannot be run standalone.' );
}

/*! Replacement for ctype_digit, to properly handle (via return value false) nulls,
 *  booleans, objects, resources, etc.
 *  Taken from: http://www.php.net/manual/en/function.ctype-digit.php#87113
 */
function ctype_digit2 ($str) {
	 return (is_string($str) || is_int($str) || is_float($str)) &&
			 preg_match('/^\d+\z/', $str);
}

/*! The central class of the PubmedParser extension.
 *  Fetches article information in XML format from Pubmed.gov, and
 *  provides an interface to the article properties.
 */
class Core
{
	/**
	 * Constructor
	 * @param $pmid Pubmed identifier for the article; must be an integer.
	 * @note The optional parameters $param1 and $param2 may contain one of
	 * three parametrs: The name of a <ref name="..."></ref> structure, the name
	 * of the template to use to build the citation, or the keyword 'reload' to
	 * force reloading the Pubmed XML data from the internet. Note that to be
	 * able to distinguish a template name from a reference name, the template
	 * name MUST be prefixed with '#' (configurable in MediaWiki system
	 * messages).
	 */
	function __construct( $pmid = 0, $param1 = '', $param2 = '' ) {
		$this->status = PUBMEDPARSER_INVALIDPMID;
		$this->template = Extension::$templateName;
		$this->pmid = $pmid;
		$this->parseParam( $param1 );
		$this->parseParam( $param2 );
		$this->lookUp();
	}

	/**
	 * Parses a parameter and sets private fields accordingly.
	 */
	private function parseParam( $param ) {
		// Is the parameter meant to indicate the template name?
		if ( substr( $param, 0, 1 ) === PUBMEDPARSER_TEMPLATECHAR ) {
			$this->template = substr( $param, 1 );
		}
		elseif ( $param === Extension::$reload ) {
			$this->reload = true;
		}
		elseif ( strlen( $param ) > 0 ) {
			$this->reference = $param;
		}
	}

	/** Central function of this class.
	 * @return Array with parametrized PubMed article template.
	 */
	function execute() {
		if ( $this->statusCode() == PUBMEDPARSER_OK ) {
			$output = $this->buildTemplate( $this->article );
			if ( $this->reference ) {
				$output = "<ref name=\"{$this->reference}\">$output</ref>";
			}
		}
		else { // status not ok
			$output = '<span class="pubmedparser-error" '
				. 'style="font-size:150%; color:red; background-color:yellow;">'
				. $this->statusMsg() . '</span>';
		}

		// set noparse to false to enable full parsing of the output
		// (required for expansion of templates)
		return array( $output, 'noparse' => false );
	}

	/**
	 * Builds a wiki template with Pubmed data.
	 * @param PubmedArticle $article PubmedArticle object that contains the
	 * data.
	 * @return String containing a Wiki template with parametrized Pubmed data.
	 * @note Article is given as a parameter to facilitate unit testing.
	 */
	function buildTemplate( Article $article ) {
		return '{{' . $this->template . '|'
			. 'pmid=' . $article->pmid
			. '|' . Extension::$authors     . '=' . $article->authors()
			. '|' . Extension::$authorsI    . '=' . $article->authors( true )
			. '|' . Extension::$allAuthors  . '=' . $article->allAuthors()
			. '|' . Extension::$allAuthorsI . '=' . $article->allAuthors( true )
			. '|' . Extension::$journal     . '=' . $article->journal
			. '|' . Extension::$journalCaps . '=' . $article->journalCaps()
			. '|' . Extension::$journalA    . '=' . $article->journalAbbrev
			. '|' . Extension::$journalANoP . '=' . $article->journalAbbrevNoPeriods()
			. '|' . Extension::$year        . '=' . $article->year
			. '|' . Extension::$volume      . '=' . $article->volume
			. '|' . Extension::$pages       . '=' . $article->pages
			. '|' . Extension::$firstPage   . '=' . $article->firstPage()
			. '|' . Extension::$doi         . '=' . $article->doi
			. '|' . Extension::$pmc         . '=' . $article->pmc
			. '|' . Extension::$abstract    . '=' . $article->abstract
			. '|' . Extension::$title       . '=' . $article->title
			. '}}';
	}

	/** Retrieves article data for PMID from PubMed.
	 *  The function first attempts to locate a local copy of the XML
	 *  data for the requested PMID. If not found, it checks Pubmed online.
	 *	For this to work, the EUtilities application must be up and running
	 *	on the NCBI server.
	 */
	function lookUp() {
		// If a PMCID is given, attempt to convert it to a PMID.
		if (preg_match('/PMC\d+/i', $this->pmid)) {
			if (Helpers::Pmc2Pmid($this->pmid, $lookup_pmid)) {
				$this->pmid = $lookup_pmid;
			}
		}

		// First, let's check if the PMID consists of digits only
		// This check is also important to prevent SQL injections!
		if ( !ctype_digit2( $this->pmid ) ) {
			$this->status = PUBMEDPARSER_INVALIDPMID;
			return;
		}

		$this->status = PUBMEDPARSER_OK;
		$xml = null;
		if ( ! $this->reload ) {
			$xml = self::fetchFromDb( $this->pmid );
			if ( $this->status != PUBMEDPARSER_OK ) {
				return;
			}
		};

		if ( is_string( $xml ) ) {
			$found_in_cache = true;
		}
		else {
			$found_in_cache = false;

			// fetch the article information from PubMed in XML format
			// note: it's important to have retmode=xml, not rettype=xml!
			// rettype=xml returns an HTML page with formatted XML-like text;
			// retmode=xml returns raw XML.
			$url = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi'
				. "?db=pubmed&id={$this->pmid}&retmode=xml";
			if ( !Helpers::FetchRemote( $url, $xml ) ) {
				$this->status = PUBMEDPARSER_CANNOTDOWNLOAD;
				return;
			}
		} // if no xml in database

		if ( is_string( $xml ) ) {
			$this->article = new Article( $this->pmid, $xml );
			if ( $this->article->hasTitle() ) {
				if ( ! $found_in_cache ) {
					$this->storeInDb( $this->pmid, $xml );
				}
			}
			else {
				$this->status = PUBMEDPARSER_INVALIDXML;
			}
		}
		else {
			$this->status = PUBMEDPARSER_NODATA;
		}
	}

	// *************************************************************
	// Database caching

	/** Fetches a PMID record from the wiki database, if available.
	 * @param $pmid Pubmed ID to look up.
	 * @return XML string containing the Pubmed record, of null if the
	 * record was not found.
	 * @note $pmid must be an integer to prevent SQL injections. Since
	 * it is a scalar, specifying a typed parameter in the function
	 * signature does not work. This is a private method that is called
	 * by PubmedParserFetcher::lookUp() which ensures that PMIDs are
	 * integers.
	 */
	private function fetchFromDb( $pmid ) {
		$dbr = $this->getReadDb();
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
			}
		} else {
			$this->status = PUBMEDPARSER_DBERROR;
			return $null;
		}
	}

	/** Accessor for the current database read connection.
	 * The connection will be created if it does not exist yet.
	 * @return Database object as created by MediaWiki's wfGetDb().
	 */
	protected function getReadDb() {
		if ( !self::$_readDb ) {
			self::$_readDb = wfGetDB( DB_REPLICA );
		};
		return self::$_readDb;
	}

	/** Stores the current PMID record in the wiki database.
	 * @param integer $pmid Pubmed identifier
	 * @param string  $xml  Pubmed XML to store (will be escaped by MediaWiki)
	 * @return result of call to DatabaseBase::upsert
	 */
	private function storeInDb( $pmid, $xml ) {
		$dbw = $this->getWriteDb();
		$row = array(
			'pmid' => $pmid,
			'xml' => $xml
		);
		return $dbw->upsert( 'pubmed', $row, array( 'pmid' ), $row );
	}

	/** Accessor for the current database write connection.
	 * The connection will be created if it does not exist yet.
	 * @return Database object as created by MediaWiki's wfGetDb().
	 */
	protected function getWriteDb() {
		if ( !self::$_writeDb ) {
			self::$_writeDb = wfGetDB( DB_MASTER );
		};
		return self::$_writeDb;
	}

	/** Returns the status of the object
	 */
	function statusCode() {
		return $this->status;
	}

	/// Returns the status message text
	function statusMsg() {
		$s = wfMessage( 'pubmedparser-error' )->text() . ': ';
		switch ( $this->status ) {
			case PUBMEDPARSER_INVALIDPMID:
				return $s . wfMessage( 'pubmedparser-error-invalidpmid' )->text()
					. ' (PMID: [https://pubmed.gov/' . $this->pmid . ' '
					. $this->pmid . '])';
			case PUBMEDPARSER_NODATA:
				return $s . wfMessage( 'pubmedparser-error-nodata' )->text()
					. ' (PMID: [https://pubmed.gov/' . $this->pmid . ' '
					. $this->pmid . '])';
			case PUBMEDPARSER_DBERROR:
				return $s . wfMessage( 'pubmedparser-error-dberror' )->text();
			case PUBMEDPARSER_INVALIDXML:
				return $s . wfMessage( 'pubmedparser-error-invalidxml' )->text() .
					" ({$this->article->message})";
			case PUBMEDPARSER_OK:
				return $s . 'ok'; // no i18n since this message will never be shown to the user
			default:
				return 'Status code: #' . $this->status;
		}
	}

	public $article;  ///< instance of PubmedArticle
	private $pmid = 0;
	private $reload;    ///< If true, force reloading the XML from the web.
	private $reference; ///< Optional name of a footnote reference.
	private $status;  ///< holds status information (0 if everything is ok)
	private $template; ///< Name of the template to use.
	private static $_readDb = null;
	private static $_writeDb = null;
}
// vim: tw=78:ts=2:sw=2:noet:comments^=\:///
