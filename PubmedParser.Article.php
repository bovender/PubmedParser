<?php
/*
 *      \file PubmedParser.Article.php
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

class PubmedArticle
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

	/** Constructs a new article object from a given Pubmed XML string.
	 */
	function __construct( $pmid, $xml )
	{
		$reader = XMLReader::xml( $xml );
		if ( $reader )
		{
			$this->pmid = $pmid;
			$this->xml = $xml;
			$this->parse( $reader );
		}
	}

	/** Parses Pubmed XML
	 */
	private function parse( XMLReader $reader ) {
		while ( $reader->read() ) {
			if ( $reader->nodeType == XMLReader::ELEMENT ) {
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
	protected function parseAuthors( XMLReader $reader ) {
		// Loop over the children of the AuthorList node and stop at the closing 
		// tag of the AuthorList node.
		while ( $reader->read() && ! ( $reader->name === 'AuthorList' ) ) {
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

	/** Parses the publication date (PubmedArticleSet -> PubmedArticle -> 
	 * MedlineCitation -> Article -> Journal -> JournalIssue -> PubDate).
	 * Sometimes, there will be no such node; in these cases, we use the 
	 * 'MedlineDate' node that is a child of 'JournalIssue'.
	 */
	protected function parseDate( XMLReader $reader) {
		while ( $reader->read() && ! ( $reader->name === 'PubDate' ) ) {
			if ( $reader->nodeType == XMLReader::ELEMENT ) {
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
			if ( $useInitial ) {
				$i = $author->Initials;      // Initials is the concatenated string of initials
				$iarray = str_split($i, 1);  // get the single initials
				$i = implode( wfMessage( 'pubmedparser-initialperiod' )->text(), $iarray)
					. wfMessage( 'pubmedparser-initialperiod' )->text();
				$n = trim( $n . wfMessage( 'pubmedparser-initialseparator' )->text() . ' ' . $i, ' ');
				return $n;
			} else {
				return $n;
			}
		} else {
			return $author->CollectiveName;
		}
	}
}

// vim: ts=2:sw=2:noet:comments^=\:///
