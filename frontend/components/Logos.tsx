
import React from 'react';

const Logos: React.FC = () => {
  const partners = ['MEDCORE', 'SAFEHAVEN', 'BIOQUANTUM', 'VITALIS'];

  return (
    <section className="py-20 border-y border-white/5 space-y-12">
      <h3 className="text-gray-500 uppercase tracking-widest text-center text-sm font-semibold">Trusted by Leading Institutions</h3>
      <div className="flex flex-wrap justify-center items-center gap-12 md:gap-24 opacity-60">
        {partners.map((partner) => (
          <div key={partner} className="text-2xl font-bold tracking-tighter text-white/50 hover:text-white transition-all cursor-default">
            {partner}
          </div>
        ))}
      </div>
    </section>
  );
};

export default Logos;
