const Path = require('path');
const { CssWebpackConfig } = require('@silverstripe/webpack-config');

const PATHS = {
  ROOT: Path.resolve(),
  SRC: Path.resolve('client/src'),
  DIST: Path.resolve('client/dist'),
};

const config = [
  // sass to css
  new CssWebpackConfig('css', PATHS)
    .setEntry({
      sitewidecontentreport: `${PATHS.SRC}/styles/sitewidecontentreport.scss`,
    })
    .getConfig(),
];

module.exports = config;
