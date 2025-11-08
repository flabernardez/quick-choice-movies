const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        'game-block/index': path.resolve(process.cwd(), 'src/game-block', 'index.js'),
        'meta-fields/index': path.resolve(process.cwd(), 'src/meta-fields', 'index.js'),
    },
    output: {
        filename: '[name].js',
        path: path.resolve(process.cwd(), 'build'),
    },
};
