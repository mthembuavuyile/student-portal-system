// react-components/src/components/StudentCard.js

import React from 'react';

/**
 * StudentCard Component
 * 
 * A reusable component to display a summary of a single student's information.
 * It's a presentational component used by StudentList.
 *
 * @param {object} props - The properties passed to the component.
 * @param {object} props.student - The student object to display.
 * @param {function} props.onViewProfile - Handler to execute when the view profile button is clicked.
 */
const StudentCard = ({ student, onViewProfile }) => {
    
    // Event handler for the delete button (simulated)
    const handleDelete = () => {
        // In a real app, this would call a function passed down via props
        // to trigger an API call, e.g., props.onDelete(student.id)
        alert(`Simulating delete for student: ${student.full_name}`);
    };

    return (
        <div className="student-card">
            <div className="card-content">
                <h4 className="card-title">{student.full_name}</h4>
                <p className="card-subtitle">ID: {student.student_id_number}</p>
                <p className="card-info"><strong>Course:</strong> {student.course_of_study}</p>
            </div>
            <div className="card-actions">
                {/* The onViewProfile function is passed from the parent and called with the student's ID */}
                <button onClick={() => onViewProfile(student.id)} className="btn-primary">View Profile</button>
                <button onClick={handleDelete} className="btn-danger">Delete</button>
            </div>
        </div>
    );
};

export default StudentCard;