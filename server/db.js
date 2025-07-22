const mysql = require("mysql2");

// Create a connection to MySQL database
const db = mysql.createConnection({
  host: "localhost",
  user: "root", // Change if your MySQL username is different
  password: "", // Change if you have a MySQL password
  database: "chat_app",
});

// Connect to MySQL
db.connect((err) => {
  if (err) {
    console.error("Database connection failed:", err);
  }
});

module.exports = db;
