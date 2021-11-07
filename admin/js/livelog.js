jQuery( document ).ready(
	function($) {
		function initialize() {
			$( '#vibes-select-filter' ).change(
				function () {
					filter = $( this ).val();
				}
			);
			$( '#vibes-control-play' ).click(
				function () {
					consoleRun();
				}
			);
			$( '#vibes-control-pause' ).click(
				function () {
					consolePause();
				}
			);
		}
		function consoleRun() {
			document.querySelector( '#vibes-control-pause' ).classList.remove( 'vibes-control-inactive' );
			document.querySelector( '#vibes-control-pause' ).classList.add( 'vibes-control-active' );
			document.querySelector( '#vibes-control-play' ).classList.remove( 'vibes-control-active' );
			document.querySelector( '#vibes-control-play' ).classList.add( 'vibes-control-inactive' );
			document.querySelector( '.vibes-control-hint' ).innerHTML = 'running&nbsp;&nbsp;&nbsp;🟢';
			running = true;
		}
		function consolePause() {
			document.querySelector( '#vibes-control-play' ).classList.remove( 'vibes-control-inactive' );
			document.querySelector( '#vibes-control-play' ).classList.add( 'vibes-control-active' );
			document.querySelector( '#vibes-control-pause' ).classList.remove( 'vibes-control-active' );
			document.querySelector( '#vibes-control-pause' ).classList.add( 'vibes-control-inactive' );
			document.querySelector( '.vibes-control-hint' ).innerHTML = 'paused&nbsp;&nbsp;&nbsp;🟠';
			running = false;
		}
		function loadLines() {
			if ( running ) {
				if ( '0' === index ) {
					elem = document.createElement( 'pre' );
					elem.classList.add( 'vibes-logger-line' );
					elem.classList.add( 'vibes-logger-line-init' );
					elem.innerHTML = 'Waiting first signal...';
					root.appendChild( elem );
					init = true;
				}
				$.ajax(
					{
						type : 'GET',
						url : livelog.restUrl,
						data : { filter: filter, index: index },
						beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', livelog.restNonce ); xhr.setRequestHeader( 'Traffic-No-Log', 'inbound' ); },
						success: function( response ) {
							if ( response ) {
								if ( undefined !== response.index ) {
									index = response.index;
								}
								if ( undefined !== response.items ) {
									items = Object.entries( response.items );
									if ( items.length > 0 ) {
										if ( init ) {
											root.removeChild( root.firstElementChild );
											init = false;
											consoleRun();
										}
										items.forEach(
											function( item ){
												elem = document.createElement( 'pre' );
												elem.classList.add( 'vibes-logger-line' );
												elem.classList.add( 'vibes-logger-line-' + item[1].type );
												elem.innerHTML = item[1].line.replace( ' ', '&nbsp;' );
												if ( root.childElementCount > livelog.buffer ) {
													root.removeChild( root.firstElementChild );
												}
												root.appendChild( elem );
												$( '#vibes-logger-lines' ).animate( { scrollTop: elem.offsetTop }, 20 );
											}
										);
									}
								}
							}
						},
						complete:function( response ) {
							setTimeout( loadLines, livelog.frequency );
						}
					}
				);
			} else {
				setTimeout( loadLines, 250 );
			}
		}

		let filter  = 'all';
		let index   = '0';
		let running = true;
		let init    = false;
		const root  = document.querySelector( '#vibes-logger-lines' );

		initialize();
		loadLines();

	}
);
