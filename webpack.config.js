const webpack = require("webpack");
const ModernizrWebpackPlugin = require('modernizr-webpack-plugin');

module.exports = {
  context: __dirname + '/src/ClassCentral/SiteBundle/Resources/public/client',
  entry: {
    'cc': './CC.js',
    'analytics': './Analytics.js',
    'cc-style': './Style.less',
    'cc-search': './Search.js',
  },
  resolve: {
    alias: {
      'handlebars' : 'handlebars/dist/handlebars.js'
    },
    extensions: ['.js', '.jsx', '.es6', '.less', '.css'],
    modules: ['node_modules', 'client']
  },
  module: {
    rules: [
      {
        test: /modernizr/,
        loader: 'imports-loader?this=>window!exports-loader?window.Modernizr'
      },
      {
        test:  /flexslider/,
        loader: 'imports-loader?this=>window!exports-loader?window.jQuery'
      },
      {
        test: /classcentral/,
        loader: 'imports-loader?this=>window!exports-loader?window.loadRaty!exports-loader?window.courseListCheckboxHandler'
      },
      {
        test: /Keen/,
        loader: 'imports-loader?this=>window'
      },
      {
        test: /packages\/analytics\/Analytics/,
        loader: 'imports-loader?this=>window'
      },
      {
        test: __dirname + 'node_modules/blueimp-file-upload/js/vendor/jquery.ui.widget.js',
        loader: 'imports-loader?define=>false&exports=>false!blueimp-file-upload/js/vendor/jquery.ui.widget.js'
      },
      {
        test: __dirname + 'node_modules/blueimp-file-upload/js/jquery.iframe-transport.js',
        loader: 'imports-loader?define=>false&exports=>false!blueimp-file-upload/js/jquery.iframe-transport.js'
      },
      {
        test: __dirname + 'node_modules/blueimp-file-upload/js/jquery.fileupload.js',
        loader: 'imports-loader?define=>false&exports=>false!blueimp-file-upload/js/jquery.fileupload.js'
      },
      {
        test: require.resolve('js-cookie'),
        loader: 'expose-loader?Cookies'
      },
      {
        test: require.resolve('ismobilejs'),
        loader: 'expose-loader?isMobile'
      },
      {
        test: require.resolve('countup.js'),
        loader: 'expose-loader?countUp'
      },
      {
        test: require.resolve('jquery'),
        loader: 'expose-loader?jQuery!expose-loader?$'
      },
      {
        test: /^cc\.js$/,
        loader: 'expose-loader?CC'
      },
      {
        test: [
          /\.jsx?$/,
          /\.es6?$/
        ],
        exclude: /(node_modules|server)/,
        use: {
          loader: 'babel-loader',
        }
      }
    ]
  },
  plugins: [
    new webpack.ProvidePlugin({
        $: "jquery",
        jQuery: "jquery"
    }),
    new ModernizrWebpackPlugin({
      filename: 'modernizr.js',
      "options": [
        "html5shiv",
        "prefixes",
        "testStyles"
      ],
      "feature-detects": [
        "forms/formattribute",
        "forms/placeholder",
        "inputtypes",
        "svg",
        "svg/clippaths",
        "svg/inline"
      ]
    })
  ]
};
