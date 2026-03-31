
import React from 'react';

const Testimonial: React.FC = () => {
  return (
    <section className="relative glass p-12 md:p-20 rounded-[40px] overflow-hidden">
      <div className="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-purple-500/5 to-transparent pointer-events-none" />
      
      <div className="relative z-10 max-w-4xl mx-auto text-center space-y-8">
        <div className="text-purple-400/30 text-8xl serif absolute top-10 left-10 select-none">“</div>
        <p className="text-2xl md:text-3xl italic text-gray-200 leading-relaxed font-light serif">
          "HealthFlow has revolutionized how we handle clinician onboarding. The predictive credentialing saves our team hundreds of hours every month, allowing us to focus on what matters most: patient care."
        </p>
        <div className="space-y-2">
          <div className="font-semibold text-lg">— Dr. Sarah Jenkins</div>
          <div className="text-gray-500 text-sm">Director of Operations, Medcore General</div>
        </div>
        <div className="text-purple-400/30 text-8xl serif absolute bottom-0 right-10 select-none">”</div>
      </div>
    </section>
  );
};

export default Testimonial;
