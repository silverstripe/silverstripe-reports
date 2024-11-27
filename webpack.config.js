const Path = require('path');
const { JavascriptWebpackConfig, CssWebpackConfig } = require('@silverstripe/webpack-config');

const PATHS = {
  ROOT: Path.resolve(),
  SRC: Path.resolve('client/src'),
  DIST: Path.resolve('client/dist'),
};

const config = [
  // Main JS bundle
  new JavascriptWebpackConfig('js', PATHS, 'silverstripe/reports')
    .setEntry({
      BrokenExternalLinksReport: `${PATHS.SRC}/js/BrokenExternalLinksReport.js`,
      ReportAdmin: `${PATHS.SRC}/js/ReportAdmin.js`,
      'ReportAdmin.Tree': `${PATHS.SRC}/js/ReportAdmin.Tree.js`,
    })
    .getConfig(),
  // sass to css
  new CssWebpackConfig('css', PATHS)
    .setEntry({
      BrokenExternalLinksReport: `${PATHS.SRC}/styles/BrokenExternalLinksReport.scss`,
      sitewidecontentreport: `${PATHS.SRC}/styles/sitewidecontentreport.scss`,
    })
    .getConfig(),
];

module.exports = config;
