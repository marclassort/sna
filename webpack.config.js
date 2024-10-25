const path = require('path');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = {
    mode: 'production',
    entry: {
        theme: './public/assets/js/theme.js',
        plugins: './public/assets/js/plugins.js'
    },
    output: {
        filename: '[name].min.js',
        path: path.resolve(__dirname, 'public/js'),
    },
    optimization: {
        minimize: true,
        minimizer: [new TerserPlugin()],
    },
    resolve: {
        alias: {
            jquery: 'jquery'
        }
    },
    externals: {
        jquery: 'jQuery'
    },
};
