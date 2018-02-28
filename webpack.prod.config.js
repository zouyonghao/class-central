const webpack = require('webpack');
const config = require('./webpack.config');
const ManifestPlugin = require('webpack-manifest-plugin');
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const CleanWebpackPlugin = require('clean-webpack-plugin');
const _ = require('lodash');

config.devtool = 'cheap-module-source-map';
config.output = {
  path: __dirname + '/web/webpack',
  filename: '[name].[chunkhash].js',
};
config.module.rules.push({
  test: [
    /\.less$/,
    /\.css$/
  ],
  use: ExtractTextPlugin.extract({
    fallback: "style-loader",
    use: [
      {
        loader: 'css-loader',
        options: {
          minimize: true,
          sourceMap: true,
        }
      },
      {
        loader: 'less-loader',
        options: {
          globalVars: {
            imageUrl: "'/bundles/classcentralsite/images/'",
            assetUrl: "'/bundles/classcentralsite/slashpixel/images/'"
          }
        }
      }
    ]
  })
});
config.plugins = _.union(config.plugins, [
  new CleanWebpackPlugin(['web/webpack'], {
    root: __dirname,
    verbose: true,
    dry: false,
    exclude: ['manifest.dev.json']
  }),
  new ManifestPlugin({
    fileName: 'manifest.prod.json',
  }),
  new webpack.DefinePlugin({
    'process.env': {
      'NODE_ENV': JSON.stringify('production')
    }
  }),
  new ExtractTextPlugin('[name].[contenthash].css'),
  new webpack.optimize.UglifyJsPlugin({
    sourceMap: true,
    output: {
      comments: false,
    },
    exclude: [/\.min\.js$/gi]
  }),
  new webpack.optimize.AggressiveMergingPlugin(),
]);

module.exports = config;
