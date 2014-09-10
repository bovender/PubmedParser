<?php
/*
 *      \file PubmedParser.body.php
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

	class PubmedParser {
		/// Default setup function.
		/// Associates the "pmid" magic word with the efPubmedParser_Render function.
		public static function Setup( &$parser ) {
			# Set a function hook associating the "pmid" magic word with our function
			$parser->setFunctionHook( 'PubmedParser', 'PubmedParser::Render' );
			PubmedParser::loadMessages();
			return true;
		}


		public static function onUnitTestsList( &$files ) {
			$files = array_merge( $files, glob( __DIR__ . '/tests/*Test.php' ) );
			return true;
		}

		/// Creates a Pubmed table in the Wiki database. This will hold XML 
		/// strings downloaded from pubmed.gov.
		public static function CreateTable( DatabaseUpdater $updater ) {
			$updater->addExtensionTable( 'Pubmed',
				dirname( __FILE__ ) . '/PubmedParser.table.sql', true );
			return true;
		}

		/// Parser function.
		/** @param $parser     The parser; ignored by the function.
		 *  @param $param1     The mandatory first parameter; expected to be a PMID.
		 *  @param $param2     The optional second parameter; can indicate a reference name.
		 *                     If given, the output will be surrounded by <ref name="$param2"></ref>
		 *                     tags (note that this requires the Cite extension).
		 */
		public static function Render( $parser, $param1 = '', $param2 = '', $param3 = '' ) {
			$refName = $param2;
			if ( strtoupper( $refName ) == PUBMEDPARSER_RELOAD ) {
				$reload = true;
				$refName = $param3;
			} else {
				strtoupper( $param3 ) == PUBMEDPARSER_RELOAD ? $reload = true : $reload = false;
			}

			$fetcher = new PubmedParserFetcher( $param1, $reload );

			if ( $fetcher->statusCode() == PUBMEDPARSER_OK ) {
				$article = $fetcher->article;
				$output = '{{' . PubmedParser::$templateName . '|'
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

				if ( $refName != '' ) {
					$output = "<ref name=\"$refName\">$output</ref>";
				}
			} else { // status not ok
				$output = '<span style="font-size:150%; color:red; background-color:yellow;">'
					. $pm->statusMsg()
					. '</span>';
			}

			// set noparse to false to enable full parsing of the output
			// (required for expansion of templates)
			return array( $output, 'noparse' => false );    
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
