
import React from 'react';

const PersonnelDatabase: React.FC = () => {
  const staff = [
    { id: '1', name: 'Dr. Michael Chen', role: 'Physician', status: 'Active', compliance: '100%' },
    { id: '2', name: 'Lisa Ray', role: 'Registered Nurse', status: 'Active', compliance: '92%' },
    { id: '3', name: 'Sarah Jenkins', role: 'Compliance Officer', status: 'Active', compliance: '100%' },
    { id: '4', name: 'David Smith', role: 'Medical Tech', status: 'Pending', compliance: '65%' },
    { id: '5', name: 'Emma Wilson', role: 'Lead Nurse', status: 'Active', compliance: '98%' },
  ];

  return (
    <div className="glass-dark rounded-3xl overflow-hidden border border-white/5">
      <table className="w-full text-left">
        <thead>
          <tr className="border-b border-white/5 bg-white/5">
            <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Name</th>
            <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Role</th>
            <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Status</th>
            <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Compliance</th>
            <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Actions</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-white/5">
          {staff.map(person => (
            <tr key={person.id} className="hover:bg-white/5 transition-colors group">
              <td className="px-6 py-4 font-medium text-slate-200">{person.name}</td>
              <td className="px-6 py-4 text-slate-400">{person.role}</td>
              <td className="px-6 py-4">
                <span className={`px-2 py-1 rounded-full text-[10px] font-bold uppercase ${
                  person.status === 'Active' ? 'bg-green-500/10 text-green-500' : 'bg-amber-500/10 text-amber-500'
                }`}>
                  {person.status}
                </span>
              </td>
              <td className="px-6 py-4 text-slate-200 font-bold">{person.compliance}</td>
              <td className="px-6 py-4">
                <button className="text-primary hover:text-primary/80 text-sm font-semibold transition-colors">View Details</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default PersonnelDatabase;
