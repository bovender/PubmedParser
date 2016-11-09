<?php
/*
 *      \file PubmedParser.helpers.php
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
namespace PubmedParser;
 
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is an extension to MediaWiki and cannot be run standalone.' );
}

/*! Helper class for the PubmedParser extension. Contains static methods.
 */
class Helpers
{
	public static function FetchRemote($uri, &$result) {
		try {
			$result = file_get_contents( $uri );
		}
		catch (Exception $e) {
			try {
				$curl = curl_init( $uri );
				curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
				$result = curl_exec( $curl );
				curl_close( $curl );
			}
			catch (Exception $e) {
				return false;
			}
		}
		return true;
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
			"?tool=PubmedParser&email=krada@gmx.net&ids=$pmc&format=json&versions=no";
		if (self::FetchRemote($url, $json)) {
			$data = json_decode($json, true);
			try {
				$pmid = $data['records'][0]['pmid'];	
				$result = true;
			}
			catch (Exception $e) { }
		}
		return $result;
	}
}

// vim: tw=78:ts=2:sw=2:noet:comments^=\:///
