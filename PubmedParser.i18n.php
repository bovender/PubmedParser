<?php
$messages = array();

$messages['en'] = array(
	'pubmedparser-desc'		=> 'Adds a parser function #PMID to look up articles in pubmed.gov by ID.',

	// the following are the default template name and parameter names;
	// no need to localize them, but we define them as messages to allow
	// Wiki administrators to change the names to suit their needs
	'pubmedparser-templatename' => 'pubmed', // the name of the template to use
	'pubmedparser-authors'			=> 'authors',
	'pubmedparser-allauthors'		=> 'allauthors',
	'pubmedparser-journal'			=> 'journal',
	'pubmedparser-journala'			=> 'journala',
	'pubmedparser-volume'				=> 'volume',
	'pubmedparser-pages'				=> 'pages',
	'pubmedparser-year'					=> 'year',
	'pubmedparser-doi'					=> 'doi',
	'pubmedparser-title'				=> 'title'
);

$messages['de'] = array(
	'pubmedparser-desc'		=> 'Fügt das Parser-Schlüsselwort #PMID hinzu, mit dem Artikel anhand ihrer PMID-Nummer in Pubmed nachgeschlagen werden können.'
);
