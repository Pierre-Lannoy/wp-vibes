jQuery( document ).ready(
	function($) {
		$( ".vibes-about-logo" ).css( {opacity:1} );
		$( ".vibes-select" ).each(
			function() {
				var chevron  = 'data:image/svg+xml;base64,PHN2ZwogIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICB3aWR0aD0iMjQiCiAgaGVpZ2h0PSIyNCIKICB2aWV3Qm94PSIwIDAgMjQgMjQiCiAgZmlsbD0ibm9uZSIKICBzdHJva2U9IiM3Mzg3OUMiCiAgc3Ryb2tlLXdpZHRoPSIyIgogIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIKICBzdHJva2UtbGluZWpvaW49InJvdW5kIgo+CiAgPHBvbHlsaW5lIHBvaW50cz0iNiA5IDEyIDE1IDE4IDkiIC8+Cjwvc3ZnPgo=';
				var classes  = $( this ).attr( "class" );
				var data     = $( this ).attr( "data" );
				var template = '<div class="' + classes + '">';
				if ( '' !== data ) {
					template += '<span class="vibes-select-trigger"><img style="width:13px;vertical-align:middle;" src="' + data + '"/>&nbsp;&nbsp;<span class="vibes-exergue">' + $( this ).attr( "placeholder" ) + '</span></span>';
				} else {
					template += '<span class="vibes-select-trigger">' + $( this ).attr( "placeholder" ) + '&nbsp;<img style="width:18px;vertical-align:top;" src="' + chevron + '" /></span>';
				}
				template += '<div class="vibes-options"><div class="vibes-options-container">';
				$( this ).find( "option" ).each(
					function() {
						template += '<span class="vibes-option " data-value="' + $( this ).attr( "value" ) + '">' + $( this ).html().replace( "~-", "<br/><span class=\"vibes-option-subtext\">" ).replace( "-~", "</span>" ) + '</span>';
					}
				);
				template += '</div></div></div>';
				$( this ).wrap( '<div class="vibes-select-wrapper"></div>' );
				$( this ).after( template );
			}
		);
		$( ".vibes-option:first-of-type" ).hover(
			function() {
				$( this ).parents( ".vibes-options" ).addClass( "option-hover" );
			},
			function() {
				$( this ).parents( ".vibes-options" ).removeClass( "option-hover" );
			}
		);
		$( ".vibes-select-trigger" ).on(
			"click",
			function() {
				$( ".vibes-select" ).not( $( this ).parents( ".vibes-select" ) ).removeClass( "opened" );
				$( 'html' ).one(
					'click',
					function() {
						$( ".vibes-select" ).removeClass( "opened" );
					}
				);
				$( this ).parents( ".vibes-select" ).toggleClass( "opened" );
				event.stopPropagation();
			}
		);
		$( ".vibes-option" ).on(
			"click",
			function() {
				$( location ).attr( "href", $( this ).data( "value" ) );
			}
		);


		$( "#vibes-chart-button-cls" ).on(
			"click",
			function() {
				$( "#vibes-chart-cls" ).addClass( "active" );
				$( "#vibes-chart-fid" ).removeClass( "active" );
				$( "#vibes-chart-lcp" ).removeClass( "active" );
				$( "#vibes-chart-fcp" ).removeClass( "active" );
				$( "#vibes-chart-ttfb" ).removeClass( "active" );
				$( "#vibes-chart-button-cls" ).addClass( "active" );
				$( "#vibes-chart-button-fid" ).removeClass( "active" );
				$( "#vibes-chart-button-lcp" ).removeClass( "active" );
				$( "#vibes-chart-button-fcp" ).removeClass( "active" );
				$( "#vibes-chart-button-ttfb" ).removeClass( "active" );
			}
		);
		$( "#vibes-chart-button-fid" ).on(
			"click",
			function() {
				$( "#vibes-chart-cls" ).removeClass( "active" );
				$( "#vibes-chart-fid" ).addClass( "active" );
				$( "#vibes-chart-lcp" ).removeClass( "active" );
				$( "#vibes-chart-fcp" ).removeClass( "active" );
				$( "#vibes-chart-ttfb" ).removeClass( "active" );
				$( "#vibes-chart-button-cls" ).removeClass( "active" );
				$( "#vibes-chart-button-fid" ).addClass( "active" );
				$( "#vibes-chart-button-lcp" ).removeClass( "active" );
				$( "#vibes-chart-button-fcp" ).removeClass( "active" );
				$( "#vibes-chart-button-ttfb" ).removeClass( "active" );
			}
		);
		$( "#vibes-chart-button-lcp" ).on(
			"click",
			function() {
				$( "#vibes-chart-cls" ).removeClass( "active" );
				$( "#vibes-chart-fid" ).removeClass( "active" );
				$( "#vibes-chart-lcp" ).addClass( "active" );
				$( "#vibes-chart-fcp" ).removeClass( "active" );
				$( "#vibes-chart-ttfb" ).removeClass( "active" );
				$( "#vibes-chart-button-cls" ).removeClass( "active" );
				$( "#vibes-chart-button-fid" ).removeClass( "active" );
				$( "#vibes-chart-button-lcp" ).addClass( "active" );
				$( "#vibes-chart-button-fcp" ).removeClass( "active" );
				$( "#vibes-chart-button-ttfb" ).removeClass( "active" );
			}
		);
		$( "#vibes-chart-button-fcp" ).on(
			"click",
			function() {
				$( "#vibes-chart-cls" ).removeClass( "active" );
				$( "#vibes-chart-fid" ).removeClass( "active" );
				$( "#vibes-chart-lcp" ).removeClass( "active" );
				$( "#vibes-chart-fcp" ).addClass( "active" );
				$( "#vibes-chart-ttfb" ).removeClass( "active" );
				$( "#vibes-chart-button-cls" ).removeClass( "active" );
				$( "#vibes-chart-button-fid" ).removeClass( "active" );
				$( "#vibes-chart-button-lcp" ).removeClass( "active" );
				$( "#vibes-chart-button-fcp" ).addClass( "active" );
				$( "#vibes-chart-button-ttfb" ).removeClass( "active" );
			}
		);
		$( "#vibes-chart-button-ttfb" ).on(
			"click",
			function() {
				$( "#vibes-chart-cls" ).removeClass( "active" );
				$( "#vibes-chart-fid" ).removeClass( "active" );
				$( "#vibes-chart-lcp" ).removeClass( "active" );
				$( "#vibes-chart-fcp" ).removeClass( "active" );
				$( "#vibes-chart-ttfb" ).addClass( "active" );
				$( "#vibes-chart-button-cls" ).removeClass( "active" );
				$( "#vibes-chart-button-fid" ).removeClass( "active" );
				$( "#vibes-chart-button-lcp" ).removeClass( "active" );
				$( "#vibes-chart-button-fcp" ).removeClass( "active" );
				$( "#vibes-chart-button-ttfb" ).addClass( "active" );
			}
		);

		$( "#vibes-chart-button-time" ).on(
			"click",
			function() {
				$( "#vibes-chart-time" ).addClass( "active" );
				$( "#vibes-chart-net" ).removeClass( "active" );
				$( "#vibes-chart-button-time" ).addClass( "active" );
				$( "#vibes-chart-button-net" ).removeClass( "active" );
			}
		);
		$( "#vibes-chart-button-net" ).on(
			"click",
			function() {
				$( "#vibes-chart-net" ).addClass( "active" );
				$( "#vibes-chart-time" ).removeClass( "active" );
				$( "#vibes-chart-button-net" ).addClass( "active" );
				$( "#vibes-chart-button-time" ).removeClass( "active" );
			}
		);
	}
);
