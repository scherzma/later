import React from 'react';

function Footer() {
  const currentYear = new Date().getFullYear();
  
  return (
    <footer className="bg-gradient-to-r from-gray-800 to-gray-900 text-white py-8">
      <div className="container mx-auto px-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div>
            <h3 className="text-xl font-bold mb-4">Later</h3>
            <p className="text-gray-300">
              A modern task management application designed to help you organize your work
              and personal tasks effectively.
            </p>
          </div>
          
          <div>
            <h3 className="text-xl font-bold mb-4">Features</h3>
            <ul className="text-gray-300 space-y-2">
              <li>Task prioritization</li>
              <li>Tag management</li>
              <li>Due date tracking</li>
              <li>Productivity streaks</li>
              <li>Task postponement</li>
            </ul>
          </div>
          
          <div>
            <h3 className="text-xl font-bold mb-4">Contact</h3>
            <p className="text-gray-300">
              Questions or feedback? Reach out to us.
            </p>
            <div className="mt-2">
              <a href="mailto:info@later-app.example.com" className="text-blue-400 hover:text-blue-300 transition">
                info@later-app.example.com
              </a>
            </div>
          </div>
        </div>
        
        <div className="mt-8 pt-6 border-t border-gray-700 text-center text-gray-400">
          <p>&copy; {currentYear} Later App. All rights reserved.</p>
        </div>
      </div>
    </footer>
  );
}

export default Footer;