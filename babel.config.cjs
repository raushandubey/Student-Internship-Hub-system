/**
 * Babel Configuration for Jest Tests
 * 
 * Enables ES module support in Jest tests
 */

module.exports = {
    presets: [
        ['@babel/preset-env', {
            targets: {
                node: 'current'
            }
        }]
    ]
};
