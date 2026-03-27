
import React, { useState } from 'react';
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import ComplianceHub from './pages/ComplianceHub';
import PersonnelDatabase from './pages/PersonnelDatabase';
import Pipeline from './pages/Pipeline';
import Analytics from './pages/Analytics';
import Configuration from './pages/Configuration';
import AccessControls from './pages/AccessControls';
import { PageId } from './types';

const App: React.FC = () => {
  const [activePage, setActivePage] = useState<PageId>('compliance');

  const renderPage = () => {
    switch (activePage) {
      case 'compliance': return <ComplianceHub />;
      case 'personnel': return <PersonnelDatabase />;
      case 'pipeline': return <Pipeline />;
      case 'analytics': return <Analytics />;
      case 'config': return <Configuration />;
      case 'access': return <AccessControls />;
      default: return <ComplianceHub />;
    }
  };

  return (
    <div className="flex h-screen overflow-hidden bg-background-dark text-slate-100">
      <Sidebar activePage={activePage} onPageChange={setActivePage} />
      
      <main className="flex-1 overflow-y-auto relative flex flex-col">
        {/* Background Gradients */}
        <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-primary/10 blur-[150px] -z-10 rounded-full pointer-events-none"></div>
        <div className="absolute bottom-0 left-0 w-[400px] h-[400px] bg-accent-blue/10 blur-[120px] -z-10 rounded-full pointer-events-none"></div>
        
        <Header title={getPageTitle(activePage)} />
        
        <div className="flex-1 px-8 pb-8">
          {renderPage()}
        </div>
      </main>

      <div className="fixed top-0 left-0 w-full h-full pointer-events-none -z-20">
        <div className="absolute top-[10%] left-[20%] w-64 h-64 bg-primary/5 rounded-full blur-3xl"></div>
        <div className="absolute bottom-[20%] right-[10%] w-96 h-96 bg-accent-blue/5 rounded-full blur-3xl"></div>
      </div>
    </div>
  );
};

const getPageTitle = (id: PageId): string => {
  switch (id) {
    case 'compliance': return 'Staffing Intelligence';
    case 'personnel': return 'Personnel Database';
    case 'pipeline': return 'Credentialing Pipeline';
    case 'analytics': return 'Predictive Analytics';
    case 'config': return 'Global Configuration';
    case 'access': return 'Access Controls';
    default: return 'Dashboard';
  }
};

export default App;
