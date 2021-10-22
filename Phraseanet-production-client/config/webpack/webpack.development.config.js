/**
 * WEBPACK CONFIG
 *
 */
/* eslint-disable no-var */
require('babel-core/register');

// Webpack config for development

const webpack = require('webpack');
const path = require('path');
const pkg = require('../../package.json');
// const banner = require('../banner');
const WebpackNotifierPlugin = require('webpack-notifier');
const config = require('../config');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

// add loader for external stylesheets:
var extractCSS = new ExtractTextPlugin({
  filename: '[name].css',
  allChunks: true
});

module.exports = {
    // entry points
    entry: {
        production: config.sourceDir + 'prod/index.js',
        lightbox: config.sourceDir + 'lightbox/index.js',
        'lightbox-mobile': config.sourceDir + 'lightbox-mobile/index.js',
        permaview: config.sourceDir + 'permaview/index.js',
        authenticate: [config.sourceDir + 'authenticate/index.js'],
        account: [config.sourceDir + 'account/index.js'],
        'skin-000000': [config.sourceDir + 'skins/skin-000000.js'],
        'skin-959595': [config.sourceDir + 'skins/skin-959595.js'],
        'skin-FFFFFF': [config.sourceDir + 'skins/skin-FFFFFF.js']
    },
    cache: true,
    watch: true,
    devtool: 'inline-source-map',
    output: {
        path: config.distDir,
        filename: '[name].js',
        chunkFilename: 'lazy-[name].js?v=' + config.assetFileVersion,
        libraryTarget: 'umd',
        library: config._app,
        publicPath: '/assets/production/'
    },
    module: {
        rules: [
          {
            test: /\.js$/,
            enforce: 'pre',
            loader: 'eslint-loader',
            exclude: /node_modules/,
            include: path.join(__dirname, '../../src')
          },
          {
            test: /\.js$/,
            exclude: /node_modules/,
            use: [{
              loader: 'babel-loader',
              options: { presets: ['es2015', 'stage-0'] },
            }],
          },
          {
              test: /\.(ttf|eot|woff|svg|png|jpg|gif)$/,
              use: [
                {
                  loader: 'url-loader',
                  options: {
                    limit: 10000,
                    name: '[name].[hash:6].[ext]',
                  }
                }
              ],
              exclude: /node_modules/
          },
          {
              test: /\.(ttf|eot|woff|svg|png|jpg|jpeg|gif)$/,
              use: [
                {
                  loader: 'file-loader',
                  options: {
                    name: '[name].[hash:6].[ext]'
                  }
                }
              ]
          },
          {
              test: /\.css$/,
              use: [
                "style-loader",
                "css-loader"
              ]
          },
          {
                test: /videojs-flash\.js$/,
                loader: 'script-loader'
          },
          // exclude skins as inline-css in dev env
          // {
          //     test: /\.scss$/,
          //     exclude: /src\/skins\//,
          //     loaders: ['style', 'css', 'resolve-url', 'sass?sourceMap']
          // },
          // only skins are extracted as external file in dev env:
          // every css should be exported as file in dev env
          {
              test: /\.scss$/,
              // exclude: /src\/(?!skins)/,
              // include: [path.join(__dirname, '../../src'), path.join(__dirname, '../../stylesheets')],
              use: ExtractTextPlugin.extract({
                fallback: "style-loader",
                use: [
                  'css-loader',
                  'resolve-url-loader',
                  { loader: 'sass-loader', options: { sourceMap: true } }
                ],
                publicPath: './'
              })
          },{
              test: require.resolve('jquery-lazyload'),
              use: "imports-loader?this=>window"
          }, {
              test: require.resolve('geonames-server-jquery-plugin/jquery.geonames'),
              use: "imports-loader?this=>window"
          }, {
              test: require.resolve('bootstrap-multiselect'),
              use: "imports-loader?this=>window"
          }
        ]
    },
    resolve: {
        extensions: ['*', '.js', '.css', '.scss']
    },
    plugins: [
        new WebpackNotifierPlugin({
            alwaysNotify: true
        }),
        // new webpack.BannerPlugin(banner),
        new webpack.ProvidePlugin({
            "videojs": "video.js",
            "window.videojs": "video.js"
        }),
        new webpack.DefinePlugin({
            '__DEV__': true,
            'process.env.NODE_ENV': JSON.stringify('development'),
            VERSION: JSON.stringify(pkg.version)
        }),
        new webpack.optimize.CommonsChunkPlugin({
            name: 'commons',
            chunks: ['production', 'lightbox'],
            minChunks: 2
        }),
        new webpack.LoaderOptionsPlugin({
          debug: true,
          options: {
            eslint: {
              configFile: config.eslintDir
            }
          }
        }),
        extractCSS
        // i18next
    ],
    externals: {
        jquery: 'jQuery',
        ui: 'jQuery.ui'
    }
};
