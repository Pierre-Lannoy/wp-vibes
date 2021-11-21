jQuery( document ).ready(
	function($) {
		const All_Details = document.querySelectorAll('details');

		All_Details.forEach(deet=>{
			deet.addEventListener('toggle', toggleOpenOneOnly)
		})

		function toggleOpenOneOnly(e) {
			if (this.open) {
				All_Details.forEach(deet=>{
					if (deet!=this && deet.open) deet.open = false
				});
			}
		}


		$( ".vibes-about-logo" ).css( {opacity:1} );
		$( ".vibes-select" ).each(
			function() {
				var chevron  = 'data:image/svg+xml;base64,PHN2ZwogIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICB3aWR0aD0iMjQiCiAgaGVpZ2h0PSIyNCIKICB2aWV3Qm94PSIwIDAgMjQgMjQiCiAgZmlsbD0ibm9uZSIKICBzdHJva2U9IiM3Mzg3OUMiCiAgc3Ryb2tlLXdpZHRoPSIyIgogIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIKICBzdHJva2UtbGluZWpvaW49InJvdW5kIgo+CiAgPHBvbHlsaW5lIHBvaW50cz0iNiA5IDEyIDE1IDE4IDkiIC8+Cjwvc3ZnPgo=';
				var classes  = $( this ).attr( "class" ),
				id           = $( this ).attr( "id" ),
				name         = $( this ).attr( "name" );
				var template = '<div class="' + classes + '">';
				template    += '<span class="vibes-select-trigger">' + $( this ).attr( "placeholder" ) + '&nbsp;<img style="width:18px;vertical-align:top;" src="' + chevron + '" /></span>';
				template    += '<div class="vibes-options">';
				$( this ).find( "option" ).each(
					function() {
						template += '<span class="vibes-option ' + $( this ).attr( "class" ) + '" data-value="' + $( this ).attr( "value" ) + '">' + $( this ).html().replace( "~-", "<br/><span class=\"vibes-option-subtext\">" ).replace( "-~", "</span>" ) + '</span>';
					}
				);
				template += '</div></div>';

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
		$( "#vibes-chart-button-calls" ).on(
			"click",
			function() {
				$( "#vibes-chart-calls" ).addClass( "active" );
				$( "#vibes-chart-data" ).removeClass( "active" );
				$( "#vibes-chart-uptime" ).removeClass( "active" );
				$( "#vibes-chart-button-calls" ).addClass( "active" );
				$( "#vibes-chart-button-data" ).removeClass( "active" );
				$( "#vibes-chart-button-uptime" ).removeClass( "active" );
			}
		);
		$( "#vibes-chart-button-data" ).on(
			"click",
			function() {
				$( "#vibes-chart-calls" ).removeClass( "active" );
				$( "#vibes-chart-data" ).addClass( "active" );
				$( "#vibes-chart-uptime" ).removeClass( "active" );
				$( "#vibes-chart-button-calls" ).removeClass( "active" );
				$( "#vibes-chart-button-data" ).addClass( "active" );
				$( "#vibes-chart-button-uptime" ).removeClass( "active" );
			}
		);
		$( "#vibes-chart-button-uptime" ).on(
			"click",
			function() {
				$( "#vibes-chart-calls" ).removeClass( "active" );
				$( "#vibes-chart-data" ).removeClass( "active" );
				$( "#vibes-chart-uptime" ).addClass( "active" );
				$( "#vibes-chart-button-calls" ).removeClass( "active" );
				$( "#vibes-chart-button-data" ).removeClass( "active" );
				$( "#vibes-chart-button-uptime" ).addClass( "active" );
			}
		);
	}
);

