const webpack = require('webpack');
const config = require('./webpack.config');
const _ = require('lodash');
const ExtractTextPlugin = require("extract-text-webpack-plugin");

config.devtool = 'eval';
config.devServer = {
  inline: true,
  hot: true,
  host: "0.0.0.0",
  port: "8081",
  https: true,
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
      },
      {
        loader: 'less-loader',
        options: {
          globalVars: {
            imageUrl: "'https://class-central.test/bundles/classcentralsite/images/'",
            assetUrl: "'https://class-central.test/bundles/classcentralsite/slashpixel/images/'"
          }
        }
      },
    ]
  })
});
config.output = {
  path: __dirname + '/web/webpack/development',
  filename: '[name].dev.js',
};
config.plugins = _.union(config.plugins, [
  new webpack.HotModuleReplacementPlugin(),
  new ExtractTextPlugin('[name].dev.css')
]);

module.exports = config;
