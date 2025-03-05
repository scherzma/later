// TaskCompletionAnimation.js
import React, { useState, useEffect, useRef } from 'react';
import Confetti from 'react-confetti';
import { useWindowSize } from 'react-use';
import '../animations.css';
import { useStreak } from '../context/StreakContext';

const MESSAGES = [
    "Task Completed!",
    "Great job!",
    "Way to go!",
    "You're crushing it!",
    "One down, more to conquer!",
    "Progress made!",
    "Achievement unlocked!",
    "Mission accomplished!"
];

const ENCOURAGING_NOTES = [
    "Keep up the good work!",
    "You're on a roll!",
    "Productivity level: Expert!",
    "Your future self thanks you!",
    "That's how it's done!",
    "Making it happen!",
    "Success is built one task at a time!",
    "Momentum builds with each completion!"
];

const TaskCompletionAnimation = ({ show, onComplete }) => {
    const { width, height } = useWindowSize();
    const [isActive, setIsActive] = useState(false);
    const [message, setMessage] = useState("");
    const [encouragingNote, setEncouragingNote] = useState("");
    const [starParticles, setStarParticles] = useState([]);
    const containerRef = useRef(null);
    
    // Use streak context hook
    const { streakInfo } = useStreak();

    // Generate random message
    useEffect(() => {
        if (show) {
            setMessage(MESSAGES[Math.floor(Math.random() * MESSAGES.length)]);
            setEncouragingNote(ENCOURAGING_NOTES[Math.floor(Math.random() * ENCOURAGING_NOTES.length)]);
            setIsActive(true);

            // Create star particles
            const particles = [];
            for (let i = 0; i < 15; i++) {
                particles.push({
                    id: i,
                    size: Math.random() * 10 + 5,
                    left: Math.random() * 80 + 10 + '%',
                    top: Math.random() * 80 + 10 + '%',
                    delay: Math.random() * 0.5
                });
            }
            setStarParticles(particles);

            // Play success sound if available
            try {
                const audio = new Audio('/success.mp3'); // You'd need to add this file to your public folder
                audio.volume = 0.5;
                audio.play().catch(e => console.log('Audio play failed:', e));
            } catch (error) {
                console.log('Audio not supported');
            }

            // Automatically hide after animation completes
            const timer = setTimeout(() => {
                setIsActive(false);
                if (onComplete) onComplete();
            }, 2800);

            return () => clearTimeout(timer);
        }
    }, [show, onComplete]);

    if (!isActive) return null;

    return (
        <>
            {/* Confetti overlay */}
            <Confetti
                width={width}
                height={height}
                recycle={false}
                numberOfPieces={300}
                gravity={0.3}
                colors={['#FFC107', '#4CAF50', '#2196F3', '#E91E63', '#9C27B0']}
            />

            {/* Star particles */}
            {starParticles.map(particle => (
                <div
                    key={particle.id}
                    className="star-particle"
                    style={{
                        width: particle.size,
                        height: particle.size,
                        left: particle.left,
                        top: particle.top,
                        animationDelay: `${particle.delay}s`
                    }}
                />
            ))}

            {/* Celebratory message */}
            <div className="fixed inset-0 flex items-center justify-center z-50 pointer-events-none">
                <div ref={containerRef} className="bg-white bg-opacity-90 rounded-lg p-8 shadow-lg scale-in-center">
                    <div className="text-center">
                        <div className="inline-block bg-green-100 rounded-full p-4 mb-4 relative">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-16 w-16 text-green-600 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                            </svg>

                            {/* Additional animated elements around the checkmark */}
                            <span className="absolute top-0 right-0 h-3 w-3 bg-yellow-400 rounded-full animate-ping"></span>
                            <span className="absolute bottom-0 left-0 h-3 w-3 bg-blue-400 rounded-full animate-ping" style={{ animationDelay: '0.5s' }}></span>
                            <span className="absolute top-0 left-5 h-2 w-2 bg-purple-400 rounded-full animate-ping" style={{ animationDelay: '0.7s' }}></span>
                        </div>
                        <h2 className="text-3xl font-bold text-gray-800 mb-2">{message}</h2>
                        <p className="text-gray-600 text-xl">{encouragingNote}</p>

                        {/* Streak counter */}
                        <div className="mt-4 bg-blue-50 rounded-lg py-2 px-4 inline-block">
                            <span className="text-blue-800 font-medium">
                                🔥 Task streak: {streakInfo.currentStreak || 0}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default TaskCompletionAnimation;