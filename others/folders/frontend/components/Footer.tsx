
import React from 'react';

const Footer: React.FC = () => {
  return (
    <footer className="bg-[#050507] border-t border-white/5 pt-20 pb-10 px-6 sm:px-12 mt-0">
      <div className="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-12">
        <div className="space-y-6">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 bg-gradient-to-tr from-purple-600 to-blue-400 rounded-lg" />
            <span className="text-xl font-bold">HealthFlow</span>
          </div>
          <p className="text-gray-500 text-sm leading-relaxed">
            Elevating healthcare staffing through automated compliance and intelligent credentialing.
          </p>
        </div>

        <div>
          <h4 className="font-semibold mb-6">Platform</h4>
          <ul className="space-y-4 text-sm text-gray-500">
            <li><a href="#" className="hover:text-white transition-colors">About</a></li>
            <li><a href="#" className="hover:text-white transition-colors">Intelligence</a></li>
            <li><a href="#" className="hover:text-white transition-colors">Solution</a></li>
          </ul>
        </div>

        <div>
          <h4 className="font-semibold mb-6">Intelligence</h4>
          <ul className="space-y-4 text-sm text-gray-500">
            <li><a href="#" className="hover:text-white transition-colors">Assets</a></li>
            <li><a href="#" className="hover:text-white transition-colors">Events</a></li>
            <li><a href="#" className="hover:text-white transition-colors">Contact</a></li>
            <li><a href="#" className="hover:text-white transition-colors">Privacy Policy</a></li>
          </ul>
        </div>

        <div className="space-y-6">
          <h4 className="font-semibold">Join our Newsletter</h4>
          <div className="flex gap-2">
            <input 
              type="email" 
              placeholder="Email" 
              className="bg-white/5 border border-white/10 rounded-full px-6 py-3 text-sm flex-1 focus:outline-none focus:border-purple-500 transition-colors"
            />
            <button className="px-6 py-3 bg-blue-600 hover:bg-blue-500 rounded-full text-sm font-bold transition-all shadow-lg shadow-blue-500/20">
              Submit
            </button>
          </div>
        </div>
      </div>
      
      <div className="max-w-7xl mx-auto mt-20 pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-600">
        <p>© 2024 HealthFlow Systems Inc. All rights reserved.</p>
        <div className="flex gap-8">
          <a href="#" className="hover:text-white transition-colors">Terms</a>
          <a href="#" className="hover:text-white transition-colors">Privacy</a>
          <a href="#" className="hover:text-white transition-colors">Cookies</a>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
