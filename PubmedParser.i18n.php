<?php
/*! \file PubmedParser.i18n.php
 */
 
$messages = array();

$messages['en'] = array(
	'pubmedparser-desc' => 'Adds a parser function #PMID to look up articles in pubmed.gov by ID.',
	'pubmedparser-error' => 'PubmedParser error',
	'pubmedparser-error-nodata' => 'Pubmed did not return article data, please check the PMID or try again later.',
	'pubmedparser-error-invalidpmid' => 'Invalid PMID, please check.',

	// the following are the default template name and parameter names;
	// no need to localize them, but we define them as messages to allow
	// Wiki administrators to change the names to suit their needs
	'pubmedparser-templatename' => 'pubmed', // the name of the template to use
	'pubmedparser-authors'      => 'authors',
	'pubmedparser-authorsi'     => 'authorsi',
	'pubmedparser-allauthors'   => 'allauthors',
	'pubmedparser-allauthorsi'  => 'allauthorsi',
	'pubmedparser-journal'      => 'journal',
	'pubmedparser-journalcaps'  => 'journalcaps',
	'pubmedparser-journala'     => 'journala',
	'pubmedparser-volume'       => 'volume',
	'pubmedparser-pages'        => 'pages',
	'pubmedparser-firstpage'    => 'firstpage',
	'pubmedparser-year'         => 'year',
	'pubmedparser-doi'          => 'doi',
	'pubmedparser-title'        => 'title',
	'pubmedparser-and'          => '&',
	'pubmedparser-etal'         => '\'\'et al.\'\'',
	'pubmedparser-initialseparator' => '',  // how to separate initials from last name
	'pubmedparser-initialperiod'=> '',      // character to place after an initial
);

$messages['de'] = array(
	'pubmedparser-desc' => 'Fügt das Parser-Schlüsselwort #PMID hinzu, mit dem Artikel anhand ihrer PMID-Nummer in Pubmed nachgeschlagen werden können.',
	'pubmedparser-error' => 'PubmedParser-Fehler',
	'pubmedparser-error-nodata' => 'Pubmed hat keine Daten geliefert, bitte PMID überprüfen oder später erneut probieren.',
	'pubmedparser-error-invalidpmid' => 'Ungültige PMID, bitte überprüfen.'
);
