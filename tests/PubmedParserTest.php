<?php
/**
 * Unit tests for the PubmedParserFetcher class.
 * @group Database
 * @covers PubmedParser
 */
class PubmedParserTest extends MediaWikiTestCase {
	/** An array of template fields that are used to build the reference. The 
		* keys of this associative array map to the public static properties of 
		* the PubmedParserFetcher class.
	 */
	private $templateFields = array(
		'title' => 'test title',
		'journal' => 'test journal',
		'journalA' => 'test journal abbr',
		'pages' => '10-20',
		'doi' => 'testdoi',
		'year' => '2014',
		'volume' => '1',
		'abstract' => 'test-abstract',
	);

	private $untestedFields = array(
		'authors',
		'authorsI',
		'allAuthors',
		'allAuthorsI',
		'journalCaps',
		'journalANoP',
		'firstPage',
	);

	/**
	 * Tests that invalid PMIDs produce errors.
	 * @covers PubmedParserFetcher::Render()
	 * @dataProvider invalidPmidProvider
	 */
	public function testRenderWithInvalidPmidOutputsError( $pmid ) {
		$mockParser = $this->getMock( 'Parser' );
		$result = PubmedParser::Render( $mockParser, $pmid );
		$this->assertRegExp('/span class="pubmedparser-error/',
		 	$result[0], 'No error was reported dispite invalid PMID');
	}

	/**
	 * @covers PubmedParser::buildTemplate
	 * @dataProvider pubmedXmlProvider
	 */
	public function testBuildTemplate( $xml ) {
		// Since wfMessage returns empty strings, prepare the messages.
		foreach ( $this->templateFields as $key => $value ) {
			// Two $$ to use the content of $key as variable name.
			PubmedParser::$$key = strtolower( $key );
		};
		foreach ( $this->untestedFields as $key ) {
			// Two $$ to use the content of $key as variable name.
			PubmedParser::$$key = strtolower( $key );
		};

		// Manually set the template name; this cannot be done with the 
		// $templateFields array since there is no corresponding value, and we 
		// loop over the entire array further below to assert correctness of the 
		// template transclusion that was built.
		PubmedParser::$templateName = "pubmed";

		$article = new PubmedArticle( 123, $xml );
		$template = PubmedParser::buildTemplate( $article );
		$year = PubmedParser::$year;
		foreach ( $this->templateFields as $field => $value ) {
			$s = strtolower( $field );
			$this->assertRegExp( "/$s=$value/", $template,
			 	"Template has incorrect $field parameter" );
		}
	}

	public function invalidPmidProvider() {
		return array(
			array( '' ),
			array( -3 )
		);
	}

	public function pubmedXmlProvider() {
		return array(
			array( <<<EOF
<!-- Note that this is not a complete XML structure, just
     a reduced set of nodes for testing purposes -->
<PubmedArticleSet>
<PubmedArticle>
    <MedlineCitation Owner="NLM" Status="MEDLINE">
        <PMID Version="1">3</PMID>
        <Article PubModel="Print">
            <Journal>
                <JournalIssue CitedMedium="Internet">
                    <Volume>{$this->templateFields['volume']}</Volume>
                    <PubDate>
                        <Year>{$this->templateFields['year']}</Year>
                    </PubDate>
                </JournalIssue>
                <Title>{$this->templateFields['journal']}</Title>
                <ISOAbbreviation>{$this->templateFields['journalA']}</ISOAbbreviation>
            </Journal>
            <ArticleTitle>{$this->templateFields['title']}</ArticleTitle>
            <Pagination>
                <MedlinePgn>{$this->templateFields['pages']}</MedlinePgn>
            </Pagination>
            <ELocationID EIdType="doi" ValidYN="Y">{$this->templateFields['doi']}_alternative_node</ELocationID>
            <Abstract>
                <AbstractText>{$this->templateFields['abstract']}</AbstractText>
            </Abstract>
            <AuthorList CompleteYN="Y">
                <Author ValidYN="Y">
                    <LastName>authorname</LastName>
                    <ForeName>first name</ForeName>
                    <Initials>fn</Initials>
                </Author>
            </AuthorList>
        </Article>
    </MedlineCitation>
    <PubmedData>
        <ArticleIdList>
            <ArticleId IdType="doi">{$this->templateFields['doi']}</ArticleId>
            <ArticleId IdType="pubmed">24717514</ArticleId>
        </ArticleIdList>
    </PubmedData>
</PubmedArticle>
</PubmedArticleSet>
EOF
		));
	}
}
// vim: ts=2:sw=2:noet:comments^=\:///
