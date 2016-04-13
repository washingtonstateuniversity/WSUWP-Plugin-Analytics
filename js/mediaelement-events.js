_wpmejsSettings.success = function( mejs ) {
		if ( "flash" === mejs.pluginType ) {

			// Autoplay
			if ( mejs.attributes.autoplay && "false" !== mejs.attributes.autoplay ) {
				mejs.addEventListener( "canplay", function() {
					mejs.play();
				}, false );
			}

			// Loop
			if ( mejs.attributes.loop && "false" !== mejs.attributes.loop ) {
				mejs.addEventListener( "ended", function() {
					mejs.play();
				}, false );
			}
		}

		if ( typeof jQuery.jtrack !== "undefined" ) {

			// Event listener for when the video starts playing
			mejs.addEventListener( "playing", function() {
				jQuery( "body" ).trackEvent( mejs.className, "playing", mejs.currentTime, 0, "_wsuGA", "siteScope" );
			}, false );

			// Event listener for when the video is paused
			mejs.addEventListener( "pause", function() {
				jQuery( "body" ).trackEvent( mejs.className, "pausing", mejs.currentTime, 0, "_wsuGA", "siteScope" );
			}, false );

			// Event listener for when the video ends
			mejs.addEventListener( "ended", function() {
				jQuery( "body" ).trackEvent( mejs.className, "ending", mejs.currentTime, 0, "_wsuGA", "siteScope" );
			}, false );
		}
	};
