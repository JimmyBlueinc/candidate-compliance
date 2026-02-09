
import React from 'react';

const CTA: React.FC = () => {
  return (
    <section className="relative overflow-hidden rounded-[40px]">
      <div className="absolute inset-0 bg-gradient-to-r from-blue-900/40 via-purple-900/40 to-blue-900/40 blur-3xl opacity-50" />
      
      <div className="relative glass p-16 md:p-24 flex flex-col items-center text-center space-y-10 border-white/20">
        <h2 className="serif text-4xl md:text-6xl max-w-3xl">Ready for the Next Generation of Compliance?</h2>
        <div className="flex flex-wrap justify-center gap-6">
          <button className="px-10 py-5 bg-white text-black font-bold rounded-full hover:scale-105 transition-transform shadow-2xl shadow-white/10">
            Start Your Pilot
          </button>
          <button className="px-10 py-5 bg-white/10 border border-white/20 rounded-full font-bold hover:bg-white/20 transition-all">
            Request Enterprise Demo
          </button>
        </div>
      </div>
    </section>
  );
};

export default CTA;
