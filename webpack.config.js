const fs = require("fs");
const path = require("path");
const IgnoreEmitPlugin = require("ignore-emit-webpack-plugin");
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const { textdomain } = JSON.parse(fs.readFileSync("./package.json"));

module.exports = {
  ...defaultConfig,
  entry: {
    index: path.resolve(process.cwd(), "src", "index.js"),
  },
  plugins: [...defaultConfig.plugins],
};
