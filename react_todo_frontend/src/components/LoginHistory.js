import React, { useEffect, useState } from "react";
import { getUserData } from "../utils/auth";

function LoginHistory() {
    const [userData, setUserData] = useState({});
    
    useEffect(() => {
        // Get user data when component mounts
        const data = getUserData() || {};
        setUserData(data);
        console.log("User data from localStorage:", data); // Debug output
    }, []);
    
    return (
        <div className="bg-white shadow-lg rounded-lg overflow-hidden">
            <div className="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                <h2 className="text-xl font-bold text-white">Security Center</h2>
            </div>
            <div className="p-6">
                <div className="space-y-4">
                    {/* Security Summary */}
                    <div className="bg-gray-50 p-4 rounded-lg border-l-4 border-blue-500 mb-6">
                        <h3 className="text-lg font-medium text-gray-800 mb-2">Security Summary</h3>
                        <p className="text-gray-600">
                            Your account security is {userData.failedAttempts > 0 ? 'at risk' : 'good'}. 
                            {userData.failedAttempts > 0 
                                ? ' There have been failed login attempts since your last login.' 
                                : ' No suspicious activity has been detected.'}
                        </p>
                    </div>
                    
                    {/* Failed Attempts - With high visibility if there are failed attempts */}
                    <div className={`flex items-center p-4 rounded-lg ${
                        userData.failedAttempts > 0 
                            ? 'bg-red-100 border border-red-300 animate-pulse' 
                            : 'bg-green-50'
                    }`}>
                        <div className={`p-3 rounded-full mr-4 ${
                            userData.failedAttempts > 0 ? 'bg-red-200 text-red-600' : 'bg-green-100 text-green-600'
                        }`}>
                            {userData.failedAttempts > 0 ? (
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            ) : (
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            )}
                        </div>
                        <div>
                            <div className="font-medium text-gray-800">Failed Login Attempts</div>
                            <div className={`text-sm ${userData.failedAttempts > 0 ? 'text-red-800 font-bold' : 'text-gray-600'}`}>
                                {userData.failedAttempts !== undefined ? (
                                    userData.failedAttempts > 0 ? (
                                        <span className="text-red-700">
                                            WARNING: {userData.failedAttempts} failed login {userData.failedAttempts === 1 ? 'attempt' : 'attempts'} since your last successful login. 
                                            This could indicate someone tried to access your account.
                                        </span>
                                    ) : "No failed login attempts detected since your last login."
                                ) : "Login attempt information not available"}
                            </div>
                            
                            {userData.failedAttempts > 0 && (
                                <div className="mt-2 flex">
                                    <button className="mr-2 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                                        Change Password
                                    </button>
                                    <button className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                        Enable 2FA
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Last Login */}
                    <div className="flex items-center p-4 bg-blue-50 rounded-lg">
                        <div className="bg-blue-100 p-3 rounded-full mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                        </div>
                        <div>
                            <div className="font-medium text-gray-800">Last Successful Login</div>
                            <div className="text-gray-600 text-sm">
                                {userData.lastLogin 
                                    ? new Date(userData.lastLogin).toLocaleString() 
                                    : "Not available"}
                            </div>
                        </div>
                    </div>

                    {/* Session Info */}
                    <div className="flex items-center p-4 bg-green-50 rounded-lg">
                        <div className="bg-green-100 p-3 rounded-full mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <div className="font-medium text-gray-800">Current Session</div>
                            <div className="text-gray-600 text-sm">
                                Active - Session will expire after 30 minutes of inactivity
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <svg className="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-yellow-800">Security Tips</h3>
                                <div className="mt-2 text-sm text-yellow-700">
                                    <ul className="list-disc pl-5 space-y-1">
                                        <li>Use a strong, unique password</li>
                                        <li>Don't share your login credentials</li>
                                        <li>Check your login history regularly</li>
                                        <li>Enable two-factor authentication when available</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div className="mt-6">
                        <h3 className="text-lg font-medium text-gray-800 mb-4">Active Sessions</h3>
                        <div className="bg-white shadow overflow-hidden sm:rounded-md">
                            <ul className="divide-y divide-gray-200">
                                <li>
                                    <div className="px-4 py-4 flex items-center sm:px-6">
                                        <div className="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between">
                                            <div>
                                                <div className="flex text-sm">
                                                    <p className="font-medium text-blue-600 truncate">Current Session</p>
                                                    <p className="ml-1 flex-shrink-0 font-normal text-gray-500">
                                                        (This device)
                                                    </p>
                                                </div>
                                                <div className="mt-2 flex">
                                                    <div className="flex items-center text-sm text-gray-500">
                                                        <svg className="flex-shrink-0 mr-1.5 h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                        </svg>
                                                        Active now
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="mt-4 flex-shrink-0 sm:mt-0">
                                                <button 
                                                    type="button" 
                                                    className="inline-flex items-center px-3 py-1.5 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                                >
                                                    Logout
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default LoginHistory;