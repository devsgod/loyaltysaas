const mix = require('laravel-mix');
const webpack = require('webpack')

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
  // App
  .js('resources/js/app/app.js', 'public/js/app.js')
  .sass('resources/sass/app/app.scss', 'public/css/app.css')

  // Campaign
  .js('resources/js/campaign/app.js', 'public/js/campaign.js')
  .sass('resources/sass/campaign/app.scss', 'public/css/campaign.css')

  // Staff
  .js('resources/js/staff/app.js', 'public/js/staff.js')
  .sass('resources/sass/staff/app.scss', 'public/css/staff.css')

  // Website
  .js('resources/js/site/app.js', 'public/js/site.js')
  .sass('resources/sass/site/app.scss', 'public/css/site.css')

  .extract([
    'babel-polyfill',
    'vue', 
    'lodash', 
    'vuetify', 
    'pusher-js', 
    'js-cookie', 
    'moment',
    'es6-promise',
    'nprogress',
    'vee-validate',
    'vue-axios',
    'vue-i18n',
    'vue-qrcode-component',
    'vue-router',
    'vuex',
    'vue-template-compiler',
    'vue-analytics',
    'vue-the-mask'
    ])
  /*.sourceMaps()*/;