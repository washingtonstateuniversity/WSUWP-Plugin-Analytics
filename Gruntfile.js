/* globals module */
module.exports = function( grunt ) {

	// Project configuration
	grunt.initConfig( {
		pkg: grunt.file.readJSON( "package.json" ),

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
			files: [ "Gruntfile.js" ],
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
			files: [ "Gruntfile.js" ],
			options: {
				preset: "jquery",
				fix: false,
				requireCamelCaseOrUpperCaseIdentifiers: false, // We rely on name_name too much to change them all.
				maximumLineLength: 250                         // Temporary
			}
		}
	} );

	grunt.loadNpmTasks( "grunt-contrib-jshint" );
	grunt.loadNpmTasks( "grunt-jscs" );
	grunt.loadNpmTasks( "grunt-phpcs" );

	// Default task(s).
	grunt.registerTask( "default", [ "phpcs", "jscs", "jshint" ] );
};
