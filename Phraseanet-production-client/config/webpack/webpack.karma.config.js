// Webpack config for development
import webpack from 'webpack';
import path from 'path';
import config from '../config';
const ExtractTextPlugin = require('extract-text-webpack-plugin');

// add loader for external stylesheets:
var extractCSS = new ExtractTextPlugin({
  filename: '[name].css',
  allChunks: true
});

module.exports = {
    cache: true,
    devtool: 'inline-source-map',
    output: {},
    module: {
      rules: [
        {
          test: /\.js$/,
          enforce: 'post',
          include: path.resolve('src/'),
          use: 'istanbul-instrumenter-loader'
        },
        {
          test: /\.css$/,
          use: [
            'style-loader',
            'css-loader'
          ]
        },
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
          test: /\.js$/,
          exclude: /node_modules/,
          use: [{
            loader: 'babel-loader',
            options: { presets: ['es2015', 'stage-0'] },
          }],
        },
        {
            test: require.resolve('jquery-lazyload'),
            use: "imports-loader?this=>window"
        }
      ]
    },
    resolve: {
        extensions: ['*', '.js', '.css']
    },
    externals: {
        jquery: 'jQuery',
        ui: 'jQuery.ui'
    },
    plugins: [
        new webpack.NormalModuleReplacementPlugin(/\.css$/, path.resolve('./src', './empty.js')),
        new webpack.LoaderOptionsPlugin({
          debug: true
        }),
        extractCSS
    ],
    devServer: {
      hot: true
    }
};
