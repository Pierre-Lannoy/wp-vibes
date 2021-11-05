import {getCLS, getFID, getLCP, getTTFB, getFCP} from './web-vitals/web-vitals.js'

function sendAnalytics(metrics) {
	metrics.authenticated = analyticsSettings.authenticated;
	metrics.locationUrl   = location ? location.href : '-';
	if ( navigator.sendBeacon ) {
		console.log( metrics );
		navigator.sendBeacon( analyticsSettings.restUrl, JSON.stringify( metrics ) );
	}
}

function sendToGoogleAnalytics(name, delta, value, id) {
	if ( ! analyticsSettings.gAnalytics ) {
		console.log( 'ABORT' );
		return;
	}
	if (typeof ga === 'function') {  // Legacy Google Analytics
		console.log( ga );
		ga(
			'send',
			'event',
			{
				eventCategory: 'Web Vitals',
				eventAction: name,
				eventLabel: id,
				eventValue: Math.round( name === 'CLS' ? delta * 1000 : delta ),
				nonInteraction: true,
				transport: 'beacon',
			}
		);
	}
	if (typeof gtag === 'function') {
		ga(
			function(tracker) {
				console.log( tracker.get( 'version' ) );
			}
		);
	}
}

function webVitalsReport({name, delta, value, id}) {
	sendAnalytics(
		{
			metrics: [{
				name:  name,
				value: value,
			}]
		}
	);
	//sendToGoogleAnalytics(name, delta, value, id);
}

function webVitalsObserve() {
	getCLS( webVitalsReport );
	getFID( webVitalsReport );
	getLCP( webVitalsReport );
	getTTFB( webVitalsReport );
	getFCP( webVitalsReport );
}

webVitalsObserve();