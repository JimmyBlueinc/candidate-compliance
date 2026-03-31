
import React from 'react';
import Navbar from './components/Navbar';
import Hero from './components/Hero';
import Features from './components/Features';
import Logos from './components/Logos';
import Testimonial from './components/Testimonial';
import CTA from './components/CTA';
import Footer from './components/Footer';

const App: React.FC = () => {
  return (
    <div className="relative min-h-screen bg-[#050507] text-white selection:bg-purple-500/30">
      {/* Background Orbs for Atmosphere */}
      <div className="orb w-[500px] h-[500px] bg-purple-900/20 top-[-100px] left-[-100px]" />
      <div className="orb w-[600px] h-[600px] bg-blue-900/10 bottom-[-200px] right-[-100px]" />
      
      <Navbar />
      
      <main className="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 pt-32 space-y-32">
        <Hero />
        <Features />
        <Logos />
        <Testimonial />
        <CTA />
      </main>
      
      <Footer />
    </div>
  );
};

export default App;
