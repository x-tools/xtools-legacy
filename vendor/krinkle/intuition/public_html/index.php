<?php
/**
 * Main entry point (web dashboard).
 *
 * This file outputs the interface to change user preferences.
 *
 * @copyright 2011-2014 See AUTHORS.txt
 * @license CC-BY 3.0 <https://creativecommons.org/licenses/by/3.0/>
 * @package intuition
 */

/**
 * Configuration
 * -------------------------------------------------
 */

require_once __DIR__ . '/../vendor/autoload.php';

$I18N = new Intuition( array(
	'domain' => 'tsintuition',
	'mode' => 'dashboard',
) );

if ( file_exists( __DIR__ . '/config.php' ) ) {
	require_once __DIR__ . '/config.php';
};


// Initialize BaseTool
$Tool = BaseTool::newFromArray( array(
	'displayTitle' => $I18N->msg( 'title' ),
	'remoteBasePath' => $I18N->dashboardHome,
	'localBasePath' => $I18N->localBaseDir,
	'revisionId' => $I18N->version,
	'styles' => array(
		'main.css',
	),
	'scripts' => array(
		'main.js',
	),
	'licenses' => array(
		'CC-BY 3.0' => 'https://creativecommons.org/licenses/by/3.0/'
	),
) );
$Tool->setSourceInfoGithub( 'Krinkle', 'intuition', dirname( __DIR__ ) );

/**
 * Tool settings
 * -------------------------------------------------
 */
$toolSettings = array(
	'tabs' => array(),
);

/**
 * Post actions
 * -------------------------------------------------
 */
if ( isset( $_POST['action'] ) ) {

	switch ( $_POST['action'] ) {
		case 'prefset':
			// Set a 30-day, then redirect to index
			$I18N->setCookie( 'userlang', $_POST['fpLang'] );
			$I18N->redirectTo( $Tool->remoteBasePath, 302 );
			break;
	}
}

/**
 * Get actions
 * -------------------------------------------------
 */
if ( isset( $_GET['action'] ) ) {

	switch ( $_GET['action'] ) {
		case 'clearcookies':
			$I18N->wipeCookies();
			$I18N->redirectTo( $Tool->generatePermalink( array( 'msg' => 2 ) ), 302 );
			break;
		case 'renewcookies':
			$I18N->renewCookies();
			$I18N->redirectTo( $Tool->generatePermalink( array( 'msg' => 3 ) ), 302 );
			break;
	}
}

/**
 * Custom return to
 * -------------------------------------------------
 */
// Tools can pass returnto and returntoquery parameters
// to redirect visitors back to them after setting, changing
// or doing something (eg. clearcookies, renewcookies or prefset)
if ( $I18N->isRedirecting() ) {
	$returnTo = $kgReq->getVal( 'returnto' );
	$returnToQuery = $kgReq->getVal( 'returntoquery' );
	if ( IntuitionUtil::nonEmptyStr( $returnTo ) ) {
		if ( IntuitionUtil::nonEmptyStr( $returnToQuery ) ) {
			$returnToQuery = '?' . urldecode( $returnToQuery );
		} else {
			$returnToQuery = '';
		}
		$I18N->redirectTo( "//{$_SERVER['SERVER_NAME']}$returnTo$returnToQuery", 302 );
	}
}


$I18N->doRedirect();

/**
 * Main content output
 * -------------------------------------------------
 */
$Tool->setLayout( 'header', array(
	'captionHtml' => $I18N->msg( 'fullname' ),
) );
$Tool->addOut( '<div class="container">' );
$Tool->addOut( _g( 'welcome' ), 'h2' );
$Tool->addOut( '<div class="well">' );

$tabContent = '<div class="tab-content">';

// Messages ?
if ( isset( $_GET['msg'] ) ) {
	switch ( $_GET['msg'] ) {
		case 2:
			$Tool->addOut(
				$I18N->msg( 'clearcookies-success' ),
				'div',
				array( 'class' => 'msg ns' )
			);
			break;
		case 3:
			$Tool->addOut(
				$I18N->msg( 'renewcookies-success', array( 'variables' => array( '30 ' . _g( 'days', array(
					'parsemag' => true,
					'variables' => array( 30 )
				) ) ) ) ),
				'div',
				array( 'class' => 'msg ns success' )
			);
			break;
	}
}

