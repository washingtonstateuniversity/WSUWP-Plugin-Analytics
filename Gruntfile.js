/* globals module */
module.exports = function( grunt ) {

	// Project configuration
	grunt.initConfig( {
		pkg: grunt.file.readJSON( "package.json" ),
		uglify: {
			options: {
				banner: "/*! <%= pkg.name %> */\n"
			},
			build: {
				src: "js/analytics.js",
				dest: "js/analytics.min.js"
			}
		},
		phpcs: {
			plugin: {
				src: "./"
			},
			options: {
				bin: "vendor/bin/phpcs --extensions=php --ignore=\"*/vendor/*,*/node_modules/*\"",
				standard: "phpcs.ruleset.xml"
			}
		},
		jshint: {
			files: [ "js/analytics.js", "js/default_events.js", "js/default_ui-events.js", "js/mediaelement-events.js", "Gruntfile.js" ],
			options: {
				bitwise: true,
				curly: true,
				eqeqeq: true,
				forin: true,
				freeze: true,
				noarg: true,
				nonbsp: true,
				quotmark: "double",
				undef: true,
				unused: true,
				browser: true, // Define globals exposed by modern browsers.
				jquery: true   // Define globals exposed by jQuery.
			}
		},
		jscs: {
			files: [ "js/analytics.js", "js/default_events.js", "js/default_ui-events.js", "js/mediaelement-events.js", "Gruntfile.js" ],
			options: {
				preset: "jquery",
				fix: false,
				verbose: true,                                 // Display the rule name with the warning.
				requireCamelCaseOrUpperCaseIdentifiers: false, // We rely on name_name too much to change them all.
				maximumLineLength: 250                         // Temporary
			}
		}
	} );

	grunt.loadNpmTasks( "grunt-contrib-uglify" );
	grunt.loadNpmTasks( "grunt-contrib-jshint" );
	grunt.loadNpmTasks( "grunt-jscs" );
	grunt.loadNpmTasks( "grunt-phpcs" );

	// Default task(s).
	grunt.registerTask( "dev", [ "phpcs", "jscs", "jshint" ] );
	grunt.registerTask( "default", [ "phpcs", "jscs", "jshint", "uglify" ] );
};
