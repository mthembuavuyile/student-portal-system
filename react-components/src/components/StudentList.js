// react-components/src/components/StudentList.js

import React from 'react';
import StudentCard from './StudentCard'; // Import the child component

/**
 * StudentList Component
 * 
 * Renders a list of StudentCard components based on an array of students.
 *
 * @param {object} props - The properties passed to the component.
 * @param {Array<object>} props.students - An array of student objects.
 * @param {function} props.onViewProfile - A handler function to be called when a "View" button is clicked.
 */
const StudentList = ({ students, onViewProfile }) => {
    // If there are no students, display a message.
    if (!students || students.length === 0) {
        return <p>No student records found.</p>;
    }

    return (
        <div className="student-list-container">
            {students.map(student => (
                // Use the student's unique ID as the key for efficient rendering.
                <StudentCard 
                    key={student.id} 
                    student={student} 
                    onViewProfile={onViewProfile} 
                />
            ))}
        </div>
    );
};

export default StudentList;