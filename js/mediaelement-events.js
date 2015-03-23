_wpmejsSettings.success = function( mejs ) {
		//orginal default settings
		var autoplay, loop;

		if ( 'flash' === mejs.pluginType ) {
			autoplay = mejs.attributes.autoplay && 'false' !== mejs.attributes.autoplay;
			loop = mejs.attributes.loop && 'false' !== mejs.attributes.loop;

			autoplay && mejs.addEventListener( 'canplay', function () {
				mejs.play();
			}, false );

			loop && mejs.addEventListener( 'ended', function () {
				mejs.play();
			}, false );
		}

		if(typeof jQuery.jtrack !=="undefined"){
			/* we can do so much better then this, but it'll work for now
				just update to do more robust tracking as we can.
			*/
			
			// Event listener for when the video starts playing
			mejs.addEventListener( 'playing', function( e ) {
				jQuery('body').trackEvent ('Audio', 'playing', "time", mejs.currentTime, "_wsuGA", "siteScope" );
			}, false);

			// Event listener for when the video is paused
			mejs.addEventListener( 'pause', function( e ) {
				jQuery('body').trackEvent ('Audio', 'pausing', "time", mejs.currentTime, "_wsuGA", "siteScope" );
			}, false);

			// Event listener for when the video ends
			mejs.addEventListener( 'ended', function( e ) {
				jQuery('body').trackEvent ('Audio', 'ending', "time", mejs.currentTime, "_wsuGA", "siteScope" );
			}, false);
		}
	};