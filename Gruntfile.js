module.exports = function (grunt) {
	grunt.initConfig(
		{
			version: {
				project: {
					src: [
					'package.json'
					]
				},
				composer: {
					src: [
					'composer.json'
					]
				},
				load_php: {
					options: {
						prefix: '\\.*\\VERSION\.*\\s=\.*\\s\''
					},
					src: [
					'class-themeisle-onboarding.php'
					]
				},
			},
		}
	);
	grunt.loadNpmTasks( 'grunt-version' );
};
