<?php
/*
 *      \file PubmedParser.Article.php
 *      
 *      Copyright 2011-2017 Daniel Kraus <krada@gmx.net>
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

class Article
{
	public $authors = array();
	public $collectiveName;
	public $title;
	public $abstract;
	public $journal;
	public $journalAbbrev;
	public $year;
	public $volume;
	public $pages;
	public $doi;
	public $pmid;
	public $pmc;
	public $xml;
	public $message; ///< May hold an exception message.

	/** Constructs a new article object from a given Pubmed XML string.
	 */
	function __construct( $pmid, $xml )
	{
		try {
			$reader = new \XMLReader;
			$reader->xml( $xml );
			$this->pmid = $pmid;
			$this->xml = $xml;
			$this->parse( $reader );
		}
		catch ( Exception $e ) {
			$this->xml = false;
			$this->message = $e->getMessage();
		}
	}

	/** Parses Pubmed XML
	 */
	private function parse( \XMLReader $reader ) {
		while ( $reader->read() ) {
			if ( $reader->nodeType == \XMLReader::ELEMENT ) {
				switch ( $reader->name ) {
					case 'AuthorList':
						$this->parseAuthors( $reader );
						break;
					case 'ArticleTitle':
						$this->title = rtrim( $reader->readInnerXML(), '.' );
						break;
					case 'Title':
						$this->journal = $reader->readInnerXML();
						break;
					case 'ISOAbbreviation':
						$this->journalAbbrev = $reader->readInnerXML();
						break;
					case 'PubDate':
						$this->parseDate( $reader );
						break;
					case 'Volume':
						$this->volume = $reader->readInnerXML();
						break;
					case 'MedlinePgn':
						$this->pages = $reader->readInnerXML();
						break;
					case 'ArticleId':
						if ( $reader->getAttribute( 'IdType' ) === 'pmc' ) {
							$this->pmc = $reader->readInnerXML();
						}
						if ( $reader->getAttribute( 'IdType' ) === 'doi' ) {
							$this->doi = $reader->readInnerXML();
						}
						break;
					case 'AbstractText':
						$label = $reader->getAttribute( 'Label' );
						if ( $label ) {
							$label .= ': ';
						}
						$this->abstract .= $label . $reader->readInnerXML(). ' ';
						break;
				}
			}
		}
	}

	/** Parse the authors node and build an array of last names and initials. 
	 * If a collective name is encountered, save it.
	 */
	protected function parseAuthors( \XMLReader $reader ) {
		// Loop over the children of the AuthorList node and stop at the closing 
		// tag of the AuthorList node.
		while ( $reader->read() && ! ( $reader->name === 'AuthorList' ) ) {
			if ( $reader->nodeType == \XMLReader::ELEMENT ) {
				switch ( $reader->name ) {
					case 'LastName':
						$this->authors[] = $reader->readInnerXML();
						break;
					case 'Initials':
						$this->initials[] = $reader->readInnerXML();
						break;
					case 'CollectiveName':
						$this->collectiveName = $reader->readInnerXML();
				}
			}
		}
	}

	/** Parses the publication date (PubmedArticleSet -> PubmedArticle -> 
	 * MedlineCitation -> Article -> Journal -> JournalIssue -> PubDate).
	 * Sometimes, there will be no such node; in these cases, we use the 
	 * 'MedlineDate' node that is a child of 'JournalIssue'.
	 */
	protected function parseDate( \XMLReader $reader) {
		while ( $reader->read() && ! ( $reader->name === 'PubDate' ) ) {
			if ( $reader->nodeType == \XMLReader::ELEMENT ) {
				if ( $reader->name === 'Year' ) {
					$this->year = $reader->readInnerXML();
				}
				elseif ( $reader->name === 'MedlineDate' && !$this->year ) {
					// If we read the year of publication from the MedlineDate node, 
					// we need to extract a four-digit string from this node's value.
					if ( preg_match( '/\d{4}/', $reader->readInnerXML(), $matches ) ) {
						$this->year = $matches[0];
					}
				}
			}
		}	
	}

	/** Returns an abbreviated list of the authors. If there are two
	 *  authors, it returns something like "Miller & Thomas"; with more
	 *  than two authors, it returns "Miller et al."
	 *  @param $useInitials [in] Boolean; if True, initials will be appended
	 */
	function authors( $useInitials = false ) {
		$numAuthors = count( $this->authors );
		if ( $numAuthors > 0 ) {
			$a = $this->authorName( 0, $useInitials );
			if ( $numAuthors > 2 ) {
				$a .= " " . Extension::$etAl;
			} elseif ( $numAuthors == 2 ) {
				$a .= ' ' . Extension::$and .  ' '	. $this->authorName( 1, $useInitials );
			}
			return $a;
		} else {
			return $this->collectiveName;
		}
	} 

	/** Returns a list of all authors of this article.
	 *  @param $useInitials [in] Boolean; if True, initials will be appended
	 */
	function allAuthors( $useInitials = false )	{
		$numAuthors = count( $this->authors );
		$a = '';
		if ( $numAuthors > 1 ) {
			for ( $i=0; $i < $numAuthors-1; $i++ ) {
				$a .= $this->authorName( $i, $useInitials ) . ', ';
			}
			// Cut off the last ", ", add the "and" character or word, and append 
			// the last author's name.
			$a = substr( $a, 0, strlen( $a )-2 ) . ' ' . Extension::$and 
				. ' ' . $this->authorName( $i, $useInitials );
		} elseif ( $numAuthors == 1 ) {
			$a = $this->authorName( 0, $useInitials );
		} else {
			$a = $this->collectiveName;
		}
		return $a;
	}

	/// Returns the journal name with all words capitalized.
	function journalCaps() {
		return ucwords( $this->title );
	}

	/// Returns the first page of the article.
	function firstPage() {
		$fp = explode('-', $this->pages);
		return $fp[0];
	}

	function journalAbbrevNoPeriods() {
		$j = $this->journalAbbrev;
		$jwords = explode(' ', $j);
		foreach ( $jwords as &$word ) { // note the ampersand!
			$word = rtrim( $word, '.' );
		}
		$j = implode( ' ', $jwords );
		return $j;
	}

	/** A private function that returns either the author's last name or
	 * the "CollectiveName" is the author is a group.
	 * \param $index Index of the author
	 * \returns either the author's last name, or the group's collective name 
	 */
	private function authorName( $index, $useInitial = false ) {
		if ( $index < count( $this->authors ) ) {
			$author = $this->authors[$index];
			if ( $useInitial ) {
				$i = $this->initials[$index];
				$iarray = str_split($i, 1);
				$i = implode( Extension::$initialPeriod, $iarray)
					. Extension::$initialPeriod;
				// Spaces in the "Pubmedparser-initialperiod" system message must be 
				// encoded as "&nbsp;", lest they be removed by MediaWiki's text 
				// processing. In order to remove the trailing "&nbsp;" after 
				// concatenating all authors and initials, we use the trim function 
				// with " \xc2\xa0".
				$author = trim( $author . Extension::$initialSeparator
					. ' ' . $i, " \xc2\xa0");
			}
			return $author;
		} else {
			return $this->collectiveName;
		}
	}
}

// vim: ts=2:sw=2:noet:comments^=\:///
