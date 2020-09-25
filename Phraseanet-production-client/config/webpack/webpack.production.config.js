require('babel-core/register');
// Webpack config for creating the production bundle.

const webpack = require('webpack');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const path = require('path');
const WebpackNotifierPlugin = require('webpack-notifier');
const PKG_LOCATION = require(path.join(__dirname, '../../package.json'));
const config = require('../config');
const webpackConfig = require('./webpack.development.config');
// add loader for external stylesheets:
var extractCSS = new ExtractTextPlugin({
  filename: '[name].css',
  allChunks: true
});

module.exports = Object.assign({}, webpackConfig, {

    cache: false,
    devtool: false,
    watch: false,
    module: {
        rules: [
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
          //     loaders: ['style', 'css', 'resolve-url', 'sass']
          // },
          // only skins are extracted as external file in dev env:
          {
              test: /\.scss$/,
              // exclude: /src\/(?!skins)/,
              // include: [path.join(__dirname, '../../src'), path.join(__dirname, '../../stylesheets')],
              use: ExtractTextPlugin.extract({
                use: [
                  'css-loader',
                  'resolve-url-loader',
                  { loader: 'sass-loader', options: { sourceMap: true } }
                ],
                publicPath: './'
              })
          },
          {
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
    plugins: [
        new webpack.ProvidePlugin({
            $: "jquery",
            jQuery: "jquery",
            "videojs": "video.js",
            "window.videojs": "video.js"
        }),
        // Notifier
        new WebpackNotifierPlugin({
            title: PKG_LOCATION.name,
            alwaysNotify: true
        }),
        // optimizations
        new webpack.NoEmitOnErrorsPlugin(),
        new webpack.DefinePlugin({
            '__DEV__': false,
            'process.env.NODE_ENV': JSON.stringify('production'),
            VERSION: JSON.stringify(PKG_LOCATION.version)
        }),
        new webpack.optimize.CommonsChunkPlugin({
            name: 'commons',
            chunks: ['production', 'lightbox'],
            minChunks: 2
        }),
        new webpack.LoaderOptionsPlugin({
          debug: true
        }),
        extractCSS
    ],
    devServer: {
      hot: true
    }
});
