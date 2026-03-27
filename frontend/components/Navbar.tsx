
import React from 'react';
import { useNavigate } from 'react-router-dom';

const Navbar: React.FC = () => {
  const links = ['Platform', 'Products', 'Intelligence', 'Resources', 'Blog'];
  const navigate = useNavigate();

  return (
    <nav className="fixed top-0 left-0 right-0 z-50 border-b border-white/5 bg-[#050507]/80 backdrop-blur-md">
      <div className="max-w-7xl mx-auto px-6 sm:px-12 h-20 flex items-center justify-between">
        <div className="flex items-center gap-12">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 bg-gradient-to-tr from-purple-600 to-blue-400 rounded-lg flex items-center justify-center">
              <span className="text-white font-bold text-lg">H</span>
            </div>
            <span className="text-xl font-semibold tracking-tight">HealthFlow</span>
          </div>
          
          <div className="hidden md:flex items-center gap-8">
            {links.map((link) => (
              <a 
                key={link} 
                href={`#${link.toLowerCase()}`} 
                className="text-sm text-gray-400 hover:text-white transition-colors"
              >
                {link}
              </a>
            ))}
          </div>
        </div>

        <div className="flex items-center gap-6">
          <button 
            onClick={() => navigate('/login')}
            className="text-sm text-gray-400 hover:text-white transition-colors"
          >
            Log In
          </button>
          <button className="px-5 py-2 rounded-full border border-white/20 text-sm hover:bg-white hover:text-black transition-all">
            Contact Us
          </button>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
