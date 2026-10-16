// react-components/server/server.js

const express = require('express');
const cors = require('cors');
const app = express();
const PORT = 3001; // Port for our mock API

app.use(cors()); // Allow requests from our React app

// Sample student data to act as our database
const students = [
    { id: 1, fullName: "Avuyile Mthembu React", studentId: "10ha2304014", course: "Systems Development", status: "Active" },
    { id: 2, fullName: "Jane Smith React", studentId: "ST1012", course: "Information Technology", status: "Active" },
    { id: 3, fullName: "John Doe React", studentId: "ST1013", course: "Computer Science", status: "Pending" }
];

// Define an API endpoint to get all students
app.get('/api/students', (req, res) => {
    res.json(students);
});

app.listen(PORT, () => {
    console.log(`Mock API server running on http://localhost:${PORT}`);
});