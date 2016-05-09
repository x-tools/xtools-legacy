## v0.2.3 (2016-03-16)

### Enhancements

* core: Precompile language list for performance.

### Bug fixes

* core: Add English to fallback chains. (issue [#53](https://github.com/Krinkle/intuition/issues/53))

## v0.2.2 (2015-11-12)

Domains:

* Removed orphantalk2 <https://github.com/Krinkle/mw-tool-orphantalk>

## v0.2.0 (2015-11-07)

Core framework now available as Composer package. See
<https://packagist.org/packages/krinkle/intuition>. Details at
<https://github.com/Krinkle/intuition/wiki/Migrate>.

Domains:

* Added whatleaveshere <https://github.com/Krinkle/mw-gadget-whatleaveshere>
* Added tsreports <https://tools.wmflabs.org/tsreports/>
* Added reflinks <https://tools.wmflabs.org/fengtools/reflinks/>
* Added templatetransclusioncheck <https://tools.wmflabs.org/templatetransclusioncheck/>
* Added dcatap <https://github.com/lokal-profil/DCAT>

### Enhancements

* api: Implement HTTP 304 caching for load.php and api.php.
* core: Implement registerDomain() method for custom domains.
* core: Rewrite using new BaseTool and Bootstrap skin.
* js-env: Implement batching for API requests (100ms debounce).

### Bug fixes

* core: Generate valid html in Intuition::getPromoBox().
* dashboard: Use "1 month" indication instead of "4 weeks".
* demo: Fix undefined message "apple-stats" in demo8.

### Maintenance

* language: Localisation data is now stored in JSON files instead of PHP.
* language: Update Names, Rtl, and Fallbacks from latest upstream MediaWiki.

## v0.1.3 (2014-05-22)

Repository moved to <https://github.com/Krinkle/intuition>.

### Maintenance

* Rename `TsIntuitionUtil::return_dump` to `TsIntuitionUtil::returnDump`.
* Deprecate global function `_()`.
* Rename hook `TsIntuition_inithook` to `intuitionHookInit`.
  Old one still works, though only one will run (if both are
  defined, the new one is used).
* Rename TsIntuition to Intuition.

## v0.1.2 (2013-04-01)

Dashboard moved to <http://toolserver.org/~intuition/>.

New domains:

* MonumentsAPI <http://toolserver.org/~erfgoed/api/>
* Recent Anonymous Activity <http://toolserver.org/~krinkle/recentAnonymousActivity/>

### Enhancements

* Textdomains may now define a $url in the definition file. (r85286)
* Added an About-area on the index page of the tool which shows all textdomains and (where
  possible) linked to the tool.
* Implemented TsIntuition::msgExists() and TsIntuition:getDomainInfo()
* Added demonstration sandboxes in /public_html/demo/ (r85471)
* Dashboard sections converted to Tabs with jQuery UI (r85260)
* Introduced new 'suppressfatal' option (r85471)
* Introduced new 'suppressbrackets' option (r85471)

### Maintenance

* Domains are now registered in Domains.php instead of a static array in the class (r85396)
* TranslationStats-graph in the about-tab (r85471)

## v0.1.1 (2011-04-01)

New domains:

* OrphanTalk2 <http://toolserver.org/~krinkle/OrphanTalk2/>

### Enhancements

* Added a clear-cookies and renew-cookies action (r84981, r85246)
* Confirmation messages after clearing or renewing cookies (r84981)
* PremadeToolserverTextdomains class has been written for TransateWiki

### Maintenance

* SVN revision id is now visible in the header (r84942)
* PremadeToolserverTextdomains moved to /exensions/Translate per convention (r85117)
* Requesting an undefined message triggers a TsIntution error on E_NOTICE level (r85052)

## v0.1.0 (2011-03-28)

First version in Wikimedia SVN.

## v0.0.1 (2011-03-23)

Initial version on Toolserver.
