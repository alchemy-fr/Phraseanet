require('babel-core/register');

/**
 * Simple example on how environment variables can be used
 */

const config   = require('./config/environment').default;

module.exports = require('./config/webpack/webpack.' + config.ENVIRONMENT_NAME + '.config');
