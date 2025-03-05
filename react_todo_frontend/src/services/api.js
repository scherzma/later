import axios from "axios";
import { getToken, getSessionId } from "../utils/auth";
import { toast } from "react-toastify";

const API_URL = "http://localhost:8000";

const api = axios.create({
    baseURL: API_URL,
    headers: {
        "Content-Type": "application/json",
    },
});

api.interceptors.request.use((config) => {
    const token = getToken();
    const sessionId = getSessionId();
    
    // Add authorization token if available
    if (token) config.headers.Authorization = `Bearer ${token}`;
    
    // Add session ID if available
    if (sessionId) config.headers["X-Session-ID"] = sessionId;
    
    return config;
});

api.interceptors.response.use(
    (response) => response.data,
    (error) => {
        // Get the current URL path
        const currentPath = window.location.pathname;
        
        if (error.response?.status === 401) {
            // Check if error is due to session expiration
            const isSessionExpired = error.response?.data?.code === 'SESSION_EXPIRED';
            
            // Only handle 401 errors if we're not already on the login page
            // This prevents infinite redirects
            if (currentPath !== "/login") {
                // Import dynamically to avoid circular reference
                import("../utils/auth").then(({ logout }) => {
                    logout();
                    window.location.href = "/login";
                    
                    if (isSessionExpired) {
                        toast.error("Session expired due to inactivity. Please log in again.");
                    } else {
                        toast.error("Authentication failed. Please log in again.");
                    }
                });
            }
        }

        // Extract error message from response if available
        const errorMessage = error.response?.data?.message || error.response?.data?.error || "An error occurred.";

        // Don't show toast for errors that will be handled by components
        // Also don't show toast for auth errors on login page to prevent toast spam
        if (error.response?.status !== 409 && error.response?.status !== 400 && 
            !(error.response?.status === 401 && currentPath === "/login")) {
            toast.error(errorMessage);
        }

        // Pass the error along with its response data
        throw error;
    }
);

// Authentication
export const login = async (username, password) => {
    try {
        const response = await api.post("/users/login", { username, password });
        console.log("Raw API login response:", response);
        
        // Ensure the response has the expected structure
        if (!response.token || !response.user) {
            console.error("Invalid login response format:", response);
            throw new Error("Server returned an invalid response");
        }
        
        // Make sure failedAttempts is included even if it's zero
        if (response.user && response.user.failedAttempts === undefined) {
            response.user.failedAttempts = 0;
        }
        
        return response;
    } catch (error) {
        console.error("Login API error:", error);
        if (error.response?.status === 401) {
            throw new Error("Invalid username or password");
        }
        throw error;
    }
};

// Log out the user - end the session on the server
export const logout = async (sessionId) => {
    return await api.post("/users/logout", { sessionId });
};

// Check if the session is still valid
export const checkSession = async (sessionId) => {
    return await api.post("/users/session", { sessionId });
};

export const register = async (username, password) => {
    try {
        return await api.post("/users/register", { username, password });
    } catch (error) {
        if (error.response?.status === 409) {
            throw new Error("Username already exists");
        }
        throw error;
    }
};

// Tasks
export const fetchTasks = () => api.get("/tasks");

export const fetchTask = (taskId) => api.get(`/tasks/${taskId}`);

export const createTask = async (taskData) => {
    try {
        // Format the task data properly
        const formattedData = {
            title: taskData.title,
            description: taskData.description || "",
            priority: taskData.priority || "medium",
            location: taskData.location || "",
            endDate: taskData.endDate || null,
            // If tags is an array of objects with value property, extract those values
            // If it's already an array of IDs, use as is
            tags: Array.isArray(taskData.tags)
                ? taskData.tags.map(tag => typeof tag === 'object' && tag.value ? tag.value : tag)
                : []
        };

        // Send the request
        const response = await api.post("/tasks", formattedData);
        return response;
    } catch (error) {
        console.error("Task creation error:", error);

        // Handle specific error cases
        if (error.response) {
            if (error.response.status === 409) {
                throw {
                    response: {
                        data: {
                            message: "A task with this title already exists",
                            errors: { title: "Task title must be unique" }
                        }
                    }
                };
            } else if (error.response.status === 400) {
                // Bad request - possibly validation errors
                throw {
                    response: {
                        data: {
                            message: error.response.data.message || "Invalid task data",
                            errors: error.response.data.errors || {}
                        }
                    }
                };
            }
        }

        // For network errors or other issues
        throw {
            response: {
                data: {
                    message: "Unable to connect to server. Please check your internet connection.",
                    errors: {}
                }
            }
        };
    }
};

export const updateTask = async (taskId, taskData) => {
    try {
        return await api.put(`/tasks/${taskId}`, taskData);
    } catch (error) {
        if (error.response?.status === 409) {
            throw {
                response: {
                    data: {
                        message: "A task with this title already exists",
                        errors: { title: "Task title must be unique" }
                    }
                }
            };
        }
        throw error;
    }
};

export const deleteTask = (taskId) => api.delete(`/tasks/${taskId}`);

// New task action endpoints
export const completeTask = (taskId) => api.post(`/tasks/${taskId}/complete`);

export const postponeTask = (taskId) => api.post(`/tasks/${taskId}/postpone`);

export const getNextTask = (excludeTaskId = null) => {
    const params = excludeTaskId ? { excludeTaskId } : {};
    return api.get('/tasks/next', { params });
};

// Task Queue endpoints
export const getTaskQueue = () => api.get('/tasks/queue');

export const addTaskToQueue = (taskId) => api.post('/tasks/queue', { taskId });

export const getTaskQueueInfo = (taskId) => api.get(`/tasks/${taskId}/queue`);

export const updateTaskQueuePosition = (taskId, position) =>
    api.put(`/tasks/${taskId}/queue`, { position });

export const removeTaskFromQueue = (taskId) => api.delete(`/tasks/${taskId}/queue`);

// Tags
export const fetchTags = () => api.get("/tags");

export const assignTagToTask = (taskId, tagId) =>
    api.post(`/tasks/${taskId}/tags`, { tagId });

export const removeTagFromTask = (taskId, tagId) =>
    api.delete(`/tasks/${taskId}/tags/${tagId}`);

export const createTag = async (tagData) => {
    try {
        return await api.post("/tags", tagData);
    } catch (error) {
        if (error.response?.status === 409) {
            throw {
                response: {
                    data: {
                        message: "A tag with this name already exists",
                        errors: { name: "Tag name must be unique" }
                    }
                }
            };
        }
        throw error;
    }
};

export const updateTag = async (tagId, tagData) => {
    try {
        return await api.put(`/tags/${tagId}`, tagData);
    } catch (error) {
        if (error.response?.status === 409) {
            throw {
                response: {
                    data: {
                        message: "A tag with this name already exists",
                        errors: { name: "Tag name must be unique" }
                    }
                }
            };
        }
        throw error;
    }
};

export const deleteTag = (tagId) => api.delete(`/tags/${tagId}`);

// User Profile
export const fetchProfile = () => api.get("/users/me");

export const fetchUserStreak = () => api.get("/users/me/streak");

export const updateProfile = async (profileData) => {
    try {
        return await api.put("/users/me", profileData);
    } catch (error) {
        if (error.response?.status === 409) {
            throw {
                response: {
                    data: {
                        message: "Username already exists",
                        errors: { username: "This username is already taken" }
                    }
                }
            };
        }
        throw error;
    }
};