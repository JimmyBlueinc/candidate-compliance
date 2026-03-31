/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence, useScroll, useTransform } from 'motion/react';
import { 
  CheckCircle2, 
  ShieldCheck, 
  Users, 
  Zap, 
  Globe, 
  ArrowRight, 
  Menu, 
  X, 
  ChevronRight,
  Clock,
  Lock,
  BarChart3,
  Stethoscope,
  Calendar,
  HeartPulse,
  Briefcase,
  ClipboardCheck,
  Activity,
  LayoutDashboard,
  Database,
  Layers
} from 'lucide-react';

const Navbar = () => {
  const [isScrolled, setIsScrolled] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  useEffect(() => {
    const handleScroll = () => setIsScrolled(window.scrollY > 20);
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  return (
    <nav className={`fixed top-0 left-0 right-0 z-50 transition-all duration-500 ${isScrolled ? 'bg-white/70 backdrop-blur-xl border-b border-blue-100/50 py-3' : 'bg-transparent py-6'}`}>
      <div className="max-w-7xl mx-auto px-6 flex items-center justify-between">
        <div className="flex items-center gap-2 group cursor-pointer">
          <div className="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center transition-transform group-hover:rotate-12">
            <Activity className="text-white w-5 h-5" />
          </div>
          <span className="text-xl font-bold tracking-tighter text-blue-950">AgencyHQ</span>
        </div>

        <div className="hidden md:flex items-center gap-10">
          {['Platform', 'Solutions', 'Customers', 'Pricing'].map((item) => (
            <a key={item} href="#" className="text-[13px] font-medium text-slate-500 hover:text-blue-600 transition-colors">
              {item}
            </a>
          ))}
        </div>

        <div className="hidden md:flex items-center gap-4">
          <button className="text-[13px] font-medium text-slate-500 hover:text-blue-600 px-4">Log in</button>
          <button className="btn-primary text-[13px] py-2.5 px-6">Get Started</button>
        </div>

        <button className="md:hidden p-2" onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}>
          {isMobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
        </button>
      </div>

      <AnimatePresence>
        {isMobileMenuOpen && (
          <motion.div 
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            className="absolute top-full left-0 right-0 bg-white border-b border-blue-100 p-8 flex flex-col gap-6 md:hidden shadow-2xl"
          >
            {['Platform', 'Solutions', 'Customers', 'Pricing'].map((item) => (
              <a key={item} href="#" className="text-xl font-semibold text-blue-950">
                {item}
              </a>
            ))}
            <div className="pt-4 flex flex-col gap-4">
              <button className="btn-secondary w-full">Log in</button>
              <button className="btn-primary w-full">Get Started</button>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </nav>
  );
};

const LogoCloud = () => {
  return (
    <div className="mt-20">
      <p className="text-center text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400 mb-8">
        Trusted by industry leaders
      </p>
      <div className="flex flex-wrap justify-center items-center gap-x-12 gap-y-8 opacity-40 grayscale transition-all hover:opacity-70">
        {['Acme Health', 'Global Care', 'Nova Medical', 'Unity Staffing', 'Apex Nursing'].map((name) => (
          <span key={name} className="text-xl font-black italic tracking-tighter text-blue-900">{name}</span>
        ))}
      </div>
    </div>
  );
};

const Hero = () => {
  return (
    <section className="relative pt-40 pb-20 px-6 bg-grid">
      <div className="absolute inset-0 bg-gradient-to-b from-white via-transparent to-white pointer-events-none" />
      
      <div className="max-w-7xl mx-auto relative">
        <div className="text-center max-w-4xl mx-auto mb-20">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
          >
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-100 text-blue-600 text-[11px] font-bold uppercase tracking-wider mb-8">
              <span className="w-1.5 h-1.5 rounded-full bg-blue-600" />
              v2.0 is now live
            </div>
            <h1 className="text-6xl md:text-8xl font-bold tracking-tighter leading-[0.9] mb-8 text-gradient">
              The operating system for healthcare staffing.
            </h1>
            <p className="text-xl md:text-2xl text-slate-500 mb-10 max-w-2xl mx-auto leading-tight font-medium">
              AgencyHQ is the central command for modern healthcare agencies. Scale your operations with automated scheduling, credentialing, and payroll.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <button className="btn-primary px-10 py-4 text-base flex items-center gap-2">
                Start your trial <ArrowRight className="w-4 h-4" />
              </button>
              <button className="btn-secondary px-10 py-4 text-base">
                View Demo
              </button>
            </div>
          </motion.div>
        </div>

        <motion.div
          initial={{ opacity: 0, y: 40 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8, delay: 0.2 }}
          className="relative max-w-6xl mx-auto"
        >
          <div className="relative rounded-2xl border border-blue-100 shadow-[0_0_100px_rgba(30,58,138,0.05)] overflow-hidden bg-white aspect-[16/10] md:aspect-[16/9]">
            <div className="absolute inset-0 bg-blue-50/30" />
            
            {/* Sidebar Mockup */}
            <div className="absolute left-0 top-0 bottom-0 w-64 border-r border-blue-50 bg-white hidden md:block p-6">
              <div className="flex items-center gap-2 mb-10">
                <div className="w-6 h-6 bg-blue-600 rounded-md" />
                <div className="w-24 h-3 bg-blue-50 rounded-full" />
              </div>
              <div className="space-y-6">
                {[1, 2, 3, 4, 5].map(i => (
                  <div key={i} className="flex items-center gap-3">
                    <div className="w-4 h-4 bg-blue-50/50 rounded" />
                    <div className={`h-2 bg-blue-50 rounded-full ${i === 1 ? 'w-20' : 'w-16'}`} />
                  </div>
                ))}
              </div>
            </div>

            {/* Main Content Mockup */}
            <div className="absolute left-0 md:left-64 right-0 top-0 bottom-0 p-8">
              <div className="flex justify-between items-center mb-10">
                <div className="w-32 h-6 bg-blue-50 rounded-lg" />
                <div className="flex gap-2">
                  <div className="w-8 h-8 rounded-full bg-blue-50/50" />
                  <div className="w-8 h-8 rounded-full bg-blue-50/50" />
                </div>
              </div>
              <div className="grid grid-cols-3 gap-6 mb-10">
                {[1, 2, 3].map(i => (
                  <div key={i} className="h-24 rounded-2xl border border-blue-50 bg-white p-4">
                    <div className="w-8 h-2 bg-blue-50/50 rounded-full mb-3" />
                    <div className="w-16 h-4 bg-blue-50 rounded-full" />
                  </div>
                ))}
              </div>
              <div className="space-y-4">
                {[1, 2, 3, 4].map(i => (
                  <div key={i} className="h-16 rounded-xl border border-blue-50 bg-white flex items-center px-6 justify-between">
                    <div className="flex items-center gap-4">
                      <div className="w-8 h-8 rounded-full bg-blue-50/50" />
                      <div className="w-32 h-3 bg-blue-50 rounded-full" />
                    </div>
                    <div className="w-16 h-2 bg-blue-50/50 rounded-full" />
                  </div>
                ))}
              </div>
            </div>
          </div>
          
          {/* Floating UI Elements */}
          <motion.div 
            animate={{ y: [0, -10, 0] }}
            transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
            className="absolute -right-12 top-1/4 glass-card p-4 rounded-2xl hidden lg:block w-48"
          >
            <div className="flex items-center gap-3 mb-3">
              <div className="w-2 h-2 rounded-full bg-emerald-500" />
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Live Fill Rate</span>
            </div>
            <div className="text-2xl font-bold text-blue-950">94.2%</div>
            <div className="mt-2 h-1 w-full bg-blue-50 rounded-full overflow-hidden">
              <div className="h-full bg-emerald-500 w-[94%]" />
            </div>
          </motion.div>

          <motion.div 
            animate={{ y: [0, 10, 0] }}
            transition={{ duration: 5, repeat: Infinity, ease: "easeInOut", delay: 1 }}
            className="absolute -left-12 bottom-1/4 glass-card p-4 rounded-2xl hidden lg:block w-56"
          >
            <div className="flex items-center gap-3 mb-3">
              <Clock className="w-3 h-3 text-blue-600" />
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Next Payroll</span>
            </div>
            <div className="text-lg font-bold text-blue-950">In 2 days</div>
            <div className="text-[10px] text-slate-400 mt-1">142 timesheets pending</div>
          </motion.div>
        </motion.div>

        <LogoCloud />
      </div>
    </section>
  );
};

const Features = () => {
  const features = [
    {
      icon: <LayoutDashboard className="w-5 h-5" />,
      title: "Command Center",
      description: "A unified dashboard to monitor every shift, credential, and invoice in real-time.",
      className: "md:col-span-2 md:row-span-2"
    },
    {
      icon: <Database className="w-5 h-5" />,
      title: "Smart Matching",
      description: "Match the right staff to the right facility based on skills, distance, and historical performance.",
      className: "md:col-span-1 md:row-span-1"
    },
    {
      icon: <Layers className="w-5 h-5" />,
      title: "Auto-Compliance",
      description: "Automated license checks and document tracking that never sleeps.",
      className: "md:col-span-1 md:row-span-1"
    },
    {
      icon: <Zap className="w-5 h-5" />,
      title: "Instant Payments",
      description: "Streamline your cash flow with automated invoicing and staff payments.",
      className: "md:col-span-3 md:row-span-1"
    }
  ];

  return (
    <section className="py-32 px-6">
      <div className="max-w-7xl mx-auto">
        <div className="mb-20">
          <h2 className="text-4xl md:text-6xl font-bold tracking-tighter mb-6 text-blue-950">Built for scale.</h2>
          <p className="text-xl text-slate-500 max-w-2xl font-medium">
            AgencyHQ replaces your fragmented tools with a single, cohesive platform designed for high-growth staffing agencies.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {features.map((feature, i) => (
            <div key={i} className={`p-10 rounded-[2rem] border border-blue-50 bg-white hover:border-blue-200 transition-all group ${feature.className}`}>
              <div className="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-8 group-hover:bg-blue-600 group-hover:text-white transition-colors text-blue-600">
                {feature.icon}
              </div>
              <h3 className="text-2xl font-bold mb-4 tracking-tight text-blue-950">{feature.title}</h3>
              <p className="text-slate-500 leading-snug font-medium">{feature.description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

const Stats = () => {
  return (
    <section className="py-32 px-6 border-y border-blue-50">
      <div className="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-12">
        {[
          { label: 'Shifts Managed', value: '2.4M+' },
          { label: 'Active Staff', value: '45k+' },
          { label: 'Time Saved', value: '60%' },
          { label: 'Fill Rate', value: '98.2%' },
        ].map((stat, i) => (
          <div key={i} className="text-center">
            <div className="text-4xl md:text-6xl font-bold tracking-tighter mb-2 text-blue-950">{stat.value}</div>
            <div className="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">{stat.label}</div>
          </div>
        ))}
      </div>
    </section>
  );
};

const CTA = () => {
  return (
    <section className="py-40 px-6">
      <div className="max-w-4xl mx-auto text-center">
        <h2 className="text-5xl md:text-8xl font-bold tracking-tighter mb-10 text-gradient">
          Ready to lead the industry?
        </h2>
        <p className="text-xl md:text-2xl text-slate-500 mb-12 font-medium">
          Join the agencies that are defining the future of healthcare staffing.
        </p>
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <button className="btn-primary px-12 py-5 text-lg">Get Started Now</button>
          <button className="btn-secondary px-12 py-5 text-lg">Contact Sales</button>
        </div>
      </div>
    </section>
  );
};

const Footer = () => {
  return (
    <footer className="py-20 px-6 border-t border-blue-50 bg-blue-50/20">
      <div className="max-w-7xl mx-auto">
        <div className="grid grid-cols-2 md:grid-cols-5 gap-12 mb-20">
          <div className="col-span-2">
            <div className="flex items-center gap-2 mb-6">
              <Activity className="w-6 h-6 text-blue-600" />
              <span className="text-xl font-bold tracking-tighter text-blue-950">AgencyHQ</span>
            </div>
            <p className="text-slate-500 text-sm max-w-xs font-medium leading-relaxed">
              The operating system for modern healthcare staffing agencies. Built in San Francisco.
            </p>
          </div>
          <div>
            <h4 className="text-[11px] font-bold uppercase tracking-widest text-blue-950 mb-6">Product</h4>
            <ul className="space-y-4 text-sm text-slate-500 font-medium">
              <li><a href="#" className="hover:text-blue-600 transition-colors">Platform</a></li>
              <li><a href="#" className="hover:text-blue-600 transition-colors">Integrations</a></li>
              <li><a href="#" className="hover:text-blue-600 transition-colors">Security</a></li>
              <li><a href="#" className="hover:text-blue-600 transition-colors">Pricing</a></li>
            </ul>
          </div>
          <div>
            <h4 className="text-[11px] font-bold uppercase tracking-widest text-blue-950 mb-6">Company</h4>
            <ul className="space-y-4 text-sm text-slate-500 font-medium">
              <li><a href="#" className="hover:text-blue-600 transition-colors">About</a></li>
              <li><a href="#" className="hover:text-blue-600 transition-colors">Blog</a></li>
              <li><a href="#" className="hover:text-blue-600 transition-colors">Careers</a></li>
              <li><a href="#" className="hover:text-blue-600 transition-colors">Contact</a></li>
            </ul>
          </div>
          <div>
            <h4 className="text-[11px] font-bold uppercase tracking-widest text-blue-950 mb-6">Social</h4>
            <ul className="space-y-4 text-sm text-slate-500 font-medium">
              <li><a href="#" className="hover:text-blue-600 transition-colors">Twitter</a></li>
              <li><a href="#" className="hover:text-blue-600 transition-colors">LinkedIn</a></li>
              <li><a href="#" className="hover:text-blue-600 transition-colors">GitHub</a></li>
            </ul>
          </div>
        </div>
        <div className="flex flex-col md:flex-row justify-between items-center pt-10 border-t border-blue-100/50 text-[11px] font-bold uppercase tracking-widest text-slate-400">
          <p>© 2026 AgencyHQ Inc.</p>
          <div className="flex gap-8 mt-6 md:mt-0">
            <a href="#" className="hover:text-blue-600 transition-colors">Privacy</a>
            <a href="#" className="hover:text-blue-600 transition-colors">Terms</a>
            <a href="#" className="hover:text-blue-600 transition-colors">Cookies</a>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default function App() {
  return (
    <div className="min-h-screen">
      <Navbar />
      <Hero />
      <Features />
      <Stats />
      <CTA />
      <Footer />
    </div>
  );
}
