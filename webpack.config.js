const globby = require("globby");
const commonConfig = require('../../../../webpack.common.config');

// Get All Paths + Entries
const paths = globby.sync(`${__dirname}/js/**/*.js`);
const entries = {};
paths.forEach((item, k) => {
  entries[item.replace(`${__dirname}`, "")] = item;
});

const config = (_env, {
    mode,
    watch,
  }) => {
  const IS_PROD = mode.toLowerCase() === `production`;
  return commonConfig(_env, IS_PROD, watch, entries, __dirname);
};

module.exports = config;
