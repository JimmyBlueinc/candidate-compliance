import React from 'react';

const Hero: React.FC = () => {
  return (
    <section className="relative grid lg:grid-cols-2 gap-16 items-center">
      <div className="space-y-8 z-10 animate-[fadeInUp_700ms_ease-out_both]">
        <h1 className="serif text-4xl sm:text-5xl md:text-6xl leading-tight tracking-tight text-gradient max-w-2xl">
          Elite Compliance for Healthcare Staffing Agencies.
        </h1>
        <p className="text-gray-400 text-lg md:text-xl max-w-xl leading-relaxed">
          Track every detail of your medical personnel to ensure excellence and trust.
        </p>
        
        <div className="flex flex-wrap gap-4 pt-4">
          <button className="px-8 py-4 bg-white text-black font-semibold rounded-full hover:bg-purple-50 transition-all shadow-xl shadow-purple-500/10 hover:-translate-y-0.5 hover:shadow-2xl hover:shadow-purple-500/20">
            Experience HealthFlow
          </button>
          <button className="px-8 py-4 bg-white/5 border border-white/10 rounded-full font-semibold hover:bg-white/10 transition-all hover:-translate-y-0.5">
            Platform Tour
          </button>
        </div>

        <div className="pt-8 flex items-baseline gap-2">
          <span className="text-4xl font-bold text-purple-400">95%</span>
          <span className="text-gray-500 font-medium">Retention Rate</span>
        </div>
      </div>

      <div className="relative flex justify-center lg:justify-end animate-[float_6s_ease-in-out_infinite]">
        {/* Orbital Shield Graphic */}
        <div className="relative w-80 h-80 md:w-[450px] md:h-[450px]">
          {/* Main Glow */}
          <div className="absolute inset-0 bg-purple-500/20 blur-[120px] rounded-full animate-pulse" />
          
          {/* Shield Placeholder - Layered elements to mimic 3D depth */}
          <div className="absolute inset-0 flex items-center justify-center">
            <div className="relative w-full h-full flex items-center justify-center">
              {/* Outer Ring */}
              <div className="absolute w-[90%] h-[90%] border border-white/10 rounded-full animate-[spin_20s_linear_infinite]" />
              <div className="absolute w-[70%] h-[70%] border border-purple-500/20 rounded-full animate-[spin_15s_linear_infinite_reverse]" />
              
              {/* Central Shield with Real Image */}
              <div className="relative z-10 w-48 h-64 md:w-64 md:h-80 glass rounded-[40px] flex items-center justify-center shadow-2xl shadow-purple-500/30 overflow-hidden border border-white/20 bg-gray-900">
                 <img 
                    src="https://images.unsplash.com/photo-1551601651-2a8555f1a136?auto=format&fit=crop&q=80&w=800" 
                    alt="Healthcare Professional" 
                    className="absolute inset-0 w-full h-full object-cover opacity-60 grayscale hover:grayscale-0 transition-all duration-700"
                    loading="lazy"
                 />
                 <div className="absolute inset-0 bg-gradient-to-t from-purple-900/80 to-transparent" />
                 <div className="relative z-20 flex flex-col items-center">
                    <svg viewBox="0 0 24 24" fill="none" className="w-20 h-20 text-white mb-4" stroke="currentColor" strokeWidth="1.5">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                    <span className="text-white text-xs font-bold tracking-[0.2em] uppercase">Verified Secured</span>
                 </div>
              </div>

              {/* Orbiting Nodes */}
              <div className="absolute w-14 h-14 bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl flex items-center justify-center -top-4 left-1/2 -translate-x-1/2 shadow-lg shadow-purple-500/20 group hover:scale-110 transition-transform">
                <svg className="w-6 h-6 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
              </div>
              <div className="absolute w-14 h-14 bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl flex items-center justify-center top-1/2 -right-4 -translate-y-1/2 shadow-lg shadow-blue-500/20 group hover:scale-110 transition-transform">
                <svg className="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
              </div>
              <div className="absolute w-14 h-14 bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl flex items-center justify-center bottom-12 -left-4 shadow-lg shadow-purple-500/20 group hover:scale-110 transition-transform">
                <svg className="w-6 h-6 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Hero;