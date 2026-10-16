// react-components/src/components/StudentForm.js
import React, { useState } from 'react';

/**
 * StudentForm Component
 * 
 * A controlled form for registering a new student.
 * It uses the 'useState' hook to manage the state of each input field.
 */
const StudentForm = () => {
    // Initialize state for all form fields
    const [formData, setFormData] = useState({
        fullName: '',
        studentId: '',
        email: '',
        dateOfBirth: '',
        courseOfStudy: 'Systems Development', // Default value
        enrollmentDate: ''
    });

    const [message, setMessage] = useState('');

    // A single handler function to update the state for any input field change
    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prevState => ({
            ...prevState,
            [name]: value
        }));
    };

    // Handles the form submission
    const handleSubmit = (e) => {
        e.preventDefault(); // Prevent the default browser refresh

        // In a real application, you would send this data to your backend API
        // For example: api.registerStudent(formData).then(...)
        
        console.log("Form Submitted with data:", formData);

        // Provide user feedback
        setMessage(`Successfully registered student: ${formData.fullName}`);

        // Reset the form after submission
        setFormData({
            fullName: '',
            studentId: '',
            email: '',
            dateOfBirth: '',
            courseOfStudy: 'Systems Development',
            enrollmentDate: ''
        });
    };

    return (
        <div className="student-form-container">
            <h3>Register New Student (React Form)</h3>
            <form onSubmit={handleSubmit}>
                <div className="form-group">
                    <label htmlFor="fullName">Full Name</label>
                    <input type="text" id="fullName" name="fullName" value={formData.fullName} onChange={handleChange} required />
                </div>
                <div className="form-group">
                    <label htmlFor="studentId">Student ID</label>
                    <input type="text" id="studentId" name="studentId" value={formData.studentId} onChange={handleChange} required />
                </div>
                <div className="form-group">
                    <label htmlFor="email">Email Address</label>
                    <input type="email" id="email" name="email" value={formData.email} onChange={handleChange} required />
                </div>
                 <div className="form-group">
                    <label htmlFor="dateOfBirth">Date of Birth</label>
                    <input type="date" id="dateOfBirth" name="dateOfBirth" value={formData.dateOfBirth} onChange={handleChange} required />
                </div>
                <div className="form-group">
                    <label htmlFor="courseOfStudy">Course of Study</label>
                    <select id="courseOfStudy" name="courseOfStudy" value={formData.courseOfStudy} onChange={handleChange} required>
                        <option value="Systems Development">Systems Development</option>
                        <option value="Information Technology">Information Technology</option>
                        <option value="Computer Science">Computer Science</option>
                    </select>
                </div>
                <div className="form-group">
                    <label htmlFor="enrollmentDate">Enrollment Date</label>
                    <input type="date" id="enrollmentDate" name="enrollmentDate" value={formData.enrollmentDate} onChange={handleChange} required />
                </div>
                <button type="submit" className="btn-primary">Register</button>
            </form>
            {message && <p className="success-message">{message}</p>}
        </div>
    );
};

export default StudentForm;