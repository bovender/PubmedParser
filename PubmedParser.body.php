<?php
/*
 *      \file PubmedParser.body.php
 *      
 *      Copyright 2011-2014 Daniel Kraus <krada@gmx.net>
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

	/**
	 * Core class of the extension.
	 * This class consists of several public, static functions that will be 
	 * called by MediaWiki. In order to facilitate unit testing, the actual 
	 * execution code was encapsulated in instance methods. The static methods 
	 * create an instance of this class and call upon the instance methods.
	 */
	class PubmedParser {
		/** Default setup function.
		 * Associates the "pmid" magic word with the efPubmedParser_Render function.
		 */
		public static function setup( &$parser ) {
			# Set a function hook associating the "pmid" magic word with our function
			$parser->setFunctionHook( 'PubmedParser', 'PubmedParser::render' );
			PubmedParser::loadMessages();
			return true;
		}

		/**
		 * Helper function to enable MediaWiki to discover our unit tests.
		 */
		public static function onUnitTestsList( &$files ) {
			$files = array_merge( $files, glob( __DIR__ . '/tests/*Test.php' ) );
			return true;
		}

		/** Creates a Pubmed table in the Wiki database. This will hold XML 
		 * strings downloaded from pubmed.gov.
		 */
		public static function createTable( DatabaseUpdater $updater ) {
			$updater->addExtensionTable( 'Pubmed',
				dirname( __FILE__ ) . '/PubmedParser.table.sql', true );
			return true;
		}

		/**
		 * Static function that is hooked to the 'pmid' magic hook.
		 */
		public static function render( $parser, $param1 = '', $param2 = '', $param3 = '' ) {
			$pubmedParser = new PubmedParser( $param1, $param2, $param3 );
			return $pubmedParser->execute();
		}

		/**
		 * Constructor
		 * @param $pmid Pubmed identifier for the article; must be an integer.
		 * @param $params[]
		 */
		public function __construct( $pmid, $param1 = '', $param2 = '' ) {
			$this->pmid = $pmid;

			if ( ( strtoupper( $param1 ) === PUBMEDPARSER_RELOAD )  ||
					( strtoupper( $param2 ) === PUBMEDPARSER_RELOAD ) ) {
				$this->reload = true;
			}

			// Depending on whether the reload parameter was given, we look
			// for a named reference parameter in $param1 or $params.
			( $this->reload ) ? $this->reference = $param1 : $this->reference = $param2;
		}

		public function execute() {
			$fetcher = new PubmedParserFetcher( $this->pmid, $this->reload );
			if ( $fetcher->statusCode() == PUBMEDPARSER_OK ) {
				$output = PubmedParser::buildTemplate( $fetcher->article );
				if ( $refName != '' ) {
					$output = "<ref name=\"$refName\">output</ref>";
				}
			} else { // status not ok
				$output = '<span class="pubmedparser-error" style="font-size:150%; "'
					. 'color:red; background-color:yellow;">' . $fetcher->statusMsg() . '</span>';
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
		 */
		public static function buildTemplate( PubmedArticle $article ) {
			return '{{' . PubmedParser::$templateName . '|'
				. 'pmid=' . $article->pmid
				. '|' . PubmedParser::$authors     . '=' . $article->authors()
				. '|' . PubmedParser::$authorsI    . '=' . $article->authors( true )
				. '|' . PubmedParser::$allAuthors  . '=' . $article->allAuthors()
				. '|' . PubmedParser::$allAuthorsI . '=' . $article->allAuthors( true )
				. '|' . PubmedParser::$journal     . '=' . $article->journal
				. '|' . PubmedParser::$journalCaps . '=' . $article->journalCaps()
				. '|' . PubmedParser::$journalA    . '=' . $article->journalAbbrev
				. '|' . PubmedParser::$journalANoP . '=' . $article->journalAbbrevNoPeriods()
				. '|' . PubmedParser::$year        . '=' . $article->year
				. '|' . PubmedParser::$volume      . '=' . $article->volume
				. '|' . PubmedParser::$pages       . '=' . $article->pages
				. '|' . PubmedParser::$firstPage   . '=' . $article->firstPage()
				. '|' . PubmedParser::$doi         . '=' . $article->doi
				. '|' . PubmedParser::$abstract    . '=' . $article->abstract
				. '|' . PubmedParser::$title       . '=' . $article->title
				. '}}';
		}

		/*! Initializes the static class members so that we don't have to
		 * query the wiki database many times whenever a Pubmed citation is 
		 * being parsed.
		 */
		private static function loadMessages() {
			PubmedParser::$authors          = wfMessage( 'pubmedparser-authors' )->text();
			PubmedParser::$authorsI         = wfMessage( 'pubmedparser-authorsi' )->text();
			PubmedParser::$allAuthors       = wfMessage( 'pubmedparser-allauthors' )->text();
			PubmedParser::$allAuthorsI      = wfMessage( 'pubmedparser-allauthorsi' )->text();
			PubmedParser::$journal          = wfMessage( 'pubmedparser-journal' )->text();
			PubmedParser::$journalCaps      = wfMessage( 'pubmedparser-journalcaps' )->text();
			PubmedParser::$journalA         = wfMessage( 'pubmedparser-journala' )->text();
			PubmedParser::$journalANoP      = wfMessage( 'pubmedparser-journalanop' )->text();
			PubmedParser::$year             = wfMessage( 'pubmedparser-year' )->text();
			PubmedParser::$volume           = wfMessage( 'pubmedparser-volume' )->text();
			PubmedParser::$pages            = wfMessage( 'pubmedparser-pages' )->text();
			PubmedParser::$firstPage        = wfMessage( 'pubmedparser-firstpage' )->text();
			PubmedParser::$doi              = wfMessage( 'pubmedparser-doi' )->text();
			PubmedParser::$abstract         = wfMessage( 'pubmedparser-abstract' )->text();
			PubmedParser::$title            = wfMessage( 'pubmedparser-title' )->text();
			PubmedParser::$etAl             = wfMessage( 'pubmedparser-etal' )->text();
			PubmedParser::$and              = wfMessage( 'pubmedparser-and' )->text();
			PubmedParser::$initialPeriod    = wfMessage( 'pubmedparser-initialperiod' )->text();
			PubmedParser::$initialSeparator = wfMessage( 'pubmedparser-initialseparator' )->text();
			PubmedParser::$templateName     = wfMessage( 'pubmedparser-templatename' )->text();
		}

		private $reload;    ///< If true, force reloading the XML from the web.
		private $reference; ///< Optional name of a footnote reference.

		public static $authors;
    public static $authorsI;
    public static $allAuthors;
    public static $allAuthorsI;
    public static $journal;
    public static $journalCaps;
    public static $journalA;
    public static $journalANoP;
    public static $year;
    public static $volume;
    public static $pages;
    public static $firstPage;
    public static $doi;
    public static $abstract;
    public static $title;
    public static $etAl;
    public static $and;
		public static $initialPeriod;
		public static $initialSeparator;
		public static $templateName;
	}
// vim: ts=2:sw=2:noet:comments^=\:///
