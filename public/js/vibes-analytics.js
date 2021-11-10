import {getCLS, getFID, getLCP, getTTFB, getFCP} from './web-vitals/web-vitals.js'

let excluded = [];

function getRandomArbitrary(min, max) {
	return Math.random() * (max - min) + min;
}

function sendAnalytics(metrics) {
	if ( navigator.sendBeacon ) {
		navigator.sendBeacon( analyticsSettings.restUrl, JSON.stringify( metrics ) );
	}
}

function webVitalsReport({name, delta, value, id}) {
	let analytics = {
		type: 'webvital',
		resource: location ? location.href : '-',
		authenticated: analyticsSettings.authenticated,
		metrics: [{
			name:  name,
			value: value,
		}]
	}
	sendAnalytics( analytics );
}

function performanceReport(timing,type) {
	let start     = timing.startTime > 0 ? timing.startTime : 0;
	let analytics = {
		type: type,
		resource: timing.name,
		authenticated: analyticsSettings.authenticated,
		initiator: timing.initiatorType,
		metrics: [{
			name:  'span_redirect',
			start: timing.redirectStart - start,
			duration: timing.redirectEnd - timing.redirectStart,
		},{
			name:  'span_dns',
			start: timing.domainLookupStart - start,
			duration: timing.domainLookupEnd - timing.domainLookupStart,
		}]};
	if ( 0 < timing.secureConnectionStart) {
		analytics.metrics.push(
			{
				name:  'span_tcp',
				start: timing.connectStart - start,
				duration: timing.connectEnd - timing.connectStart,
			},
			{
				name:  'span_ssl',
				start: timing.secureConnectionStart - start,
				duration: timing.connectEnd - timing.secureConnectionStart,
			}
		);
	} else {
		analytics.metrics.push(
			{
				name:  'span_tcp',
				start: timing.connectStart - start,
				duration: timing.connectEnd - timing.connectStart,
			}
		);
	}
	analytics.metrics.push(
		{
			name:  'span_wait',
			start: timing.requestStart - start,
			duration: timing.responseStart - timing.requestStart,
		},
		{
			name:  'span_download',
			start: timing.responseStart - start,
			duration: timing.responseEnd - timing.responseStart,
		},
		{
			name:  'load',
			value: timing.duration,
		},
		{
			name:  'redirect',
			value: timing.redirectCount,
		},
	);
	if ( 0 < timing.transferSize) {
		analytics.metrics.push(
			{
				name:  'size',
				value: timing.transferSize,
			},
		);
	} else {
		analytics.metrics.push(
			{
				name:  'cache',
				value: 1,
			}
		);
	}
	sendAnalytics( analytics );
}

function webVitalsObserve() {
	getCLS( webVitalsReport );
	getFID( webVitalsReport );
	getLCP( webVitalsReport );
	getTTFB( webVitalsReport );
	getFCP( webVitalsReport );
}

function navigationObserve(list, observer) {
	if ( 0 < list.getEntriesByType( 'navigation' ).length ) {
		performanceReport( list.getEntriesByType( 'navigation' )[0],'navigation' )
	}
}
function resourceObserve(list, observer) {
	if (0 < list.getEntriesByType( 'resource' ).length) {
		list.getEntriesByType( 'resource' ).forEach(
			function(timing){
				if ( ( ! excluded.includes( timing.name ) ) && analyticsSettings.sampling >= getRandomArbitrary( 1, 1000 ) ) {
					excluded.push( timing.name );
					performanceReport( timing,'resource' );
				}
			}
		);
	}
}

try {
	webVitalsObserve();
	excluded.push( analyticsSettings.restUrl );
	let navigationObserver = new PerformanceObserver( navigationObserve );
	navigationObserver.observe( { entryTypes: ['navigation'] } );
	let resourceObserver = new PerformanceObserver( resourceObserve );
	resourceObserver.observe( { entryTypes: ['resource'] } );
} catch (error) {
	console.error( 'Vibes error: ' . error );
}
