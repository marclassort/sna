const path = require('path');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');

module.exports = {
    entry: './public/assets/js/theme.js',
    output: {
        filename: 'theme.min.js',
        path: path.resolve(__dirname, 'public/js'),
    },
    optimization: {
        minimizer: [new UglifyJsPlugin()],
    },
};
