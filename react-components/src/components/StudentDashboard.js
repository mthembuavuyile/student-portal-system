import React, { useState, useEffect } from 'react';
import StudentList from './StudentList'; // We will create this component next

function StudentDashboard() {
    // useState hook to manage the list of students
    const [students, setStudents] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);

    // useEffect hook to fetch data when the component loads
    useEffect(() => {
        // Fetch data from our mock API
        fetch('http://localhost:3001/api/students')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                setStudents(data);
                setIsLoading(false);
            })
            .catch(error => {
                setError(error.message);
                setIsLoading(false);
            });
    }, []); // The empty array [] means this effect runs only once

    if (isLoading) {
        return <div>Loading students...</div>;
    }

    if (error) {
        return <div>Error: {error}</div>;
    }

    return (
        <div className="dashboard-container">
            <h2>Student List</h2>
            <p>This data is being fetched from a Node.js mock API.</p>
            <StudentList students={students} />

            <div className="component-discussion">
              <h3>Functional vs. Class Components</h3>
              <p>
                We chose functional components with hooks (useState, useEffect) for this project.
                This is the modern standard in React and leads to more readable and concise code
                compared to older class-based components, which required constructors, lifecycle methods
                like `componentDidMount`, and the `this` keyword to manage state.
              </p>
            </div>
        </div>
    );
}

export default StudentDashboard;