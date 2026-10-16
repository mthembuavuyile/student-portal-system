/**
 * API Service Module
 * 
 * This module centralizes all HTTP requests to the backend API.
 * It provides a clean way to interact with the server from the React components.
 */

// The base URL of our Node.js mock server
const API_BASE_URL = 'http://localhost:3001/api';

/**
 * Fetches all students from the server.
 * 
 * @returns {Promise<Array>} A promise that resolves to an array of student objects.
 */
export const getAllStudents = async () => {
    try {
        const response = await fetch(`${API_BASE_URL}/students`);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return await response.json();
    } catch (error) {
        console.error("Failed to fetch students:", error);
        // In a real app, you might want to handle this error more gracefully
        throw error;
    }
};

/**
 * Fetches a single student by their ID.
 * 
 * @param {number} id The ID of the student to fetch.
 * @returns {Promise<object>} A promise that resolves to a single student object.
 */
export const getStudentById = async (id) => {
    try {
        const response = await fetch(`${API_BASE_URL}/students/${id}`);
        if (!response.ok) {
            throw new Error(`Student with ID ${id} not found.`);
        }
        return await response.json();
    } catch (error) {
        console.error(`Failed to fetch student ${id}:`, error);
        throw error;
    }
};

/**
 * Registers a new student.
 * 
 * @param {object} studentData The data for the new student.
 * @returns {Promise<object>} A promise that resolves to the newly created student object.
 */
export const registerStudent = async (studentData) => {
    try {
        const response = await fetch(`${API_BASE_URL}/students`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(studentData),
        });
        if (!response.ok) {
            throw new Error('Failed to register student.');
        }
        return await response.json();
    } catch (error) {
        console.error("Error registering student:", error);
        throw error;
    }
};