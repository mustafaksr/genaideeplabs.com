const path = require('path');

module.exports = {
  mode: 'development',
  entry: './js/gemini.js',
  output: {
    filename: './bundle.js',
    path: path.resolve(__dirname, 'js')
  },
  plugins: [
  
  ],
  externals: {
    marked: 'marked' // Specify 'marked' as an external module
  }
};