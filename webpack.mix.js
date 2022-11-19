const mix = require('laravel-mix');
const path = require('path');
//const webpack = require("webpack");
require('dotenv').config();



mix.browserSync({
	open: false,
	port: process.env.APP_PORT,
	proxy: process.env.APP_URL,
	notify: false,
	ignored: /node_modules/,
	files: [
		'./resources/views/**/*.blade.php',
		'./resources/js/**/*.js',
		'./public/assets/css/site.css',
		'./public/assets/css/admin.css',
		'./public/assets/css/app.css'
	]
});



mix
	.webpackConfig({
		resolve: {
			alias: {
				'@': path.resolve(__dirname, 'resources/js'),
				'@plugins': path.resolve(__dirname, 'resources/js/plugins'),
				'@sass': path.resolve(__dirname, 'resources/sass'),
				'@fonts': path.resolve(__dirname, 'resources/fonts'),
			}
		}
	})
	.sass('resources/sass/common/app.scss', 'public/assets/css')
	.sass('resources/sass/admin.sass', 'public/assets/css')
	.sass('resources/sass/site.sass', 'public/assets/css')
	.options({
		processCssUrls: false,
		autoprefixer: {
			options: {
				browsers: ['last 3 versions']
			}
		}
	})
	//.copy('resources/js/plugins', 'public/assets/js/plugins')
	//.copy('resources/images', 'public/assets/images')
	//.copy('resources/fonts', 'public/assets/fonts')
	.js('resources/js/admin.js', 'public/assets/js')
	.js('resources/js/site.js', 'public/assets/js')
	.disableNotifications()
	.disableSuccessNotifications()
	.version();