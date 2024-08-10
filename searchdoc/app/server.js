const express = require('express');
const app = express();
const path = require('path');

// Serve static files
app.use(express.static(path.join(__dirname, 'public')));

// Start the server
const PORT = process.env.PORT || 3000;
const HOST = '127.0.0.1'; // Change this to your desired IP address
app.listen(PORT, HOST, () => {
  console.log(`Server is running at http://${HOST}:${PORT}`);
});
