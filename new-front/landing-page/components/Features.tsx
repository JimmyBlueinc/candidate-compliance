import React from 'react';
import { FeatureCardProps } from '../types';

const FeatureCard: React.FC<FeatureCardProps> = ({ title, description, items, icon, imageUrl, className = "" }) => (
  <div className={`glass relative overflow-hidden rounded-[32px] group hover:bg-white/5 transition-all duration-500 ${className}`}>
    {/* Background Image with Overlay */}
    {imageUrl && (
      <div className="absolute inset-0 z-0 bg-gray-900">
        <img 
          src={imageUrl} 
          alt={title} 
          className="w-full h-full object-cover opacity-20 group-hover:opacity-30 transition-opacity duration-700 group-hover:scale-110"
          loading="lazy"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-[#050507] via-[#050507]/60 to-transparent" />
      </div>
    )}

    <div className="relative z-10 p-8 h-full flex flex-col">
      <div className="mb-6">{icon}</div>
      <h3 className="text-2xl font-semibold mb-4 group-hover:text-purple-300 transition-colors">{title}</h3>
      <p className="text-gray-400 text-sm leading-relaxed mb-6">{description}</p>
      {items && (
        <ul className="space-y-3 mt-auto">
          {items.map((item, i) => (
            <li key={i} className="flex gap-2 text-sm text-gray-300">
              <span className="text-purple-500 font-bold">•</span>
              <span>{item}</span>
            </li>
          ))}
        </ul>
      )}
    </div>
  </div>
);

const Features: React.FC = () => {
  return (
    <section id="platform" className="space-y-12">
      <div className="flex flex-col md:flex-row justify-between items-end gap-6">
        <div className="space-y-4">
          <h2 className="serif text-4xl md:text-6xl text-gradient">Built for High-Stakes Compliance</h2>
          <p className="text-gray-500 max-w-xl">Precision-engineered tools to manage the complex landscape of healthcare staffing and regulatory requirements.</p>
        </div>
        <button className="text-purple-400 font-medium hover:text-purple-300 transition-colors border-b border-purple-400/30 pb-1">
          Explore all capabilities →
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Main Governance Card */}
        <FeatureCard 
          className="md:col-span-2 md:row-span-2 min-h-[400px]"
          title="Total Compliance Governance"
          description="A centralized system designed to streamline the management of healthcare credentials with unparalleled precision and audit readiness."
          imageUrl="https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&q=80&w=1200"
          items={[
            "Predictive Credentialing — Proactively tracking expirations and verifying licenses in real-time.",
            "Credential Auto-Validation — Seamlessly integrated with state databases to ensure professional integrity."
          ]}
          icon={
            <div className="w-16 h-16 glass rounded-2xl flex items-center justify-center border border-white/20">
               <svg className="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </div>
          }
        />

        {/* Credentialing Velocity Card */}
        <FeatureCard 
          title="Staff Credentialing Velocity"
          description="Accelerate your onboarding process with automated workflows that reduce bottlenecks."
          imageUrl="https://images.unsplash.com/photo-1504813184591-01592ff8178b?auto=format&fit=crop&q=80&w=800"
          icon={
            <div className="w-12 h-12 glass rounded-xl flex items-center justify-center">
              <svg className="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
          }
        />

        {/* Vault-Grade Personnel Card */}
        <FeatureCard 
          title="Vault-Grade Personnel"
          description="High-security storage for sensitive employee data and background checks."
          imageUrl="https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=800"
          icon={
             <div className="w-12 h-12 glass rounded-xl flex items-center justify-center">
              <svg className="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            </div>
          }
        />

        {/* Unified API Card */}
        <FeatureCard 
          title="Unified API"
          description="Easily connect with your existing HRIS and payroll systems via our robust API."
          imageUrl="https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&q=80&w=800"
          icon={
             <div className="w-12 h-12 glass rounded-xl flex items-center justify-center">
              <svg className="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
            </div>
          }
        />
      </div>
    </section>
  );
};

export default Features;