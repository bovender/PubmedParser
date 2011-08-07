<?php
/*
 *      \file PubmedParser.php
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

  define( 'PUBMEDPARSER_OK',          0); ///< Status code: okay
  define( 'PUBMEDPARSER_INVALIDPMID', 1); ///< Status code: PMID is invalid
  define( 'PUBMEDPARSER_NODATA',      2); ///< Status code: Pubmed returned no data
  
  $wgExtensionMessagesFiles['PubmedParser'] = dirname( __FILE__ ) . '/PubmedParser.i18n.php';

  $wgExtensionCredits['parserhook'][] = array(
    'path'           => __FILE__,
    'name'           => 'PubmedParser',
    'author'         => '[http://www.mediawiki.org/wiki/User:Bovender Daniel Kraus]', 
    'url'            => 'http://www.mediawiki.org/wiki/Extension:PubmedParser',
    'version'        => '0.1.2',
    'descriptionmsg' => 'pubmedparser-desc'
    );

  $wgAutoloadClasses['Pubmed'] = dirname(__FILE__) . '/PubmedParser.body.php';

  # Define a setup function
  $wgHooks['ParserFirstCallInit'][] = 'efPubmedParser_Setup';
  # Add a hook to initialise the magic word
  $wgHooks['LanguageGetMagic'][]    = 'efPubmedParser_Magic';

  /*! Path to the cache folder
   *  To enable caching, make sure this path exists and is writable for
   *  the web server.
   */
  $wgPubmedParserCache = "$IP/cache/PubmedParser";


  /// Default setup function.
  /// Associates the "pmid" magic word with the efPubmedParser_Render function.
  function efPubmedParser_Setup( &$parser ) {
    # Set a function hook associating the "pmid" magic word with our function
    $parser->setFunctionHook( 'pmid', 'efPubmedParser_Render' );
    return true;
  }

  /// Adds the magic word to the parser.
  function efPubmedParser_Magic( &$magicWords, $langCode ) {
    # The first array element is whether to be case sensitive, in this case (0) it is not case sensitive, 1 would be sensitive
    # All remaining elements are synonyms for our parser function
    $magicWords['pmid'] = array( 0, 'pmid' );
    # unless we return true, other parser functions extensions won't get loaded.
    return true;
  }

  /// Parser function.
  /*! \param $parser     The parser; ignored by the function.
   *  \param $param1     The mandatory first parameter; expected to be a PMID.
   *  \param $param2     The optional second parameter; can indicate a reference name.
   *                     If given, the output will be surrounded by <ref name="$param2"></ref>
   *                     tags (note that this requires the Cite extension).
   */
  function efPubmedParser_Render( $parser, $param1 = '', $param2 = '' ) {
    $pm = new Pubmed( $param1 );

    if ( $pm->statusCode() == PUBMEDPARSER_OK ) {
      $output = '{{' . wfMsg( 'pubmedparser-templatename' ) . '|'
        . 'pmid=' . $pm->pmid()
        . '|' . wfMsg( 'pubmedparser-authors' )    . '=' . $pm->authors()
        . '|' . wfMsg( 'pubmedparser-allauthors' ) . '=' . $pm->allAuthors()
        . '|' . wfMsg( 'pubmedparser-title' )      . '=' . $pm->title()
        . '|' . wfMsg( 'pubmedparser-journal' )    . '=' . $pm->journal()
        . '|' . wfMsg( 'pubmedparser-journala' )   . '=' . $pm->journalAbbrev()
        . '|' . wfMsg( 'pubmedparser-year' )       . '=' .$pm->year()
        . '|' . wfMsg( 'pubmedparser-volume' )     . '=' . $pm->volume()
        . '|' . wfMsg( 'pubmedparser-pages' )      . '=' . $pm->pages()
        . '|' . wfMsg( 'pubmedparser-doi' )        . '=' . $pm->doi()
        . '}}';

      if ( $param2 != '' ) {
        $output = "<ref name=\"$param2\">$output</ref>";
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
