<?php
/*
 *      Copyright 2011-2023 Daniel Kraus <bovender@bovender.de> and co-authors
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
namespace MediaWiki\Extension\PubmedParser;

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is an extension to MediaWiki and cannot be run standalone.' );
}

/**
 * Core class of the extension.
 * This class consists of several public, static functions that will be
 * called by MediaWiki. In order to facilitate unit testing, the actual
 * execution code was encapsulated in instance methods. The static methods
 * create an instance of this class and call upon the instance methods.
 */
class PubmedParserExtension {
	/** Default setup function.
	 * Associates the "pmid" magic word with the efPubmedParser_Render function.
	 */
	public static function setup( &$parser ) {
		$parser->setFunctionHook( 'pmid', '\MediaWiki\Extension\PubmedParser\PubmedParserExtension::render' );
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
	public static function onLoadExtensionSchemaUpdates( \DatabaseUpdater $updater ) {
		global $wgDBtype;
		$dbTag = $wgDBtype == 'postgres' ? '_Postgres' : '';
		$updater->addExtensionTable( 'Pubmed',
			__DIR__ . '/../db/PubmedParser' . $dbTag . '_Migration.sql', true );
		return true;
	}

	/**
	 * Static function that is hooked to the 'pmid' magic hook.
	 */
	public static function render( &$parser, $pmid, ...$param ) {
		if ( ! is_string( self::$authors ) ) self::loadMessages();
		$core = new Core( $pmid, $param );
		return $core->execute();
	}

	/*! Initializes the static class members so that we don't have to
	 * query the wiki database many times whenever a Pubmed citation is
	 * being parsed.
	 */
	private static function loadMessages() {
		self::$authors          = wfMessage( 'pubmedparser-authors' )->text();
		self::$authorsI         = wfMessage( 'pubmedparser-authorsi' )->text();
		self::$allAuthors       = wfMessage( 'pubmedparser-allauthors' )->text();
		self::$allAuthorsI      = wfMessage( 'pubmedparser-allauthorsi' )->text();
		self::$journal          = wfMessage( 'pubmedparser-journal' )->text();
		self::$journalCaps      = wfMessage( 'pubmedparser-journalcaps' )->text();
		self::$journalA         = wfMessage( 'pubmedparser-journala' )->text();
		self::$journalANoP      = wfMessage( 'pubmedparser-journalanop' )->text();
		self::$year             = wfMessage( 'pubmedparser-year' )->text();
		self::$volume           = wfMessage( 'pubmedparser-volume' )->text();
		self::$pages            = wfMessage( 'pubmedparser-pages' )->text();
		self::$firstPage        = wfMessage( 'pubmedparser-firstpage' )->text();
		self::$doi              = wfMessage( 'pubmedparser-doi' )->text();
		self::$pmc              = wfMessage( 'pubmedparser-pmc' )->text();
		self::$abstract         = wfMessage( 'pubmedparser-abstract' )->text();
		self::$title            = wfMessage( 'pubmedparser-title' )->text();
		self::$etAl             = wfMessage( 'pubmedparser-etal' )->text();
		self::$and              = wfMessage( 'pubmedparser-and' )->text();
		self::$initialPeriod    = wfMessage( 'pubmedparser-initialperiod' )->text();
		self::$initialSeparator = wfMessage( 'pubmedparser-initialseparator' )->text();
		self::$templateName     = wfMessage( 'pubmedparser-templatename' )->text();
		self::$reload           = wfMessage( 'pubmedparser-reload' )->text();
		self::$keywords         = wfMessage( 'pubmedparser-keywords' )->text();
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
	public static $pmc;
	public static $abstract;
	public static $title;
	public static $etAl;
	public static $and;
	public static $initialPeriod;
	public static $initialSeparator;
	public static $templateName;
	public static $reload;
	public static $keywords;
}
