import React, { useEffect } from "react";
import { Routes, Route, useNavigate, useLocation } from "react-router-dom";
import { isLoggedIn } from "./utils/auth";
import Navbar from "./components/Navbar";
import Login from "./components/Login";
import Register from "./components/Register";
import Home from "./components/Home";
import TaskList from "./components/TaskList";
import TaskForm from "./components/TaskForm";
import TaskDetail from "./components/TaskDetail";
import TagManager from "./components/TagManager";
import Profile from "./components/Profile";
import { StreakProvider } from "./context/StreakContext";

function App() {
    const navigate = useNavigate();
    const location = useLocation();

    useEffect(() => {
        // Get the current path
        const currentPath = location.pathname;

        // Only redirect if not on login or register pages
        const publicPaths = ['/login', '/register'];

        if (!isLoggedIn() && !publicPaths.includes(currentPath)) {
            navigate("/login");
        }
    }, [navigate, location]);

    return (
        <StreakProvider>
            <div className="min-h-screen bg-gray-100">
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
                    </Routes>
                </div>
            </div>
        </StreakProvider>
    );
}

export default App;