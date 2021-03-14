// .versionrc.js
const tracker = {
    filename: 'plugin.info.txt',
    updater: require('./build/pluginInfoVersionUpdater')
};

module.exports = {
    bumpFiles: [tracker],
};
