<?php
/**
 * Unit tests for the PubmedParser extension.
 * @group Database
 * @group bovender
 * @covers PubmedParser\Article
 */
namespace PubmedParser;

class ArticleTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent:setUp();
	}

	/**
	 * Main unit test for PubmedArticle properties.
	 * This test contains lots of assertions which is not considered good 
	 * style; however, placing the assertions in individual tests would have 
	 * involved creating PubmedArticle objects with the same sample data over 
	 * and over again. (I tried to use the '(at) depends' keyword of PHPUnit, 
	 * but when an object was returned from the producer, NULL would be 
	 * delivered to the consumer. Maybe there is a better way to do this.)
	 * @dataProvider xmlProvider
	 */
	public function testProperties( $pmid, $xml ) {
		$article = new Article( $pmid, $xml );
		$simple = simplexml_load_string( $xml );
		$simpleArticle = $simple->PubmedArticle->MedlineCitation->Article;
		$this->assertEquals( $article->pmid, $pmid, 'PMID incorrect' );

		// When asserting the article title, we need to add the dot back.
		$this->assertEquals( $simpleArticle->ArticleTitle, $article->title . '.',
	 		'Title incorrect' );
		$this->assertEquals( $simpleArticle->Journal->Title, $article->journal,
	 		'Journal name incorrect' );
		$this->assertEquals( $simpleArticle->Journal->ISOAbbreviation, 
			$article->journalAbbrev, 'Abbreviated journal name incorrect' );
		$this->assertEquals( $simpleArticle->Pagination->MedlinePgn, $article->pages,
	 		'Page numbers incorrect' );

		// Publication year may be stored in two differently named nodes
		$year = $simpleArticle->Journal->JournalIssue->PubDate->Year;
		if ( !$year ) {
			$year = $simpleArticle->Journal->JournalIssue->PubDate->MedlineDate;
			$this->assertGreaterThanOrEqual( 1, preg_match( '/\d{4}/', $year, $matches ),
		 		'Provided XML test data does not contain PubDate->Year or PubDate->MedlineDate' );
			$year = $matches[0];
		}
		$this->assertEquals( $year , $article->year, 'Year incorrect' );

		// Test the DOI; not all Pubmed records have this.
		foreach ( $simple->PubmedArticle->PubmedData->ArticleIdList->ArticleId as $aid ) {
			if ( $aid['IdType'] == 'doi' ) {
				$this->assertEquals( (string)$aid, $article->doi, 'DOI incorrect' );
			}
		}

		$this->assertEquals( (string)$simpleArticle->AuthorList->Author[0]->LastName,
			$article->authors[0] );
	}

	/** Reads all *.xml files in the test directory.
	 * @return An array of arrays of file content strings.
	 */
	public function xmlProvider() {
		$a = array();
		foreach( glob( __DIR__ . '/*.xml' ) as $fn ) {
			$a[] = array(
				basename( $fn, '.xml' ),
				file_get_contents( $fn )
			);
		}
		return $a;
	}
}
// vim: ts=2:sw=2:noet:comments^=\:///
