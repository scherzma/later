import React, { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { fetchTask, updateTask, deleteTask, assignTagToTask, fetchTags } from "../services/api";
import Select from "react-select";
import { toast } from "react-toastify";
import moment from "moment";
import { isPastDue } from "../utils/dateUtils";

function TaskDetail() {
    const { taskId } = useParams();
    const navigate = useNavigate();
    const [task, setTask] = useState(null);
    const [formData, setFormData] = useState({});
    const [tags, setTags] = useState([]);
    const [selectedTags, setSelectedTags] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});

    useEffect(() => {
        const fetchData = async () => {
            try {
                const taskData = await fetchTask(taskId);
                setTask(taskData);
                setFormData({
                    title: taskData.title,
                    description: taskData.description || "",
                    endDate: taskData.endDate ? taskData.endDate.slice(0, 16) : "",
                    priority: taskData.priority,
                    location: taskData.location || "",
                    finished: taskData.finished,
                });
                setSelectedTags(taskData.tags?.map((t) => ({ value: t.tagId, label: t.name })) || []);
                const allTags = await fetchTags();
                setTags(allTags.map((t) => ({ value: t.tagId, label: t.name })));
            } catch (error) {
                toast.error("Failed to load task.");
                console.error(error);
                navigate("/tasks");
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, [taskId, navigate]);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData((prev) => ({ ...prev, [name]: type === "checkbox" ? checked : value }));

        // Clear error when field is edited
        if (errors[name]) {
            setErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const handleTagChange = (selected) => {
        setSelectedTags(selected || []);
    };

    const validateForm = () => {
        const newErrors = {};
        if (!formData.title.trim()) {
            newErrors.title = "Title is required";
        }
        return newErrors;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        // Validate form
        const formErrors = validateForm();
        if (Object.keys(formErrors).length > 0) {
            setErrors(formErrors);
            return;
        }

        setSaving(true);
        try {
            const updatedTask = { ...formData, endDate: formData.endDate || null };
            const response = await updateTask(taskId, updatedTask);

            // Check if update was successful
            if (response) {
                const newTagIds = selectedTags.map((t) => t.value);
                const currentTagIds = task.tags?.map((t) => t.tagId) || [];

                // Find tags to add (in selectedTags but not in currentTags)
                const tagsToAdd = newTagIds.filter((id) => !currentTagIds.includes(id));

                // Add new tags
                for (const tagId of tagsToAdd) {
                    await assignTagToTask(taskId, tagId);
                }

                toast.success("Task updated successfully!");
                setTask(await fetchTask(taskId));
            } else {
                toast.error("Failed to update task. Please check your inputs.");
            }
        } catch (error) {
            const errorMessage = error.response?.data?.message || "Failed to update task.";
            toast.error(errorMessage);

            // Set specific errors if returned from backend
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            }

            console.error(error);
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async () => {
        if (window.confirm("Are you sure you want to delete this task?")) {
            try {
                await deleteTask(taskId);
                toast.success("Task deleted successfully!");
                navigate("/tasks");
            } catch (error) {
                toast.error("Failed to delete task.");
                console.error(error);
            }
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

    const getPriorityColor = (priority) => {
        switch (priority) {
            case 'high':
                return 'text-red-600';
            case 'medium':
                return 'text-yellow-600';
            case 'low':
                return 'text-green-600';
            default:
                return 'text-gray-600';
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center items-center min-h-[200px]">
                <svg className="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        );
    }

    return (
        <div className="max-w-2xl mx-auto">
            <div className="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
                <div className="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                    <h1 className="text-2xl font-bold text-white flex items-center justify-between">
                        <span>Task Details</span>
                        {task.finished && (
                            <span className="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-1 rounded-full">
                                Completed
                            </span>
                        )}
                        {!task.finished && isPastDue(task.endDate) && (
                            <span className="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-1 rounded-full">
                                Overdue
                            </span>
                        )}
                    </h1>
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
                            placeholder="Task title"
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
                                className={`w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition appearance-none bg-white ${getPriorityColor(formData.priority)}`}
                            >
                                <option value="low" className="text-green-600">Low</option>
                                <option value="medium" className="text-yellow-600">Medium</option>
                                <option value="high" className="text-red-600">High</option>
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

                    <div className="mb-4">
                        <label className="flex items-center">
                            <input
                                type="checkbox"
                                name="finished"
                                checked={formData.finished}
                                onChange={handleChange}
                                className="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500 focus:ring-2 transition"
                            />
                            <span className="ml-2 text-gray-700">Mark as completed</span>
                        </label>
                    </div>

                    <div className="mb-6">
                        <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="tags">
                            Tags
                        </label>
                        <Select
                            id="tags"
                            isMulti
                            options={tags}
                            value={selectedTags}
                            onChange={handleTagChange}
                            placeholder="Select tags..."
                            styles={customSelectStyles}
                            className="basic-multi-select"
                            classNamePrefix="select"
                        />
                    </div>

                    <div className="flex flex-wrap justify-between gap-3">
                        <div className="flex gap-2">
                            <button
                                type="button"
                                onClick={() => navigate("/tasks")}
                                className="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 transition"
                            >
                                Back
                            </button>

                            <button
                                type="button"
                                onClick={handleDelete}
                                className="px-6 py-3 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 transition"
                            >
                                Delete
                            </button>
                        </div>

                        <button
                            type="submit"
                            disabled={saving}
                            className="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {saving ? (
                                <span className="flex items-center">
                                    <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Saving...
                                </span>
                            ) : "Save Changes"}
                        </button>
                    </div>
                </form>

                <div className="bg-gray-50 px-6 py-4 border-t border-gray-100">
                    <div className="flex items-center text-sm text-gray-500 justify-between">
                        <div>
                            <span>Created: {moment(task.createdAt).format("MMMM Do YYYY, h:mm a")}</span>
                        </div>
                        {task.updatedAt && task.updatedAt !== task.createdAt && (
                            <div>
                                <span>Last updated: {moment(task.updatedAt).format("MMMM Do YYYY, h:mm a")}</span>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default TaskDetail;