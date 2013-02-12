<?php
/*
 *      \file PubmedParser.php
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

  $wgExtensionCredits['parserhook'][] = array(
    'path'           => __FILE__,
    'name'           => 'PubmedParser',
    'author'         => '[http://www.mediawiki.org/wiki/User:Bovender Daniel Kraus]', 
    'url'            => 'http://www.mediawiki.org/wiki/Extension:PubmedParser',
    'version'        => '1.0.0',
    'descriptionmsg' => 'pubmedparser-desc'
    );

  define( 'PUBMEDPARSER_OK',             1); ///< Status code: okay
  define( 'PUBMEDPARSER_INVALIDPMID',    2); ///< Status code: PMID is invalid
  define( 'PUBMEDPARSER_NODATA',         3); ///< Status code: Pubmed returned no data
  define( 'PUBMEDPARSER_CANNOTDOWNLOAD', 4); ///< Status code: cannot download XML data
  define( 'PUBMEDPARSER_RELOAD',      'RELOAD'); ///< Name of the 'reload' option (must be upper case!).
  
  $wgExtensionMessagesFiles['PubmedParser'] = dirname( __FILE__ ) . '/PubmedParser.i18n.php';

  $wgAutoloadClasses['PubmedParser'] = dirname(__FILE__) . '/PubmedParser.body.php';
  $wgAutoloadClasses['PubmedParserFetcher'] = dirname(__FILE__) . '/PubmedParserFetcher.body.php';

  // Define a setup function
  $wgHooks['ParserFirstCallInit'][] = 'PubmedParser::Setup';
  // Add a hook to initialise the magic word
  $wgHooks['LanguageGetMagic'][]    = 'PubmedParser::Magic';

  /*! Path to the cache folder
   *  To enable caching, make sure this path exists and is writable for
   *  the web server (chmod 777).
   */
  $wgPubmedParserCache = "$IP/cache/PubmedParser";


