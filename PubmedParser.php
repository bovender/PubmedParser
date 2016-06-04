<?php
/**
 *      \file PubmedParser.php
 *      
 *      Copyright 2011-2016 Daniel Kraus <bovender@bovender.de>
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
 
// Ensure that the script cannot be executed outside of MediaWiki
if ( !defined( 'MEDIAWIKI' ) ) {
  die( 'This is an extension to MediaWiki and cannot be run standalone.' );
}

// Display extension's information on "Special:Version"
$wgExtensionCredits['parserhook'][] = array(
  'path'           => __FILE__,
  'name'           => 'PubmedParser',
  'author'         => '[https://www.mediawiki.org/wiki/User:Bovender Daniel Kraus (bovender)]', 
  'url'            => 'https://www.mediawiki.org/wiki/Extension:PubmedParser',
  'version'        => '3.2.1',
  'descriptionmsg' => 'pubmedparser-desc',
  'license-name'   => 'GPL-2.0+'
);

// Define extension's status codes
define( 'PUBMEDPARSER_OK',             0); ///< Status code: okay
define( 'PUBMEDPARSER_INVALIDPMID',    2); ///< Status code: PMID is invalid
define( 'PUBMEDPARSER_NODATA',         3); ///< Status code: Pubmed returned no data
define( 'PUBMEDPARSER_CANNOTDOWNLOAD', 4); ///< Status code: cannot download XML data
define( 'PUBMEDPARSER_DBERROR',        5);
define( 'PUBMEDPARSER_INVALIDXML',     6); ///< Status code: Invalid XML data received
define( 'PUBMEDPARSER_TEMPLATECHAR',   '#'); ///< Indicates template name parameter

// Register extension messages
$wgExtensionMessagesFiles['PubmedParser'] = __DIR__ . '/PubmedParser.i18n.php';

// Load exension's classes
$wgAutoloadClasses['PubmedParser\Extension'] = __DIR__ . '/PubmedParser.Extension.php';
$wgAutoloadClasses['PubmedParser\Core'] = __DIR__ . '/PubmedParser.Core.php';
$wgAutoloadClasses['PubmedParser\Article'] = __DIR__ . '/PubmedParser.Article.php';
$wgAutoloadClasses['PubmedParser\Helpers'] = __DIR__ . '/PubmedParser.Helpers.php';

// Define a setup function
$wgHooks['ParserFirstCallInit'][] = 'PubmedParser\Extension::setup';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'PubmedParser\Extension::createTable';
$wgHooks['UnitTestsList'][] = 'PubmedParser\Extension::onUnitTestsList';
