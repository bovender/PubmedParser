<?php
/*
 *      Copyright 2011-2025 Daniel Kraus <bovender@bovender.de> and co-authors
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

use DatabaseUpdater;

class Hooks {
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
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		global $wgDBtype;
		switch ( $wgDBtype ) {
			case 'postgres':
				$migrationFile = 'PostgresMigration.sql';
				break;

			case 'sqlite':
				$migrationFile = 'SqliteMigration.sql';
				break;
	
			default:
				$migrationFile = 'MysqlMigration.sql';
				break;
		}
		$updater->addExtensionTable( 'Pubmed',
			__DIR__ . '/../db/' . $migrationFile, true );
		return true;
	}
}
