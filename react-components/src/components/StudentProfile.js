import React from 'react';

/**
 * StudentProfile Component
 * 
 * Displays the detailed profile of a single student.
 * This component is "presentational" as it simply displays the data passed to it.
 *
 * @param {object} props - The properties passed to the component.
 * @param {object} props.student - The student object containing all details.
 * @param {function} props.onBack - A function to call when the "back" button is clicked.
 */
const StudentProfile = ({ student, onBack }) => {
    // If no student data is provided, render a message.
    if (!student) {
        return <div>Please select a student to view their profile.</div>;
    }

    // A simple function to format dates for better readability.
    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    return (
        <div className="student-profile-card">
            <div className="profile-header">
                <img src="/assets/images/default-avatar.png" alt="Student Avatar" className="avatar" />
                <h2>{student.full_name}</h2>
                <span className="profile-status">{student.academic_status}</span>
            </div>
            <div className="profile-body">
                <h3>Personal Information</h3>
                <p><strong>Student ID:</strong> {student.student_id_number}</p>
                <p><strong>Email:</strong> {student.email}</p>
                <p><strong>Date of Birth:</strong> {formatDate(student.date_of_birth)}</p>
                
                <hr />

                <h3>Academic Information</h3>
                <p><strong>Course of Study:</strong> {student.course_of_study}</p>
                <p><strong>Enrollment Date:</strong> {formatDate(student.enrollment_date)}</p>
                <p><strong>Registration Timestamp:</strong> {new Date(student.registration_timestamp).toLocaleString('en-US')}</p>
            </div>
            <div className="profile-actions">
                <button onClick={onBack} className="btn-secondary">Back to List</button>
            </div>
        </div>
    );
};

export default StudentProfile;