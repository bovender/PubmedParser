<?php
/*
 *      \file PubmedParser.php
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

  $wgExtensionCredits['parserhook'][] = array(
    'path'           => __FILE__,
    'name'           => 'PubmedParser',
    'author'         => '[https://www.mediawiki.org/wiki/User:Bovender Daniel Kraus]', 
    'url'            => 'https://www.mediawiki.org/wiki/Extension:PubmedParser',
    'version'        => '3.1.1',
    'descriptionmsg' => 'pubmedparser-desc'
    );

  define( 'PUBMEDPARSER_OK',             0); ///< Status code: okay
  define( 'PUBMEDPARSER_INVALIDPMID',    2); ///< Status code: PMID is invalid
  define( 'PUBMEDPARSER_NODATA',         3); ///< Status code: Pubmed returned no data
  define( 'PUBMEDPARSER_CANNOTDOWNLOAD', 4); ///< Status code: cannot download XML data
  define( 'PUBMEDPARSER_DBERROR',        5);
  define( 'PUBMEDPARSER_INVALIDXML',     6); ///< Status code: Invalid XML data received
  define( 'PUBMEDPARSER_TEMPLATECHAR',   '#'); ///< Indicates template name parameter
  
  $wgExtensionMessagesFiles['PubmedParser'] = dirname( __FILE__ ) . '/PubmedParser.i18n.php';

  $wgAutoloadClasses['PubmedParser\Extension'] = dirname(__FILE__) . '/PubmedParser.Extension.php';
  $wgAutoloadClasses['PubmedParser\Core'] = dirname(__FILE__) . '/PubmedParser.Core.php';
  $wgAutoloadClasses['PubmedParser\Article'] = dirname(__FILE__) . '/PubmedParser.Article.php';
  $wgAutoloadClasses['PubmedParser\Helpers'] = dirname(__FILE__) . '/PubmedParser.Helpers.php';

  // Define a setup function
  $wgHooks['ParserFirstCallInit'][] = 'PubmedParser\Extension::setup';
  $wgHooks['LoadExtensionSchemaUpdates'][] = 'PubmedParser\Extension::createTable';
  $wgHooks['UnitTestsList'][] = 'PubmedParser\Extension::onUnitTestsList';
