import React, { useState, useEffect } from "react";
import { fetchTags, createTag, updateTag, deleteTag } from "../services/api";
import { toast } from "react-toastify";

function TagManager() {
    const [tags, setTags] = useState([]);
    const [formData, setFormData] = useState({ name: "", priority: "medium" });
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [editMode, setEditMode] = useState(null);
    const [editData, setEditData] = useState({ name: "", priority: "medium" });
    const [errors, setErrors] = useState({});

    const loadTags = async () => {
        setLoading(true);
        try {
            const fetchedTags = await fetchTags();
            setTags(fetchedTags);
        } catch (error) {
            toast.error("Failed to load tags.");
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadTags();
    }, []);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));

        // Clear error when field is edited
        if (errors[name]) {
            setErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const handleEditChange = (e) => {
        const { name, value } = e.target;
        setEditData((prev) => ({ ...prev, [name]: value }));
    };

    const validateForm = (data) => {
        const newErrors = {};
        if (!data.name.trim()) {
            newErrors.name = "Tag name is required";
        }
        return newErrors;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        // Validate form
        const formErrors = validateForm(formData);
        if (Object.keys(formErrors).length > 0) {
            setErrors(formErrors);
            return;
        }

        setSaving(true);
        try {
            await createTag(formData);
            toast.success("Tag created successfully!");
            setFormData({ name: "", priority: "medium" });
            await loadTags();
        } catch (error) {
            const errorMessage = error.response?.data?.message || "Failed to create tag.";
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

    const startEdit = (tag) => {
        setEditMode(tag.tagId);
        setEditData({ name: tag.name, priority: tag.priority });
    };

    const cancelEdit = () => {
        setEditMode(null);
        setEditData({ name: "", priority: "medium" });
    };

    const handleEditSubmit = async (tagId) => {
        // Validate form
        const formErrors = validateForm(editData);
        if (Object.keys(formErrors).length > 0) {
            setErrors(formErrors);
            return;
        }

        try {
            await updateTag(tagId, editData);
            toast.success("Tag updated successfully!");
            setEditMode(null);
            await loadTags();
        } catch (error) {
            const errorMessage = error.response?.data?.message || "Failed to update tag.";
            toast.error(errorMessage);
            console.error(error);
        }
    };

    const handleDelete = async (tagId) => {
        if (window.confirm("Are you sure you want to delete this tag? This will remove the tag from all associated tasks.")) {
            try {
                await deleteTag(tagId);
                toast.success("Tag deleted successfully!");
                await loadTags();
            } catch (error) {
                toast.error("Failed to delete tag.");
                console.error(error);
            }
        }
    };

    const getPriorityColor = (priority) => {
        switch (priority) {
            case 'high':
                return 'border-red-500 bg-red-50 text-red-700';
            case 'medium':
                return 'border-yellow-500 bg-yellow-50 text-yellow-700';
            case 'low':
                return 'border-green-500 bg-green-50 text-green-700';
            default:
                return 'border-gray-300 bg-gray-50 text-gray-700';
        }
    };

    const getPriorityIcon = (priority) => {
        switch (priority) {
            case 'high':
                return (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clipRule="evenodd" />
                    </svg>
                );
            case 'medium':
                return (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clipRule="evenodd" />
                    </svg>
                );
            case 'low':
                return (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                );
            default:
                return null;
        }
    };

    return (
        <div className="max-w-4xl mx-auto">
            <div className="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
                <div className="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                    <h1 className="text-2xl font-bold text-white">Manage Tags</h1>
                </div>

                <form onSubmit={handleSubmit} className="p-6 border-b border-gray-100">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-2" htmlFor="name">
                                Tag Name <span className="text-red-500">*</span>
                            </label>
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value={formData.name}
                                    onChange={handleChange}
                                    placeholder="Enter tag name"
                                    required
                                    className={`w-full p-3 pl-10 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 transition ${
                                        errors.name ? 'border-red-500' : 'border-gray-300'
                                    }`}
                                />
                            </div>
                            {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2" htmlFor="priority">
                                Priority
                            </label>
                            <select
                                id="priority"
                                name="priority"
                                value={formData.priority}
                                onChange={handleChange}
                                className="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 transition appearance-none bg-white"
                            >
                                <option value="low" className="text-green-600">Low</option>
                                <option value="medium" className="text-yellow-600">Medium</option>
                                <option value="high" className="text-red-600">High</option>
                            </select>
                        </div>
                    </div>

                    <div className="mt-4">
                        <button
                            type="submit"
                            disabled={saving}
                            className="w-full md:w-auto px-6 py-3 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg hover:from-purple-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        >
                            {saving ? (
                                <span className="flex items-center">
                                    <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Creating...
                                </span>
                            ) : (
                                <span className="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clipRule="evenodd" />
                                    </svg>
                                    Add Tag
                                </span>
                            )}
                        </button>
                    </div>
                </form>

                <div className="p-6">
                    <h2 className="text-xl font-semibold text-gray-800 mb-4">Your Tags</h2>

                    {loading ? (
                        <div className="flex justify-center items-center py-12">
                            <svg className="animate-spin h-8 w-8 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    ) : tags.length > 0 ? (
                        <div className="grid grid-cols-1 gap-4">
                            {tags.map((tag) => (
                                <div
                                    key={tag.tagId}
                                    className={`rounded-lg border-l-4 ${getPriorityColor(tag.priority)} shadow-sm hover:shadow-md transition p-4`}
                                >
                                    {editMode === tag.tagId ? (
                                        // Edit mode
                                        <div className="flex flex-col md:flex-row gap-4">
                                            <div className="flex-grow md:flex md:space-x-4">
                                                <input
                                                    type="text"
                                                    name="name"
                                                    value={editData.name}
                                                    onChange={handleEditChange}
                                                    placeholder="Tag name"
                                                    className="w-full md:w-2/3 p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 mb-2 md:mb-0"
                                                />
                                                <select
                                                    name="priority"
                                                    value={editData.priority}
                                                    onChange={handleEditChange}
                                                    className="w-full md:w-1/3 p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                                >
                                                    <option value="low">Low</option>
                                                    <option value="medium">Medium</option>
                                                    <option value="high">High</option>
                                                </select>
                                            </div>
                                            <div className="flex space-x-2">
                                                <button
                                                    onClick={() => handleEditSubmit(tag.tagId)}
                                                    className="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition flex-shrink-0"
                                                >
                                                    Save
                                                </button>
                                                <button
                                                    onClick={cancelEdit}
                                                    className="px-3 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 transition flex-shrink-0"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        // View mode
                                        <div className="flex justify-between items-center">
                                            <div className="flex items-center">
                                                <span className="w-8">{getPriorityIcon(tag.priority)}</span>
                                                <span className="text-lg font-medium">{tag.name}</span>
                                                <span className="ml-2 px-2 py-1 text-xs rounded-full bg-white border border-current">
                                                    {tag.priority}
                                                </span>
                                            </div>
                                            <div className="flex space-x-2">
                                                <button
                                                    onClick={() => startEdit(tag)}
                                                    className="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(tag.tagId)}
                                                    className="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 transition"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fillRule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="bg-gray-50 rounded-lg p-8 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No tags found</h3>
                            <p className="text-gray-500 mb-4">
                                Tags help you organize and filter your tasks efficiently
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

export default TagManager;