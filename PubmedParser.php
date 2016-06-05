<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'PubmedParser' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['PubmedParser'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['PubmedParserMagic'] = __DIR__ . '/includes/PubmedParser_Magic.php';
	wfWarn(
		'Deprecated PHP entry point used for PubmedParser extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the PubmedParser extension requires MediaWiki 1.25+' );
}
