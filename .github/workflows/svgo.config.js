// svgo.config.js
module.exports = {
  multipass: true, // boolean. false by default
  js2svg: {
    indent: 4, // string with spaces or number of spaces. 4 by default
    pretty: false, // boolean, false by default
  },
  plugins: [
    // set of built-in plugins enabled by default
    'preset-default',

    // enable built-in plugins by name
    'removeDimensions',

    // or by expanded notation which allows to configure plugin
    {
      name: 'sortAttrs',
      params: {
        xmlnsOrder: 'alphabetical',
      },
    },
  ],
};