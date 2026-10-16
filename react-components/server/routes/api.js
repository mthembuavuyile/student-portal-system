// react-components/server/routes/api.js

const express = require('express');
const router = express.Router();

// --- Mock Database ---
// In a real application, this data would come from your MySQL database.
// We are simulating it here with a simple array of objects.
const mockStudents = [
    {
        id: 1,
        full_name: 'Avuyile Mthembu (from React)',
        student_id_number: '10ha2304014',
        email: 'avuyile.react@example.com',
        date_of_birth: '2005-04-20',
        course_of_study: 'Systems Development',
        enrollment_date: '2023-02-20',
        academic_status: 'Active',
        registration_timestamp: new Date().toISOString()
    },
    {
        id: 2,
        full_name: 'Jane Smith (from React)',
        student_id_number: 'ST1012',
        email: 'jane.smith.react@example.com',
        date_of_birth: '2002-08-22',
        course_of_study: 'Information Technology',
        enrollment_date: '2024-02-21',
        academic_status: 'Active',
        registration_timestamp: new Date().toISOString()
    },
    {
        id: 3,
        full_name: 'John Doe (from React)',
        student_id_number: 'ST1013',
        email: 'john.doe.react@example.com',
        date_of_birth: '2001-05-15',
        course_of_study: 'Computer Science',
        enrollment_date: '2024-01-10',
        academic_status: 'Pending',
        registration_timestamp: new Date().toISOString()
    }
];

// --- API Endpoints ---

// GET /api/students
// Description: Returns a list of all students.
router.get('/students', (req, res) => {
    // Simulate a network delay for realism
    setTimeout(() => {
        res.json(mockStudents);
    }, 500); // 500ms delay
});

// GET /api/students/:id
// Description: Returns a single student by their ID.
router.get('/students/:id', (req, res) => {
    const studentId = parseInt(req.params.id, 10);
    const student = mockStudents.find(s => s.id === studentId);

    if (student) {
        res.json(student);
    } else {
        res.status(404).json({ message: 'Student not found' });
    }
});

// POST /api/students
// Description: Simulates creating a new student.
router.post('/students', (req, res) => {
    const newStudentData = req.body;
    const newStudent = {
        id: mockStudents.length + 1, // Generate a new ID
        ...newStudentData,
        registration_timestamp: new Date().toISOString(),
        academic_status: 'Active'
    };
    
    mockStudents.push(newStudent);
    console.log("Added new student:", newStudent);
    
    // Respond with the newly created student data
    res.status(201).json(newStudent);
});


// Export the router so it can be used by server.js
module.exports = router;