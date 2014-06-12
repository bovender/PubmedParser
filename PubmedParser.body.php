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
		/*! \param $parser     The parser; ignored by the function.
		 *  \param $param1     The mandatory first parameter; expected to be a PMID.
		 *  \param $param2     The optional second parameter; can indicate a reference name.
		 *                     If given, the output will be surrounded by <ref name="$param2"></ref>
		 *                     tags (note that this requires the Cite extension).*/
		public static function Render( $parser, $param1 = '', $param2 = '', $param3 = '' ) {

			/* New for version 0.2.1: Implement the ability to force a reload
			 * of article data from Pubmed, e.g. if an online-first article 
			 * has been published in print, and the Pubmed entry has been
			 * updated.
			 * Users can add an optional 'reload' parameter:
			 *    {{#PMID:123456|reload}}
			 * or {{#PMID:123456|ReferenceName|reload}}
			 */

			$refName = $param2;
			if ( strtoupper( $refName ) == PUBMEDPARSER_RELOAD ) {
				$reload = true;
				$refName = $param3;
			} else {
				strtoupper( $param3 ) == PUBMEDPARSER_RELOAD ? $reload = true : $reload = false;
			}

			$pm = new PubmedParserFetcher( $param1, $reload );

			if ( $pm->statusCode() == PUBMEDPARSER_OK ) {
				$output = '{{' . wfMessage( 'pubmedparser-templatename' )->text() . '|'
					. 'pmid=' . $pm->pmid()
					. '|' . wfMessage( 'pubmedparser-authors' )->text()    . '=' . $pm->authors()
					. '|' . wfMessage( 'pubmedparser-authorsi' )->text()   . '=' . $pm->authors( true )
					. '|' . wfMessage( 'pubmedparser-allauthors' )->text() . '=' . $pm->allAuthors()
					. '|' . wfMessage( 'pubmedparser-allauthorsi' )->text(). '=' . $pm->allAuthors( true )
					. '|' . wfMessage( 'pubmedparser-title' )->text()      . '=' . $pm->title()
					. '|' . wfMessage( 'pubmedparser-journal' )->text()    . '=' . $pm->journal()
					. '|' . wfMessage( 'pubmedparser-journalcaps' )->text(). '=' . $pm->journalCaps()
					. '|' . wfMessage( 'pubmedparser-journala' )->text()   . '=' . $pm->journalAbbrev()
					. '|' . wfMessage( 'pubmedparser-journalanop' )->text(). '=' . $pm->journalAbbrevNoPeriods()
					. '|' . wfMessage( 'pubmedparser-year' )->text()       . '=' . $pm->year()
					. '|' . wfMessage( 'pubmedparser-volume' )->text()     . '=' . $pm->volume()
					. '|' . wfMessage( 'pubmedparser-pages' )->text()      . '=' . $pm->pages()
					. '|' . wfMessage( 'pubmedparser-firstpage' )->text()  . '=' . $pm->firstPage()
					. '|' . wfMessage( 'pubmedparser-doi' )->text()        . '=' . $pm->doi()
					. '|' . wfMessage( 'pubmedparser-abstract' )->text()   . '=' . $pm->abstr()
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
	}
