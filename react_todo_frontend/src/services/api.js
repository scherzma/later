import axios from "axios";
import { getToken, logout } from "../utils/auth";
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
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

api.interceptors.response.use(
    (response) => response.data,
    (error) => {
        if (error.response?.status === 401) {
            logout();
            window.location.href = "/login";
            toast.error("Session expired. Please log in again.");
        }

        // Extract error message from response if available
        const errorMessage = error.response?.data?.message || "An error occurred.";

        // Don't show toast for errors that will be handled by components
        if (error.response?.status !== 409 && error.response?.status !== 400) {
            toast.error(errorMessage);
        }

        // Pass the error along with its response data
        throw error;
    }
);

// Authentication
export const login = async (username, password) => {
    try {
        return await api.post("/users/login", { username, password });
    } catch (error) {
        if (error.response?.status === 401) {
            throw new Error("Invalid username or password");
        }
        throw error;
    }
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