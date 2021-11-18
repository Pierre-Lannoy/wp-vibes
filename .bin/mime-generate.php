<?php
// phpcs:ignoreFile

// *********************************************************************

const VERSION  = '1.51.0';
const SOURCE   = 'https://cdn.jsdelivr.net/npm/mime-db@1.51.0/db.json';

// *********************************************************************
const TEMPLATE = './mime-types.php';
const TARGET   = '../assets/mime-types.php';
const TEMPFILE = './db.json';

function download_source() {
	$handle     = curl_init();
	$fileHandle = fopen( TEMPFILE, 'w' );
	curl_setopt_array(
		$handle,
		[
			CURLOPT_URL  => SOURCE,
			CURLOPT_FILE => $fileHandle,
		]
	);
	$data = curl_exec( $handle );
	curl_close( $handle );
	fclose( $fileHandle );
}

function getMimeTypesArray() {
	$result = [];
	foreach ( json_decode( file_get_contents( TEMPFILE ), true ) as $type => $desc ) {
		if ( array_key_exists( 'extensions', $desc ) && is_array( $desc['extensions'] ) ) {
			foreach ( $desc['extensions'] as $ext ) {
				$result[ $ext ] = $type;
			}
		}
	}
	return $result;
}

function writeAsset( $mimeTypes ) {
	$result = '';
	$tmp = print_r( $mimeTypes, true );
	$tmp = str_replace( [ "Array" . PHP_EOL, '(' . PHP_EOL, ')' . PHP_EOL  ], '' , $tmp );
	$tmp = str_replace( PHP_EOL, '\',' . PHP_EOL, $tmp );
	$tmp = str_replace( '=> ', '=> \'' , $tmp );
	$tmp = str_replace( [ '[', ']' ], '\'' , $tmp );

	$result = file_get_contents( TEMPLATE );
	$result = str_replace( '$VERSION$', VERSION, $result );
	$result = str_replace( '$DATE$', date('Y-m-d' ), $result );
	$result = str_replace( '$ARRAY$', $tmp, $result );
	file_put_contents( TARGET, $result );
}


download_source();
writeAsset( getMimeTypesArray() );
