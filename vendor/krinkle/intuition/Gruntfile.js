/**
 * @copyright 2011-2014 See AUTHORS.txt
 * @license CC-BY 3.0 <https://creativecommons.org/licenses/by/3.0/>
 * @package intuition
 */
/*jshint node:true */
module.exports = function (grunt) {
	grunt.loadNpmTasks('grunt-banana-checker');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-qunit');
	grunt.loadNpmTasks('grunt-git-authors');
	grunt.loadNpmTasks('grunt-jscs');
	grunt.loadNpmTasks('grunt-jsonlint');


	var fs = require('fs'),
		path = require('path'),
		msgDir = path.join(__dirname, 'language', 'messages'),
		domainDirs = {};
	fs.readdirSync(msgDir).forEach(function (file) {
		var stats = fs.statSync(path.join(msgDir, file));
		if (stats.isDirectory()) {
			domainDirs[file] = 'language/messages/' + file + '/';
		}
	});

	grunt.initConfig({
		banana: domainDirs,
		jshint: {
			options: {
				jshintrc: true
			},
			all: ['Gruntfile.js', 'includes/js-env/*.js']
		},
		jscs: {
			all: '<%= jshint.all %>'
		},
		jsonlint: {
			all: [
				'.jscsrc',
				'*.json',
				'{includes,language,tests}/**/*.json'
			]
		},
		qunit: {
			all: ['tests/qunit/index.html']
		}
	});

	grunt.registerTask('default', ['jshint', 'jscs', 'jsonlint', 'qunit']);
};
