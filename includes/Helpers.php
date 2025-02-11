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

use MediaWiki\MediaWikiServices;

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is an extension to MediaWiki and cannot be run standalone.' );
}

/*! Helper class for the PubmedParser extension. Contains static methods.
 */
class Helpers
{
	public static function FetchRemote($uri, &$result) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'PubmedParser' );
		$method = $config->get( 'PubmedParserRemoteFetchMethod' );

		try {
			switch ( $method ) {
				case 'curl':
					$curl = curl_init( $uri );
					curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
					$result = curl_exec( $curl );
					curl_close( $curl );
					break;
				default:
					$result = file_get_contents( $uri );
					break;
			}
			return true;
		} catch ( \Throwable $th ) {
			$result = $th;
			return false;
		}
	}

	/** Converts a PMC id to a PMID by invoking the Pubmed ID
	 * converter API.
	 * Important: $pmc is embedded directly in a remote call,
	 * so make sure it does not contain malicious injections
	 * _before_ calling this function.
	 * @param $pmc PubmedCentral ID to convert.
	 * @param &$pmid Pubmed ID corresponding to the PMC ID.
	 * @return True if successful, false if not.
	 */
	public static function Pmc2Pmid($pmc, &$pmid) {
		$result = false;
		$url = 'https://www.ncbi.nlm.nih.gov/pmc/utils/idconv/v1.0/' .
			"?tool=PubmedParser&email=bovender@bovender.de&ids=$pmc&format=json&versions=no";
		if (self::FetchRemote($url, $json)) {
			$data = json_decode($json, true);
			try {
				$pmid = $data['records'][0]['pmid'];
				$result = true;
			}
			catch (\Exception $e) { }
		}
		return $result;
	}
}

// vim: tw=78:ts=2:sw=2:noet:comments^=\:///
