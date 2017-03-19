PubmedParser extension for MediaWiki
====================================

This is an extension for the [MediaWiki](https://www.mediawiki.org) wiki 
software that facilitates retrieval and display of scientific citations 
from [Pubmed](https://pubmed.gov).

For installation and usage instructions, please see: 
<https://www.mediawiki.org/wiki/Extension:PubmedParser>


## Upgrading from previous versions of this extension (< 4)

The 4.x series finally respects the database prefix settings for the 'Pubmed' 
caching table. Before upgrading the database (`php maintenance/update.php`), 
you may want to manually rename any existing Pubmed caching table with your 
custom prefix:

        RENAME TABLE Pubmed TO <YourPrefix>Pubmed;

(Or use your GUI/web UI of choice, e.g. phpMyAdmin.)

Of course this is only necessary if you to use table prefixes, i.e. you have a 
line `$wgDBPrefix = '<YourPrefix>';` in your `LocalSettings.php`. Caveat: Don't 
change this MediaWiki setting after installation; otherwise, you'll need to 
manually rename all your database tables!


## License

PubmedParser

Copyright (C) 2011-2017 Daniel Kraus ([bovender](https://www.bovender.de))

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
