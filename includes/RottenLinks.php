<?php

use MediaWiki\MediaWikiServices;

class RottenLinks {
	public static function getResponse( $url ) {
		$services = MediaWikiServices::getInstance();

		$config = $services->getConfigFactory()->makeConfig( 'rottenlinks' );

		// Make the protocol lowercase
		$urlexp = explode( '://', $url, 2 );
		$proto = strtolower( $urlexp[0] ) . '://';
		$site = $urlexp[1];
		$urlToUse = $proto . $site;

		$status = static::getHttpStatus( $urlToUse, 'HEAD', $services, $config );
		// Some websites return 4xx or 5xx on HEAD requests but GET with the same URL gives a 200.
		if ($status >= 400) {
			$status = static::getHttpStatus( $urlToUse, 'GET', $services, $config );
		}

		return $status;
	}

	private static function getHttpStatus( $url, $method, $services, $config ) {
		$httpProxy = $config->get( 'RottenLinksHTTPProxy' );

		$request = $services->getHttpRequestFactory()->createMultiClient( [ 'proxy' => $httpProxy ] )
			->run( [
				'url' => $url,
				'method' => $method,
				'headers' => [
					'user-agent' => 'RottenLinks, MediaWiki extension (https://github.com/miraheze/RottenLinks), running on ' . $config->get( 'Server' )
				]
			], [
				'reqTimeout' => $config->get( 'RottenLinksCurlTimeout' )
			]
		);
		$status = (int)$request['code'];
		return $status;
	}
}
