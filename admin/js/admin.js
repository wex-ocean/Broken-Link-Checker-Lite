( function () {
	'use strict';

	var button = document.getElementById( 'blc-lite-scan-now' );
	var progress = document.getElementById( 'blc-lite-scan-progress' );

	if ( ! button || ! progress || typeof blcLiteScan === 'undefined' ) {
		return;
	}

	var progressBar = progress.querySelector( 'progress' );
	var label = progress.querySelector( '.blc-lite-scan-progress-label' );

	function scanBatch( offset ) {
		var data = new FormData();
		data.append( 'action', 'blc_lite_scan_progress' );
		data.append( 'nonce', blcLiteScan.nonce );
		data.append( 'offset', offset );

		fetch( blcLiteScan.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data } )
			.then( function ( response ) { return response.json(); } )
			.then( function ( response ) {
				if ( ! response.success ) {
					throw new Error( 'Scan failed' );
				}

				var result = response.data;
				var percent = result.total ? Math.min( 100, Math.round( result.next_offset / result.total * 100 ) ) : 100;
				progressBar.value = percent;
				label.textContent = percent + '%';

				if ( ! result.done ) {
					scanBatch( result.next_offset );
					return;
				}

				button.disabled = false;
				button.textContent = 'Scan Now';
				window.location.reload();
			} )
			.catch( function () {
				button.disabled = false;
				label.textContent = 'Scan failed';
			} );
	}

	button.addEventListener( 'click', function () {
		button.disabled = true;
		button.textContent = 'Scanning...';
		progress.hidden = false;
		progressBar.value = 0;
		label.textContent = '0%';
		scanBatch( 0 );
	} );
}() );