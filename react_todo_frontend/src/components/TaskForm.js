import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { createTask, fetchTags } from "../services/api";
import Select from "react-select";
import { toast } from "react-toastify";

function TaskForm() {
    const navigate = useNavigate();
    const [formData, setFormData] = useState({
        title: "",
        description: "",
        endDate: "",
        priority: "medium",
        location: "",
        tags: [],
    });
    const [tags, setTags] = useState([]);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});

    useEffect(() => {
        fetchTags().then((data) => {
            setTags(data.map((tag) => ({ value: tag.tagId, label: tag.name })));
        });
    }, []);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
        // Clear error when field is edited
        if (errors[name]) {
            setErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const handleTagChange = (selected) => {
        setFormData((prev) => ({ ...prev, tags: selected || [] }));
    };

    const validateForm = () => {
        const newErrors = {};
        if (!formData.title.trim()) {
            newErrors.title = "Title is required";
        }
        return newErrors;
    };

// Updated handleSubmit function for TaskForm.js
// Further updated handleSubmit function for TaskForm.js
    const handleSubmit = async (e) => {
        e.preventDefault();

        // Validate form
        const formErrors = validateForm();
        if (Object.keys(formErrors).length > 0) {
            setErrors(formErrors);
            return;
        }

        setLoading(true);
        try {
            // Prepare task data - ensure proper format for all fields
            const taskData = {
                title: formData.title.trim(),
                description: formData.description ? formData.description.trim() : "",
                endDate: formData.endDate || null,
                priority: formData.priority || "medium",
                location: formData.location ? formData.location.trim() : "",
                tags: formData.tags.map(tag => tag.value) // Extract tag IDs from the select component
            };

            // For debugging - can be removed in production
            console.log("Sending task data:", taskData);

            const response = await createTask(taskData);

            // If we get here without an error, consider it a success
            // The server might return different formats, so we're being flexible
            console.log("Task creation response:", response);

            // Consider it a success if we get a response without throwing an error
            toast.success("Task created successfully!");
            navigate("/tasks");
        } catch (error) {
            // Handle errors
            console.error("Task creation error:", error);

            const errorMessage = error.response?.data?.message || "Failed to create task.";
            toast.error(errorMessage);

            // Set specific errors if returned from backend
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            }
        } finally {
            setLoading(false);
        }
    };

    // Custom styles for react-select
    const customSelectStyles = {
        control: (provided) => ({
            ...provided,
            borderColor: '#e2e8f0',
            boxShadow: 'none',
            '&:hover': {
                borderColor: '#cbd5e0',
            }
        }),
        multiValue: (provided) => ({
            ...provided,
            backgroundColor: '#EBF4FF',
        }),
        multiValueLabel: (provided) => ({
            ...provided,
            color: '#4299e1',
        }),
        multiValueRemove: (provided) => ({
            ...provided,
            color: '#4299e1',
            '&:hover': {
                backgroundColor: '#bee3f8',
                color: '#2b6cb0',
            },
        }),
    };

    return (
        <div className="max-w-lg mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
            <div className="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                <h1 className="text-2xl font-bold text-white">Create New Task</h1>
            </div>

            <form onSubmit={handleSubmit} className="p-6">
                <div className="mb-4">
                    <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="title">
                        Title <span className="text-red-500">*</span>
                    </label>
                    <input
                        id="title"
                        type="text"
                        name="title"
                        value={formData.title}
                        onChange={handleChange}
                        placeholder="What needs to be done?"
                        required
                        className={`w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition ${
                            errors.title ? 'border-red-500' : 'border-gray-300'
                        }`}
                    />
                    {errors.title && <p className="text-red-500 text-xs mt-1">{errors.title}</p>}
                </div>

                <div className="mb-4">
                    <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="description">
                        Description
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        value={formData.description}
                        onChange={handleChange}
                        placeholder="Add details about this task..."
                        rows="4"
                        className="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                    />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="endDate">
                            Due Date & Time
                        </label>
                        <input
                            id="endDate"
                            type="datetime-local"
                            name="endDate"
                            value={formData.endDate}
                            onChange={handleChange}
                            className="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                        />
                    </div>

                    <div>
                        <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="priority">
                            Priority
                        </label>
                        <select
                            id="priority"
                            name="priority"
                            value={formData.priority}
                            onChange={handleChange}
                            className="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition appearance-none bg-white"
                        >
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div className="mb-4">
                    <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="location">
                        Location
                    </label>
                    <input
                        id="location"
                        type="text"
                        name="location"
                        value={formData.location}
                        onChange={handleChange}
                        placeholder="Where will this task take place?"
                        className="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                    />
                </div>

                <div className="mb-6">
                    <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="tags">
                        Tags
                    </label>
                    <Select
                        id="tags"
                        isMulti
                        options={tags}
                        value={formData.tags}
                        onChange={handleTagChange}
                        placeholder="Select or create tags..."
                        styles={customSelectStyles}
                        className="basic-multi-select"
                        classNamePrefix="select"
                    />
                </div>

                <div className="flex justify-between">
                    <button
                        type="button"
                        onClick={() => navigate("/tasks")}
                        className="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        disabled={loading}
                        className="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {loading ? (
                            <span className="flex items-center">
                                <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Creating...
                            </span>
                        ) : "Create Task"}
                    </button>
                </div>
            </form>
        </div>
    );
}

export default TaskForm;