// Cookie has already been set, show "current-settings" box
if ( $I18N->hasCookies() ) {

	$lifetime = $I18N->getCookieLifetime();
	$after = '';
	$cookieHealthClass = false;
	$cookieHealthIcon = false;
	$renew = '<p class="help-block">' . Html::element( 'a', array(
		'href' => $Tool->generatePermalink( array( 'action' => 'renewcookies' ) )
	), $I18N->msg( 'renew-cookies' ) ) .'</p>';

	// 29+ days
	if ( $lifetime > 29 * 24 * 3600 ) {
		$cookieHealthClass = 'success';
		$cookieHealthIcon = 'ok';

		$number = floor( $lifetime / 3600 / 24 / 29 );
		$time = $number . '+ ' . _g( 'months', array(
			'parsemag' => true, 'variables' => array( $number )
		) );

	// 10+ days
	} elseif ( $lifetime > 10 * 24 * 3600 ) {
		$cookieHealthClass = 'warning';
		$cookieHealthIcon = 'warning-sign';

		$number = floor( $lifetime / 3600 / 24 );
		$time = $number . '+ ' . _g( 'days', array(
			'parsemag' => true, 'variables' => array( $number )
		) );
		$after = $renew;

	// 1+ day
	} elseif ( $lifetime > 24 * 3600 ) {
		$cookieHealthClass = 'warning';
		$cookieHealthIcon = 'warning-sign';

		$number = floor( $lifetime / 3600 / 24 );
		$time = $number . '+ ' . _g( 'days', array(
			'parsemag' => true, 'variables' => array( $number )
		) );
		$after = $renew;

	// Less than a day
	} else {
		$cookieHealthClass = 'error';
		$cookieHealthIcon = 'remove';

		$number = ceil( $lifetime / 3600 );
		$time = '<' . $number . '+ ' . _g( 'hours', array(
			'parsemag' => true, 'variables' => array( $number )
		) );
		$after = $renew;
	}

	$toolSettings['tabs']['#tab-currentsettings'] = $I18N->msg( 'tab-overview' );
	$tabContent .=
		'<div class="tab-pane active" id="tab-currentsettings">'
	.	'<form role="form" class="form-horizontal"><fieldset>'
	.	Html::element( 'legend', array(), $I18N->msg( 'current-settings' ) )
	.	'<div class="form-group">'
	.	Html::element( 'label', array(
			'class' => 'col-sm-4 control-label'
		), $I18N->msg( 'current-language' ) . _g( 'colon-separator' ) . ' ' )
	.	'<div class="col-sm-8">'
	.	Html::element( 'input', array(
		'value' => $I18N->getLangName(),
		'readonly' => true,
		'class' => 'form-control'
	) )
	.	'<p class="help-block">'
	.	Html::element( 'a', array(
			'href' => $Tool->generatePermalink( array( 'action' => 'clearcookies' ) )
		), $I18N->msg( 'clear-cookies' ) )
	.	'</p>'
	.	'</div>'
	.	Html::element( 'label', array(
			'class' => 'col-sm-4 control-label'
	), $I18N->msg( 'cookie-expiration' ) . _g( 'colon-separator' ) )
	.	"<div class=\"col-sm-8 has-$cookieHealthClass has-feedback\">"
	.	Html::element( 'input', array(
			'value' => $time,
			'class' => "form-control",
			'readonly' => true
		) )
	.	"<span class=\"glyphicon glyphicon-$cookieHealthIcon form-control-feedback\"></span>"
	.	$after
	.	'</div>'
	.	'</fieldset></form>'
	.	'</div>';

	$settingsIsFirst = false;
} else {
	$settingsIsFirst = true;
}


