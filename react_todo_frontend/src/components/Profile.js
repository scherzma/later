import React, { useState, useEffect } from "react";
import { fetchProfile, fetchUserStreak, updateProfile } from "../services/api";
import { toast } from "react-toastify";

function Profile() {
    const [formData, setFormData] = useState({
        username: "",
        email: "",
        password: "",
        newPassword: "",
        confirmPassword: "",
        emailNotifications: false
    });
    const [loading, setLoading] = useState(false);
    const [isUpdating, setIsUpdating] = useState(false);
    const [errors, setErrors] = useState({});
    const [profileStats, setProfileStats] = useState({
        tasksCreated: 0,
        tasksCompleted: 0,
        joinDate: new Date().toISOString()
    });
    const [streakInfo, setStreakInfo] = useState({
        currentStreak: 0,
        bestStreak: 0,
        lastCompletedDate: null,
        needsTaskToday: false,
        tasksFinishedToday: 0,
        pendingTasks: 0
    });

    useEffect(() => {
        fetchUserProfile();
    }, []);

    const fetchUserProfile = async () => {
        try {
            setLoading(true);

            // Fetch user profile
            const profile = await fetchProfile();

            setFormData(prev => ({
                ...prev,
                username: profile.username,
                email: profile.email || "",
                emailNotifications: profile.preferences?.emailNotifications || false
            }));

            // Update streak info from profile if available
            if (profile.streakInfo) {
                setStreakInfo(prev => ({
                    ...prev,
                    ...profile.streakInfo
                }));
            }

            // Get more detailed streak information
            const streakDetails = await fetchUserStreak();
            setStreakInfo(prev => ({
                ...prev,
                ...streakDetails.streakInfo
            }));

            // In a real app, you'd have additional endpoints to fetch these stats
            // For now, we'll calculate some basic stats
            setProfileStats({
                tasksCreated: streakDetails.streakInfo.tasksFinishedToday + streakDetails.streakInfo.pendingTasks,
                tasksCompleted: streakDetails.streakInfo.tasksFinishedToday,
                joinDate: profile.createdAt || new Date().toISOString()
            });
        } catch (error) {
            toast.error("Failed to load profile.");
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData((prev) => ({
            ...prev,
            [name]: type === "checkbox" ? checked : value
        }));

        // Clear error when field is edited
        if (errors[name]) {
            setErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const validateForm = () => {
        const newErrors = {};

        if (!formData.username.trim()) {
            newErrors.username = "Username is required";
        }

        if (formData.email && !/\S+@\S+\.\S+/.test(formData.email)) {
            newErrors.email = "Invalid email address";
        }

        // Only validate password fields if the user is trying to change password
        if (formData.password || formData.newPassword || formData.confirmPassword) {
            if (!formData.password) {
                newErrors.password = "Current password is required to set a new password";
            }

            if (!formData.newPassword) {
                newErrors.newPassword = "New password is required";
            } else if (formData.newPassword.length < 6) {
                newErrors.newPassword = "Password must be at least 6 characters";
            }

            if (formData.newPassword !== formData.confirmPassword) {
                newErrors.confirmPassword = "Passwords don't match";
            }
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

        setIsUpdating(true);
        try {
            // Prepare profile data
            const profileData = {
                username: formData.username,
                email: formData.email || undefined,
                emailNotifications: formData.emailNotifications
            };

            // Only include password if the user is changing it
            if (formData.password && formData.newPassword) {
                profileData.password = formData.password;
                profileData.newPassword = formData.newPassword;
            }

            const result = await updateProfile(profileData);
            toast.success("Profile updated successfully!");

            // Update any returned profile data
            if (result.username) {
                setFormData(prev => ({
                    ...prev,
                    username: result.username
                }));
            }

            // Clear password fields after successful update
            setFormData(prev => ({
                ...prev,
                password: "",
                newPassword: "",
                confirmPassword: ""
            }));
        } catch (error) {
            const errorMessage = error.response?.data?.message || "Failed to update profile.";
            toast.error(errorMessage);

            // Set specific errors if returned from backend
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            }

            console.error(error);
        } finally {
            setIsUpdating(false);
        }
    };

    // Format join date to a readable format
    const formatJoinDate = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    // Calculate streak completion percentage for progress bar
    const calculateStreakPercentage = () => {
        // Assuming 10 days is a good milestone to reach 100%
        return Math.min(100, Math.round((streakInfo.currentStreak / 10) * 100));
    };

    // Calculate user level based on streak
    const calculateUserLevel = () => {
        return Math.floor(streakInfo.currentStreak / 5) + 1;
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
        <div className="max-w-4xl mx-auto">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {/* Profile Stats Card */}
                <div className="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div className="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
                        <h2 className="text-xl font-bold text-white">Profile Summary</h2>
                    </div>
                    <div className="p-6">
                        <div className="flex flex-col items-center mb-6">
                            <div className="bg-indigo-100 rounded-full p-6 mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-16 w-16 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h3 className="text-2xl font-bold text-gray-800">{formData.username}</h3>
                            <p className="text-gray-500">Member since {formatJoinDate(profileStats.joinDate)}</p>
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between p-3 bg-indigo-50 rounded-lg">
                                <div className="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-indigo-500 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                        <path fillRule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clipRule="evenodd" />
                                    </svg>
                                    <span>Tasks Created</span>
                                </div>
                                <span className="font-bold text-indigo-600">{profileStats.tasksCreated}</span>
                            </div>

                            <div className="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <div className="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-green-500 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                    <span>Tasks Completed</span>
                                </div>
                                <span className="font-bold text-green-600">{profileStats.tasksCompleted}</span>
                            </div>

                            <div className="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                                <div className="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-yellow-500 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clipRule="evenodd" />
                                    </svg>
                                    <span>Day Streak</span>
                                </div>
                                <span className="font-bold text-yellow-600">{streakInfo.currentStreak}</span>
                            </div>

                            <div className="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                                <div className="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-purple-500 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M13.5 3a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM11 11.5a.5.5 0 01.5-.5h1a.5.5 0 01.5.5v1a.5.5 0 01-.5.5h-1a.5.5 0 01-.5-.5v-1zm-3 0a.5.5 0 01.5-.5h1a.5.5 0 01.5.5v1a.5.5 0 01-.5.5h-1a.5.5 0 01-.5-.5v-1zm-5 2a2 2 0 104 0 2 2 0 00-4 0z" />
                                        <path fillRule="evenodd" d="M15.5 5a2 2 0 100 4 2 2 0 000-4zm-1 7a.5.5 0 01.5-.5h1a.5.5 0 01.5.5v1a.5.5 0 01-.5.5h-1a.5.5 0 01-.5-.5v-1zm-7-7a.5.5 0 01.5-.5h1a.5.5 0 01.5.5v1a.5.5 0 01-.5.5h-1a.5.5 0 01-.5-.5V5z" clipRule="evenodd" />
                                    </svg>
                                    <span>Best Streak</span>
                                </div>
                                <span className="font-bold text-purple-600">{streakInfo.bestStreak}</span>
                            </div>

                            <div className="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <div className="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-blue-500 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clipRule="evenodd" />
                                    </svg>
                                    <span>Tasks Today</span>
                                </div>
                                <span className="font-bold text-blue-600">{streakInfo.tasksFinishedToday}</span>
                            </div>

                            <div className="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                                <div className="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-red-500 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clipRule="evenodd" />
                                    </svg>
                                    <span>Pending Tasks</span>
                                </div>
                                <span className="font-bold text-red-600">{streakInfo.pendingTasks}</span>
                            </div>
                        </div>

                        {/* Productivity Level */}
                        <div className="mt-6">
                            <div className="flex justify-between mb-1">
                                <span className="text-sm font-medium text-gray-700">Productivity Level</span>
                                <span className="text-sm font-medium text-gray-700">{calculateStreakPercentage()}%</span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-2.5">
                                <div
                                    className="bg-gradient-to-r from-green-500 to-blue-500 h-2.5 rounded-full"
                                    style={{ width: `${calculateStreakPercentage()}%` }}
                                ></div>
                            </div>
                            <div className="flex justify-between mt-2">
                                <span className="text-xs text-gray-500">Level {calculateUserLevel()}</span>
                                <span className="text-xs text-gray-500">Next: Level {calculateUserLevel() + 1}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Account Settings Card */}
                <div className="bg-white shadow-lg rounded-lg overflow-hidden md:col-span-2">
                    <div className="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                        <h2 className="text-xl font-bold text-white">Account Settings</h2>
                    </div>

                    <form onSubmit={handleSubmit} className="p-6">
                        {errors.general && (
                            <div className="mb-4 bg-red-50 border-l-4 border-red-500 p-4 text-red-700">
                                <p>{errors.general}</p>
                            </div>
                        )}

                        <div className="mb-6">
                            <h3 className="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Basic Information</h3>

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2" htmlFor="username">
                                    Username <span className="text-red-500">*</span>
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <input
                                        id="username"
                                        name="username"
                                        type="text"
                                        value={formData.username}
                                        onChange={handleChange}
                                        className={`pl-10 block w-full border ${
                                            errors.username ? 'border-red-300' : 'border-gray-300'
                                        } rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500`}
                                    />
                                </div>
                                {errors.username && <p className="mt-2 text-sm text-red-600">{errors.username}</p>}
                            </div>

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2" htmlFor="email">
                                    Email
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                        </svg>
                                    </div>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value={formData.email}
                                        onChange={handleChange}
                                        className={`pl-10 block w-full border ${
                                            errors.email ? 'border-red-300' : 'border-gray-300'
                                        } rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500`}
                                        placeholder="example@email.com"
                                    />
                                </div>
                                {errors.email && <p className="mt-2 text-sm text-red-600">{errors.email}</p>}
                            </div>

                            <div className="mb-4">
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        name="emailNotifications"
                                        checked={formData.emailNotifications}
                                        onChange={handleChange}
                                        className="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500 focus:ring-2 transition"
                                    />
                                    <span className="ml-2 text-gray-700">Receive email notifications</span>
                                </label>
                            </div>
                        </div>

                        <div className="mb-6">
                            <h3 className="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Change Password</h3>

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2" htmlFor="password">
                                    Current Password
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        value={formData.password}
                                        onChange={handleChange}
                                        className={`pl-10 block w-full border ${
                                            errors.password ? 'border-red-300' : 'border-gray-300'
                                        } rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500`}
                                    />
                                </div>
                                {errors.password && <p className="mt-2 text-sm text-red-600">{errors.password}</p>}
                            </div>

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2" htmlFor="newPassword">
                                    New Password
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <input
                                        id="newPassword"
                                        name="newPassword"
                                        type="password"
                                        value={formData.newPassword}
                                        onChange={handleChange}
                                        className={`pl-10 block w-full border ${
                                            errors.newPassword ? 'border-red-300' : 'border-gray-300'
                                        } rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500`}
                                    />
                                </div>
                                {errors.newPassword && <p className="mt-2 text-sm text-red-600">{errors.newPassword}</p>}
                            </div>

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2" htmlFor="confirmPassword">
                                    Confirm New Password
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <input
                                        id="confirmPassword"
                                        name="confirmPassword"
                                        type="password"
                                        value={formData.confirmPassword}
                                        onChange={handleChange}
                                        className={`pl-10 block w-full border ${
                                            errors.confirmPassword ? 'border-red-300' : 'border-gray-300'
                                        } rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500`}
                                    />
                                </div>
                                {errors.confirmPassword && <p className="mt-2 text-sm text-red-600">{errors.confirmPassword}</p>}
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <button
                                type="submit"
                                disabled={isUpdating}
                                className="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {isUpdating ? (
                                    <span className="flex items-center">
                                        <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Updating...
                                    </span>
                                ) : "Save Changes"}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {/* Task Streak Status Card */}
            <div className="bg-white shadow-lg rounded-lg overflow-hidden mt-6">
                <div className="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                    <h2 className="text-xl font-bold text-white">Streak Status</h2>
                </div>
                <div className="p-6">
                    <div className="flex items-center mb-6">
                        <div className={`p-4 rounded-full mr-4 ${streakInfo.needsTaskToday ? 'bg-yellow-100 text-yellow-600' : 'bg-green-100 text-green-600'}`}>
                            {streakInfo.needsTaskToday ? (
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            ) : (
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            )}
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold text-gray-800">
                                {streakInfo.needsTaskToday
                                    ? "Complete a task today to keep your streak!"
                                    : "You've completed tasks today - streak maintained!"}
                            </h3>
                            <p className="text-gray-600 mt-1">
                                {streakInfo.needsTaskToday
                                    ? "You haven't completed any tasks today. Don't break your streak!"
                                    : `You've completed ${streakInfo.tasksFinishedToday} task${streakInfo.tasksFinishedToday !== 1 ? 's' : ''} today. Great work!`}
                            </p>
                        </div>
                    </div>

                    {streakInfo.lastCompletedDate && (
                        <div className="bg-blue-50 p-4 rounded-lg mb-4">
                            <div className="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-blue-500 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clipRule="evenodd" />
                                </svg>
                                <span className="text-blue-700 font-medium">Last task completed: {formatJoinDate(streakInfo.lastCompletedDate)}</span>
                            </div>
                        </div>
                    )}

                    <div className="bg-gray-50 p-4 rounded-lg">
                        <h4 className="font-medium text-gray-800 mb-3">Pending Tasks</h4>
                        <div className="flex justify-between items-center">
                            <div className="w-full bg-gray-200 rounded-full h-3">
                                <div
                                    className="bg-gradient-to-r from-red-500 to-blue-500 h-3 rounded-full"
                                    style={{ width: `${Math.min(100, (streakInfo.pendingTasks / (streakInfo.pendingTasks + streakInfo.tasksFinishedToday)) * 100)}%` }}
                                ></div>
                            </div>
                            <span className="ml-4 text-gray-600 font-medium">{streakInfo.pendingTasks}</span>
                        </div>
                        <div className="text-sm text-gray-500 mt-2">
                            {streakInfo.pendingTasks === 0
                                ? "You have no pending tasks. Time to create more!"
                                : `You have ${streakInfo.pendingTasks} pending task${streakInfo.pendingTasks !== 1 ? 's' : ''} left to complete.`}
                        </div>
                    </div>
                </div>
            </div>

            {/* Additional Preferences Card */}
            <div className="bg-white shadow-lg rounded-lg overflow-hidden mt-6">
                <div className="bg-gradient-to-r from-pink-500 to-pink-600 px-6 py-4">
                    <h2 className="text-xl font-bold text-white">Additional Preferences</h2>
                </div>
                <div className="p-6">
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-pink-500 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                </svg>
                                <span className="font-medium">Daily Task Reminders</span>
                            </div>
                            <label className="flex items-center cursor-pointer">
                                <div className="relative">
                                    <input type="checkbox" className="sr-only" defaultChecked />
                                    <div className="block bg-gray-200 w-12 h-6 rounded-full"></div>
                                    <div className="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform translate-x-6"></div>
                                </div>
                            </label>
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-pink-500 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                                </svg>
                                <span className="font-medium">Push Notifications</span>
                            </div>
                            <label className="flex items-center cursor-pointer">
                                <div className="relative">
                                    <input type="checkbox" className="sr-only" />
                                    <div className="block bg-gray-200 w-12 h-6 rounded-full"></div>
                                    <div className="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition"></div>
                                </div>
                            </label>
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-pink-500 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm0 2h10v7h-2l-1 2H8l-1-2H5V5z" clipRule="evenodd" />
                                </svg>
                                <span className="font-medium">Weekly Summary</span>
                            </div>
                            <label className="flex items-center cursor-pointer">
                                <div className="relative">
                                    <input type="checkbox" className="sr-only" defaultChecked />
                                    <div className="block bg-gray-200 w-12 h-6 rounded-full"></div>
                                    <div className="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform translate-x-6"></div>
                                </div>
                            </label>
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-pink-500 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z" />
                                </svg>
                                <span className="font-medium">Task Completion Animations</span>
                            </div>
                            <label className="flex items-center cursor-pointer">
                                <div className="relative">
                                    <input type="checkbox" className="sr-only" defaultChecked />
                                    <div className="block bg-gray-200 w-12 h-6 rounded-full"></div>
                                    <div className="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform translate-x-6"></div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Profile;