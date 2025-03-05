import React, { useEffect, useState } from "react";
import { Routes, Route, useNavigate, useLocation } from "react-router-dom";
import { isLoggedIn, checkSession, logout } from "./utils/auth";
import { toast } from "react-toastify";
import Navbar from "./components/Navbar";
import Footer from "./components/Footer";
import Login from "./components/Login";
import Register from "./components/Register";
import Home from "./components/Home";
import TaskList from "./components/TaskList";
import TaskForm from "./components/TaskForm";
import TaskDetail from "./components/TaskDetail";
import TagManager from "./components/TagManager";
import Profile from "./components/Profile";
import LoginHistory from "./components/LoginHistory";
import { StreakProvider } from "./context/StreakContext";

function App() {
    const navigate = useNavigate();
    const location = useLocation();
    const [lastActivity, setLastActivity] = useState(Date.now());
    const [inactivityTimer, setInactivityTimer] = useState(null);
    const inactivityTimeout = 30 * 60 * 1000; // 30 minutes in milliseconds

    // Check authentication on mount and redirect if needed
    useEffect(() => {
        // Get the current path
        const currentPath = location.pathname;

        // Only redirect if not on login or register pages
        const publicPaths = ['/login', '/register'];

        if (!isLoggedIn() && !publicPaths.includes(currentPath)) {
            navigate("/login");
        }
    }, [navigate, location]);

    // Set up inactivity tracking
    useEffect(() => {
        // Only track activity for authenticated users
        if (!isLoggedIn()) return;
        
        // Reset the inactivity timer on user activity
        const resetInactivityTimer = () => {
            setLastActivity(Date.now());
        };

        // Check for inactivity periodically
        const checkInactivity = () => {
            const currentTime = Date.now();
            const timeSinceLastActivity = currentTime - lastActivity;
            
            // If user has been inactive for too long, verify session
            if (timeSinceLastActivity > inactivityTimeout) {
                // Check if session is still valid on the server
                checkSession().then(isValid => {
                    if (!isValid) {
                        // Session expired, log user out
                        logout();
                        navigate("/login");
                        toast.error("You have been logged out due to inactivity.");
                    } else {
                        // Session is still valid, reset timer
                        setLastActivity(Date.now());
                    }
                });
            }
        };

        // Set up activity event listeners
        const activityEvents = ['mousedown', 'keydown', 'touchstart', 'scroll'];
        activityEvents.forEach(event => {
            window.addEventListener(event, resetInactivityTimer);
        });

        // Start inactivity timer
        const timer = setInterval(checkInactivity, 60000); // Check every minute
        setInactivityTimer(timer);

        // Cleanup event listeners and timer
        return () => {
            activityEvents.forEach(event => {
                window.removeEventListener(event, resetInactivityTimer);
            });
            clearInterval(timer);
        };
    }, [lastActivity, navigate, inactivityTimeout]);

    return (
        <StreakProvider>
            <div className="flex flex-col min-h-screen">
                <div className="flex-grow bg-gray-100">
                    {isLoggedIn() && <Navbar />}
                    <div className="container mx-auto p-4">
                        <Routes>
                            <Route path="/login" element={<Login />} />
                            <Route path="/register" element={<Register />} />
                            <Route path="/" element={<Home />} />
                            <Route path="/tasks" element={<TaskList />} />
                            <Route path="/tasks/new" element={<TaskForm />} />
                            <Route path="/tasks/:taskId" element={<TaskDetail />} />
                            <Route path="/tags" element={<TagManager />} />
                            <Route path="/profile" element={<Profile />} />
                            <Route path="/security" element={<LoginHistory />} />
                        </Routes>
                    </div>
                </div>
                <Footer />
            </div>
        </StreakProvider>
    );
}

export default App;