// Settings form
$dropdown = '<select name="fpLang" class="form-control">';
$selected = ' selected';
foreach ( $I18N->getAvailableLangs() as $langCode => $langName ) {
	$attr = $langCode == $I18N->getLang() ? $selected : '';
	$dropdown .= '<option value="' . $langCode . '"' . $attr . '>'
		. "$langCode - $langName"
		. '</option>';
}
$dropdown .= '</select>';

$toolSettings['tabs']['#tab-settingsform'] = $I18N->msg('tab-settings');
$tabContent .= Html::openElement('div', array(
		'class' => array(
			'tab-pane',
			'active' => $settingsIsFirst,
		),
		'id' => 'tab-settingsform'
	) ) . '<form action="' . $Tool->remoteBasePath
		. '" method="post" role="form" class="form-horizontal">
	<fieldset>
	<legend>' . $I18N->msg( 'settings-legend' ) . '</legend>
	<div class="form-group">
	<label class="col-sm-4 control-label">'
		. _html( 'choose-language' ) .
		_g( 'colon-separator' )
	. '</label>
	<div class="col-sm-8">
	' . $dropdown . '
	</div>
	</div>

	<input type="hidden" name="action" value="prefset">
	<input type="hidden" name="returnto" value="' .
		htmlspecialchars( $kgReq->getVal( 'returnto' ) ) . '">
	<input type="hidden" name="returntoquery" value="' .
		htmlspecialchars( $kgReq->getVal( 'returntoquery' ) )  . '">
	<div class="form-group">
		<div class="col-sm-offset-4 col-sm-8">
			<input type="submit" class="btn btn-default btn-primary" value="'
				. _html( 'form-submit', 'general' ) . '">
		</div>
	</div>

</fieldset></form>
</div>';


// About tab

$about = '<div class="tab-pane" id="tab-about">';
$about .= '<a href="https://translatewiki.net/wiki/Translating:Intuition">'
	.	Html::element( 'img', array(
		'src' => 'https://translatewiki.net/w/i.php?title=Special:TranslationStats&graphit=1&'
			. 'count=edits&scale=months&days=250&width=520&height=400&group=tsint-0-all',
		'width' => 520,
		'height' => 400,
		'alt' => '',
		'class' => 'pull-right'
		))
	.	'</a>';
$about .= ''
	. '<div class="lead">' . $I18N->msg( 'usage' ) . '</div><ul>';
$tools = json_decode( file_get_contents( __DIR__ . '/tools.json' ), /* assoc = */ true );
foreach ( $tools as $domain => $domainInfo ) {
	$title = $I18N->msg( 'title', $domain, /* fallback = */ $domain );
	if ( isset( $domainInfo['url'] ) ) {
		$about .= '<li><a href="'
			. htmlspecialchars( $domainInfo['url'] )
			. '">' . htmlspecialchars( $title )
			. '</a></li>';
	}
}
$about .= '</ul><a href="https://github.com/Krinkle/intuition/wiki/Documentation">'
	. 'Technical documentation</a></div>';

$toolSettings['tabs']['#tab-about'] = $I18N->msg('tab-about');
$tabContent .= $about;

$tabContent .= '</div><!-- /.tab-content -->';

$toolSettings['tabs']['demo/demo1.php'] = $I18N->msg('tab-demo');

$tabBar = '<ul class="nav nav-tabs intuition-nav-tabs">';
reset( $toolSettings['tabs'] );
$firstTabId = key( $toolSettings['tabs'] );
foreach ( $toolSettings['tabs'] as $tabID => $tabName ) {
	$tabBar .= Html::rawElement( 'li', array(
		'class' => array(
			'active' => $tabID === $firstTabId,
		)
	), Html::element( 'a', array(
		'href' => $tabID,
		'data-toggle' => $tabID[0] === '#' ? 'tab' : null,
	), $tabName ) );
}
$tabBar .= '</ul>';

$Tool->addOut( $tabBar );
$Tool->addOut( $tabContent );
$Tool->addOut( '</div><!-- /.well -->' );
$Tool->addOut( '</div><!-- /.container -->' );

/**
 * Close up
 * -------------------------------------------------
 */
$Tool->flushMainOutput();
