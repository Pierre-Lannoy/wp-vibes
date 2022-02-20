import {getCLS, getFID, getLCP, getTTFB, getFCP} from './we-vi/we-vi.js'

let excluded = [];
let buffer   = [];
let sending  = false;

function getRandomArbitrary(min, max) {
	return Math.random() * (max - min) + min;
}

function sendAnalytics(metrics) {
	if ( navigator.sendBeacon ) {
		navigator.sendBeacon( analyticsSettings.restUrl, JSON.stringify( metrics ) );
	}
}

function sendBuffer() {
	sending = true;
	if ( 0 < buffer.length ) {
		sendAnalytics(
			{
				type: 'multi',
				metrics: buffer
			}
		);
		buffer = [];
	}
	sending = false;
}

function bufferizeAnalytics(metrics) {
	buffer.push( metrics );
}

function webVitalsReport({name, delta, value, id}) {
	let analytics = {
		type: 'webvital',
		resource: location ? location.href : '-',
		authenticated: analyticsSettings.authenticated,
		metrics: [{
			name: name,
			value: value,
		}]
	}
	if ( '0' === analyticsSettings.multiMetrics ) {
		sendAnalytics( analytics );
	} else {
		bufferizeAnalytics( analytics );
	}
}

function performanceReport(timing,type) {
	let start     = timing.startTime > 0 ? timing.startTime : 0;
	start         = timing.workerStart > 0 ? timing.workerStart : start;
	let analytics = {
		type: type,
		resource: timing.name,
		authenticated: analyticsSettings.authenticated,
		initiator: timing.initiatorType,
		metrics: [{
			name: 'redirect',
			start: timing.redirectStart > start ? timing.redirectStart - start : 0,
			duration: timing.redirectEnd - timing.redirectStart,
		},{
			name: 'dns',
			start: timing.domainLookupStart > start ? timing.domainLookupStart - start : 0,
			duration: timing.domainLookupEnd - timing.domainLookupStart,
		}]};
	if ( 0 < timing.secureConnectionStart) {
		analytics.metrics.push(
			{
				name: 'tcp',
				start: timing.connectStart > start ? timing.connectStart - start : 0,
				duration: timing.connectEnd - timing.connectStart,
			},
			{
				name: 'ssl',
				start: timing.secureConnectionStart > start ? timing.secureConnectionStart - start : 0,
				duration: timing.connectEnd - timing.secureConnectionStart,
			}
		);
	} else {
		analytics.metrics.push(
			{
				name: 'tcp',
				start: timing.connectStart > start ? timing.connectStart - start : 0,
				duration: timing.connectEnd - timing.connectStart,
			}
		);
	}
	analytics.metrics.push(
		{
			name: 'redirects',
			value: timing.redirectCount ? timing.redirectCount : 0,
		},
	);
	if ( 0 < timing.transferSize) {
		analytics.metrics.push(
			{
				name: 'wait',
				start: timing.requestStart > start ? timing.requestStart - start : timing.requestStart,
				duration: timing.responseStart - timing.requestStart,
			},
			{
				name: 'download',
				start: timing.responseStart > start ? timing.responseStart - start : timing.responseStart,
				duration: timing.responseEnd - timing.responseStart,
			},
			{
				name: 'load',
				value: timing.duration,
			},
			{
				name: 'size',
				value: timing.transferSize,
			},
		);
	} else {
		analytics.metrics.push(
			{
				name: 'wait',
				start: timing.connectEnd,
				duration: 0,
			},
			{
				name: 'download',
				start: timing.connectEnd,
				duration: 0,
			},
			{
				name: 'load',
				value: 0,
			},
			{
				name: 'cache',
				value: 1,
			}
		);
	}
	if ( '0' === analyticsSettings.multiMetrics ) {
		sendAnalytics( analytics );
	} else {
		bufferizeAnalytics( analytics );
	}
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
				if ( ( 'xmlhttprequest' === timing.initiatorType || 'beacon' === timing.initiatorType || 'other' === timing.initiatorType ) && '1' === analyticsSettings.smartFilter && ( 0 < timing.name.indexOf( '/beacon' ) || 0 < timing.name.indexOf( '/livelog' ) ) ) {
					return;
				}
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
	let navigationObserver = new PerformanceObserver( navigationObserve );
	navigationObserver.observe( { entryTypes: ['navigation'] } );
	let resourceObserver = new PerformanceObserver( resourceObserve );
	resourceObserver.observe( { entryTypes: ['resource'] } );
	if ( '1' === analyticsSettings.multiMetrics ) {
		document.addEventListener(
			'visibilitychange',
			function logData() {
				if ( document.visibilityState === 'hidden' && ! sending) {
					sendBuffer();
				}
			}
		);
		window.addEventListener(
			'pagehide',
			event => {
				if ( ! sending ) {
					sendBuffer();
				}
			},
			false
		);
	}

} catch (error) {
	console.error( 'Vibes analytics error: ', error.message );
}
