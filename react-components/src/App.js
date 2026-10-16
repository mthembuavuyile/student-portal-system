// react-components/src/App.js

import React from 'react';
import StudentDashboard from './components/StudentDashboard';
import './styles/components.css'; // Let's create this file next

function App() {
    return (
        <div className="App">
            <header className="App-header">
                <h1>React Student Dashboard</h1>
                <p>
                    This is the React-enhanced version of the Student Management System.
                </p>
            </header>
            <main>
                <StudentDashboard />
            </main>
        </div>
    );
}

export default App;