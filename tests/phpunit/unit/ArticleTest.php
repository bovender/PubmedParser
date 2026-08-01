<?php
/**
 * Unit tests for the PubmedParser extension.
 */
namespace MediaWiki\Extension\PubmedParser;

/**
 * @group extension-PubmedParser
 * @covers MediaWiki\Extension\PubmedParser\Article
 */
class ArticleTest extends \MediaWikiUnitTestCase {

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

	protected function setUp(): void {
		parent::setUp();
		// Article::authorName() reads these static fields directly; since this
		// is a unit test (no DB/message system available), set them explicitly
		// instead of relying on wfMessage().
		Extension::$and = '&';
		Extension::$etAl = 'et al.';
		Extension::$initialSeparator = ',';
		Extension::$initialPeriod = '.';
	}

	/**
	 * @dataProvider authorsProvider
	 */
	public function testAuthorsAbbreviatedList( $lastNames, $expectedAbbreviated, $expectedAll ) {
		$article = new Article( 1, $this->xmlWithAuthors( array_map(
			static function ( $name ) {
				return [ 'last' => $name ];
			},
			$lastNames
		) ) );
		$this->assertSame( $expectedAbbreviated, $article->authors(), 'Abbreviated author list incorrect' );
		$this->assertSame( $expectedAll, $article->allAuthors(), 'Full author list incorrect' );
	}

	public function authorsProvider() {
		return [
			'single author' => [ [ 'Smith' ], 'Smith', 'Smith' ],
			'two authors' => [ [ 'Smith', 'Jones' ], 'Smith & Jones', 'Smith & Jones' ],
			'three authors' => [ [ 'Smith', 'Jones', 'Lee' ], 'Smith et al.', 'Smith, Jones & Lee' ],
		];
	}

	/**
	 * Regression test: Article::authorName() used to join initials with the
	 * result of `Extension::$initialPeriod || ''` (a boolean OR that always
	 * yields true/false in PHP, unlike JavaScript's `||`), which corrupted
	 * the initials with a literal "1" instead of the configured separator.
	 */
	public function testAuthorsWithInitialsAreJoinedCorrectly() {
		Extension::$initialPeriod = '. ';
		$article = new Article( 1, $this->xmlWithAuthors( [
			[ 'last' => 'Smith', 'initials' => 'JD' ],
		] ) );
		$this->assertSame( 'Smith, J. D.', $article->authors( true ) );
	}

	/**
	 * Regression test: authorName() used to test
	 * `sizeof( $this->initials ) >= $index` (off-by-one), which allowed an
	 * out-of-bounds array access whenever an author had no <Initials> node
	 * while an earlier author did.
	 */
	public function testMissingInitialsForLaterAuthorDoesNotCrash() {
		$article = new Article( 1, $this->xmlWithAuthors( [
			[ 'last' => 'Smith', 'initials' => 'J' ],
			[ 'last' => 'Jones' ], // no <Initials> node
		] ) );
		$this->assertSame( 'Smith, J. & Jones', $article->allAuthors( true ) );
	}

	public function testCollectiveNameIsUsedWhenNoIndividualAuthors() {
		$article = new Article( 1, $this->xmlWithAuthors( [
			[ 'collective' => 'The Study Group' ],
		] ) );
		$this->assertSame( 'The Study Group', $article->authors() );
		$this->assertSame( 'The Study Group', $article->allAuthors() );
	}

	public function testAllKeywords() {
		$article = new Article( 1, $this->xmlWithAuthors( [ [ 'last' => 'Smith' ] ], [
			'keywords' => [ 'alpha', 'beta' ],
		] ) );
		$this->assertSame( 'alpha,beta', $article->allKeywords() );
	}

	public function testJournalCaps() {
		$article = new Article( 1, $this->xmlWithAuthors( [ [ 'last' => 'Smith' ] ], [
			'journal' => 'molecular cell biology',
		] ) );
		$this->assertSame( 'Molecular Cell Biology', $article->journalCaps() );
	}

	public function testFirstPageReturnsOnlyStartOfRange() {
		$article = new Article( 1, $this->xmlWithAuthors( [ [ 'last' => 'Smith' ] ], [
			'pages' => '726-8',
		] ) );
		$this->assertSame( '726', $article->firstPage() );
	}

	public function testJournalAbbrevNoPeriodsStripsPeriods() {
		$article = new Article( 1, $this->xmlWithAuthors( [ [ 'last' => 'Smith' ] ], [
			'journalAbbrev' => 'Mol. Cell.',
		] ) );
		$this->assertSame( 'Mol Cell', $article->journalAbbrevNoPeriods() );
	}

	public function testHasTitleIsFalseWhenArticleTitleIsMissing() {
		$article = new Article( 1, $this->xmlWithAuthors( [ [ 'last' => 'Smith' ] ], [
			'includeTitle' => false,
		] ) );
		$this->assertFalse( $article->hasTitle() );
	}

	/**
	 * Regression test: the constructor used to `catch ( Exception $e )`,
	 * which - because this class lives in the
	 * MediaWiki\Extension\PubmedParser namespace and no such Exception
	 * class exists there - could never actually catch anything. Malformed
	 * input must not cause a fatal error.
	 */
	public function testConstructorDoesNotThrowOnMalformedXml() {
		$article = new Article( 1, '<PubmedArticleSet><PubmedArticle>' );
		$this->assertFalse( $article->hasTitle() );
	}

	/** Builds a minimal Pubmed-style XML fixture with the given authors.
	 * @param array $authors List of ['last' => ..., 'initials' => ...] or ['collective' => ...]
	 * @param array $extra Optional overrides: title, journalAbbrev, pages, keywords, includeTitle
	 */
	private function xmlWithAuthors( array $authors, array $extra = [] ) {
		$authorXml = '';
		foreach ( $authors as $author ) {
			if ( isset( $author['collective'] ) ) {
				$authorXml .= '<Author><CollectiveName>' . $author['collective'] . '</CollectiveName></Author>';
			} else {
				$authorXml .= '<Author><LastName>' . $author['last'] . '</LastName>';
				if ( isset( $author['initials'] ) ) {
					$authorXml .= '<Initials>' . $author['initials'] . '</Initials>';
				}
				$authorXml .= '</Author>';
			}
		}

		$includeTitle = $extra['includeTitle'] ?? true;
		$title = $includeTitle ? '<ArticleTitle>' . ( $extra['title'] ?? 'Test title' ) . '</ArticleTitle>' : '';
		$journal = $extra['journal'] ?? 'Test Journal';
		$journalAbbrev = $extra['journalAbbrev'] ?? 'T. J.';
		$pages = $extra['pages'] ?? '10-20';
		$keywordsXml = '';
		foreach ( $extra['keywords'] ?? [] as $keyword ) {
			$keywordsXml .= "<Keyword>$keyword</Keyword>";
		}

		return <<<EOF
<PubmedArticleSet>
<PubmedArticle>
    <MedlineCitation>
        <Article>
            $title
            <Journal>
                <JournalIssue>
                    <PubDate><Year>2020</Year></PubDate>
                </JournalIssue>
                <Title>$journal</Title>
                <ISOAbbreviation>$journalAbbrev</ISOAbbreviation>
            </Journal>
            <Pagination><MedlinePgn>$pages</MedlinePgn></Pagination>
            <AuthorList>$authorXml</AuthorList>
            <KeywordList>$keywordsXml</KeywordList>
        </Article>
    </MedlineCitation>
</PubmedArticle>
</PubmedArticleSet>
EOF;
	}
}
