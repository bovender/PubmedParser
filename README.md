# Extension PubmedParser

<https://www.mediawiki.org/wiki/Extension:PubmedParser>

**PubmedParser** is an extension for [MediaWiki][]. It provides a parser
keyword (`#pmid`) to fetch article information by unique ID from the [Pubmed][]
database. It outputs the article information formatted as Wiki markup for a
template. The template can be defined inside the Wiki and adjusted as needed.
Compared with the other, very useful extension [Pubmed][Pubmed extension]
(which, however, has not been updated in a while), this extension can be fully
[configured](#customization) using Wiki messages. It does not require editing
configuration files on the server.

PubmedParser fetches information on a single article at a time only. If you
would like to generate lists of articles, please have a look at the
[Pubmed][Pubmed extension] extension (provided it still works with recent
versions of MediaWiki).

If the [Cite][] extension is installed, you can add an additional parameter to
the #pmid keyword, and PubmedParser will generate a `<ref name="additional
parameter">...</ref>` structure for you. This makes it extremely easy to insert
reusably references into your Wiki page.

<!-- TOC ignore:true -->
## Contents

<!-- TOC -->



<!-- /TOC -->

## Installation

To obtain the extension, you can either download a compressed archive from the
[Github releases page][]: Choose one of the 'Source code' archives and extract
it in your Wiki's `extension` folder. Note that these archives contain a folder
that is named after the release version, e.g. `PubmedParser-5.1.0`. You may want
to rename the folder to `PubmedParser`.

Alternatively (and preferred by the author), if you have [Git][], you can clone
the repository in the usual way into the `extensions` folder.

To activate the extension, add the following to your `LocalSettings.php` file:

```php
wfLoadExtension( 'PubmedParser' );
```

Older versions of the extension required the PHP parameter `allow_url_fopen` to
be able to fetch XML data from PubMed. Since there are security concerns over
using this parameter, from version 1.0.0 on, PubmedParser will also work if
this option is not set. In that case, it will require the Curl library. Many
PHP servers are configured to work with Curl.

## Configuration

### NCBI API key

**NCBI/Pubmed imposes a limit to the number of requests per second.** If you
expect your wiki to issue more than three requests per second (> 3/s), you will
need an API key. The API key is a hexadecimal number with 36 digits. _With an API
key, the limit is raised to 10 per second_.
See "[New API Keys for the E-utilities][]"
and "[A General Introduction to the E-utilities][]" at NCBI for more information.

Please note that this rate limit applies to your _server_, not to the users'
browsers. If you edit a page and have more than 3 new `#pmid` keywords in the
text, your server will issue as many calls to the Pubmed API, and this may
exceed the rate limit. If you edit a page with pre-existing `#pmid` keywords,
no additional calls to the Pubmed API will be issued, because those records
can be fetched from cache. You can [obtain][obtain-key] an API key from the NCBI
Account Settings page.

In a similar vein, if there is a possibility that more than three users of your
wiki edit pages containing a `#pmid` keyword at the same time, you may also want
to [obtain][obtain-key] and configure an API key in order to prevent error
messages from Pubmed.

Once you have your API key, place it in your `LocalSettings.php` as follows:

```php
$wgPubmedParserApiKey = `<your personal 36 hexadecimal digits>';
```

Keep your API key private to prevent abuse (which would be tracked back to
your NCBI account).

### Remote fetch method

By default, PubmedParser will attempt to use the PHP function
[`file_get_contents()`][fgc] to fetch article information from the remote NCBI
servers. You can change this to [`cURL`][curl] if you wish -- for instance,
if `file_get_contents()` is not available to you:

```php
$wgPubmedParserRemoteFetchMethod = 'curl'; // default is 'file_get_contents'
```

## Usage

To retrieve the article with PMID [19782018][], insert the following into your
page:

```mediawiki
{{#pmid:19782018}}
```

This will produce the following output:

```mediawiki
{{pubmed|pmid=19782018|authors=Alon|allauthors=Alon
    |title=How to choose a good scientific problem
    |journal=Molecular cell|journala=Mol. Cell.
    |year=2009|volume=35|pages=726-8|doi=10.1016/j.molcel.2009.09.013}}
```

However, you will never get to see this output, since `{{pubmed|...}}`
represents a template, which is immediately processed by the MediaWiki
software.

Notes:

- The template `pubmed` must exist in your wiki; otherwise, "Template:pubmed"
  will be inserted in red into your displayed page (click on the red link to
  create the template).
- You can fully customize the names of the template itself as well as the names
  of the parameters of the template (see below).
- Your template does not need to use all parameters; unused parameters will be
  discarded.
- `authors` is an abbreviated list of the author names; use `allauthors` if
  you need all of the names.
- `journal` is the full journal name as stored in Pubmed; use `journala` to
  get the MedLine title abbreviation.

Thus, if your template looks like this:

```mediawiki
{{{authors}}}: {{{title}}}. ''{{{journala}}}'' {{{year}}};{{{volume}}}:{{{pages}}}.
```

You will get this:

```mediawiki
{| class="wikitable" style="width:100%;"
| Alon: How to choose a good scientific problem. ''Mol. Cell.'' 2009;35:726-8.
|}
```

### Template variables

You can use the following variables in your "pubmed" template. Note that you
can edit all variable names (as well as the name of the template itself)
according to your needs (see [Customization](#Ccstomization) below).

| Parameter | Description
|-----------|------------
| `{{{authors}}}` | Abbreviated list of author names. If there are only two authors, they will be concatenated by "&"; with three or more authors, an "et al." will be appended after the first author. <br />Note: You can edit the "&" and "et al." at Special:AllMessages (see [below](#customization)).
| `{{{authorsi}}}` | Abbreviated list of author names, just like above, but with initials appended. <br /> Note: You can edit the separator between last name and first name as well as what to put after the initials (e.g., a period) at Special:AllMessages (see [below](#customization)).
| `{{{allauthors}}}` | List of all author names. The last author's name is appended with "&". <br /> Note: You can edit the "&" and "et al." at Special:AllMessages (see [below](#customization)).
| `{{{allauthorsi}}}` | List of all author names, just like above, but with initials appended. <br /> Note: You can edit the separator between last name and first name as well as what to put after the initials (e.g., a period) at Special:AllMessages (see [below](#customization)).
| `{{{title}}}` | The title of the article. A trailing period will be stripped.
| `{{{journal}}}` | The full name of the journal as stored in Pubmed. Capitalization is the same as in Pubmed.
| `{{{journalcaps}}}` | The full name of the journal as stored in Pubmed with all words capitalized.
| `{{{journala}}}` | The abbreviated name of the journal (ISO specification), e.g.: N. Engl. J. Med.
| `{{{journalanop}}}` | The abbreviated name of the journal (ISO specification) without periods, e.g.: N Engl J Med
| `{{{year}}}` | The year the article was published.
| `{{{volume}}}` | The volume of the journal.
| `{{{pages}}}` | The pagination as stored in Pubmed. Leading digits may be omitted in the last page number; for example, "1324 through 1336" is given as "1324-36".
| `{{{pmid}}}` | The PMID number (i.e., the same ID that was used to call #pmid).
| `{{{doi}}}` | The DOI ([Digital Object Identifer][]) of the article that points to the full text. Not all Pubmed entries provide this information.
| `{{{abstract}}}` | The article's abstract. If you want to have a ''collapsible'' abstract in your template, consider the [example below](#collapse).

### Example template `pubmed`

```mediawiki
{{{authors}}}: {{{title}}}. ''{{{journala}}}'' {{{year}}};
{{#if:{{{volume|}}}|{{{volume|}}}:}}{{{pages|)}}}.
PMID: [{{{pmid|}}}](https://www.ncbi.nlm.nih.gov/pubmed/{{{pmid|}}}).
{{**#if**:{{{doi|}}}|[DOI](https://dx.doi.org/{{{doi|}}}).}}
```

(When copying this into your own Wiki, make sure to place the entire code on one line.)

Formatted example:

```mediawiki
Cumming ''et al.'': Error bars in experimental biology.
''J. Cell Biol.'' 2007;177:7-11.
PMID: [17420288](https://www.ncbi.nlm.nih.gov/pubmed/17420288).
[DOI](https://dx.doi.org/10.1083/jcb.200611141).
```

Note: This template requires the [ParserFunctions][] extension (which provides
`{{#if:test|then|else}}`) to link the DOI only if it is contained in the Pubmed
data.

<a id="collapse"></a>Alternative with collapsible abstract:

```mediawiki
{{#if:{{{authorsi}}}|{{{authorsi}}}:}} {{{title}}}.
''{{#if:{{{journalanop}}}|{{{journalanop}}}|{{{journalcaps}}}}}''
{{{year}}}{{#if:{{{volume}}}|;}}{{{volume}}}{{#if:{{{firstpage}}}|:{{{firstpage}}}}}.
PMID: [{{{pmid|}}}](https://pubmed.gov/{{{pmid|}}}).
{{#if:{{{doi}}}|DOI: [{{{doi}}}](https://dx.doi.org/{{{doi}}}).}}
{{#if:{{{pmc}}}|[Full text](https://ncbi.nlm.nih.gov/pmc/{{{pmc}}}?report=reader)}}
{{#if:{{{abstract}}}|<span class="abstract mw-collapsible mw-collapsed"
data-expandtext="Abstract" data-collapsetext="Abstract">{{{abstract}}}</span>}}
```

### Adding `<ref></ref>` tags automatically

You may add an additional parameter to the #PMID call to make the PubmedParser
automatically insert `<ref></ref>` tags. The [Cite][] extension will use these
tags to build your bibliography. To reuse the reference, simply insert
`<ref name="yourparameter" />`.

Example:

```mediawiki
{{#pmid:19782018|Alon2009}}
```

This will produce the following output:

```html
<ref name="Alon2009">{{pubmed|pmid=19782018|authors=Alon|allauthors=Alon|title=How to choose a good scientific problem|journal=Molecular cell|journala=Mol. Cell.|year=2009|volume=35|pages=726-8|doi=10.1016/j.molcel.2009.09.013}}</ref>
```

Since this output is immediately parsed again by MediaWiki, you will never get
to see it this way; instead, you will see the footnote generated by the [Cite][]
extension, which contains whatever output your template "pubmed" produces from
this.

To cite this same paper again, simply type `<ref name="Alon2009" />`

### Forcing reload from Pubmed

Occasionally, Pubmed article information is updated, e.g. when an article that
was initially published online-only appears in print. When using the cache
feature, you can force retrieval of information from Pubmed by adding a
'reload' parameter to your `#pmid` call:

```mediawiki
{{#pmid:123456|reload}}
```

or

```mediawiki
{{#pmid:123456|Miller2011|reload}}
```

It goes without saying that the updated information is stored in the cache folder.

Note that reloading only occurs when a page is _edited_ but not when it is
_viewed_. Therefore, you can leave the 'reload' parameter in your `#pmid` call
without causing superfluous download requests from Pubmed. The next time you
edit a page, you can remove the 'reload' option, and the article information
will be retrieved from cache again.

## Customization

You can customize the name of the template as well as the names of the
parameters by editing the system messages of your wiki: Go to
`Special:AllMessages` and filter for `pubmedparser`.

Name | Description | Default value
-----|-------------|--------------
`pubmedparser-templatename` | Name of the MediaWiki template | pubmed
`pubmedparser-abstract`     | Template parameter for the abstract | abstract
`pubmedparser-authors` | Template parameter for the abbreviated list of authors | authors
`pubmedparser-authorsi` | Template parameter for abbreviated list of authors with initials | authorsi
`pubmedparser-allauthors` | Template parameter for the complete list of authors | allauthors
`pubmedparser-allauthorsi` | Template parameter for the complete list of authors with initials | allauthorsi
`pubmedparser-journal` | Template parameter for the journal name (Pubmed style) | journal
`pubmedparser-journalcaps` | Template parameter for the journal name (capitalized) | journalcaps
`pubmedparser-journala` | Template parameter for the abbreviated journal name | journala
`pubmedparser-journalanop` | Template parameter for the complete list of authors without periods | journalanop
`pubmedparser-volume` | Template parameter for the volume | volume
`pubmedparser-pages` | Template parameter for the pagination | pages
`pubmedparser-firstpage` | Template parameter for the first page number | firstpage
`pubmedparser-year` | Template parameter for the year of publication | year
`pubmedparser-doi` | Template parameter for the digital object identifier (DOI) | doi
`pubmedparser-title` | Template parameter for the article title | title
`pubmedparser-and` | Concatenation symbol for the last author name (e.g., "and") | &
`pubmedparser-etal` | Abbreviation of the author list | ''et al.''
`pubmedparser-initialseparator` | Separator between last name and initials (e.g., ",") | [empty]
`pubmedparser-initialperiod` | Abbreviation symbol for the initials (e.g., "." or ". "). | [empty]

## Upgrading from previous versions of this extension

The 4.x and later versions finally respect the database prefix settings for the
`Pubmed` caching table. Before upgrading the database (`php maintenance/update.php`),
you may want to manually rename any existing Pubmed caching table with your
custom prefix:

```sql
RENAME TABLE Pubmed TO <YourPrefix>Pubmed;
```

(Or use your GUI/web UI of choice, e.g. phpMyAdmin.)

Of course this is only necessary if you to use table prefixes, i.e. you have a
line `$wgDBPrefix = '<YourPrefix>';` in your `LocalSettings.php`. Caveat: Don't
change this MediaWiki setting after installation; otherwise, you'll need to
manually rename all your database tables!

## License

Copyright (c) 2011-2023 Daniel Kraus ([bovender](https://www.bovender.de))
and co-authors

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

[19782018]: https://pubmed.gov/19782018
[A General Introduction to the E-utilities]: https://www.ncbi.nlm.nih.gov/books/NBK25497
[Cite]: https://mediawiki.org/wiki/Extension:Cite
[curl]: https://www.php.net/manual/book.curl.php
[Digital Object Identifier]: https://www.doi.org
[fgc]: https://www.php.net/manual/function.file-get-contents.php
[Git]: https://git-scm.com
[Github releases page]: https://github.com/bovender/PubmedParser/releases
[MediaWiki]: https://www.mediawiki.org
[New API Keys for the E-utilities]: https://ncbiinsights.ncbi.nlm.nih.gov/2017/11/02/new-api-keys-for-the-e-utilities
[obtain-key]: https://www.ncbi.nlm.nih.gov/account/settings
[ParserFunctions]: https://mediawiki.org/wiki/Extension:ParserFunctions
[Pubmed]: https://pubmed.gov
[Pubmed extension]: https://www.mediawiki.org/wiki/Extension:Pubmed
