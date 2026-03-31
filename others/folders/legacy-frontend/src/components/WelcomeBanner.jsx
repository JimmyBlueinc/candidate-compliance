import { useState, useEffect } from 'react';
import { X, Heart, Users, Shield, Award, Target, Building2, CheckCircle2, Eye, Lock, FileCheck } from 'lucide-react';

const WelcomeBanner = () => {
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    // Check if user has dismissed the welcome banner
    const dismissed = localStorage.getItem('goodwill_welcome_dismissed');
    if (!dismissed) {
      setIsVisible(true);
    }
  }, []);

  const handleDismiss = () => {
    setIsVisible(false);
    localStorage.setItem('goodwill_welcome_dismissed', 'true');
  };

  if (!isVisible) return null;

  return (
    <div className="bg-gradient-to-br from-goodwill-primary via-goodwill-primary/95 to-goodwill-primary/90 rounded-xl shadow-lg border border-goodwill-primary/20 p-6 mb-6 relative overflow-hidden animate-fade-in-down">
      {/* Background Pattern */}
      <div className="absolute inset-0 opacity-10">
        <div className="absolute inset-0" style={{
          backgroundImage: `url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")`,
        }}></div>
      </div>

      <div className="relative z-10">
        {/* Header */}
        <div className="flex items-start justify-between mb-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
              <Building2 className="w-6 h-6 text-white" strokeWidth={2} />
            </div>
            <div>
              <h2 className="text-xl font-bold text-white">Welcome to Goodwill Staffing</h2>
              <p className="text-sm text-white/90 mt-0.5">Compliance, Security & Staff Monitoring System</p>
            </div>
          </div>
          <button
            onClick={handleDismiss}
            className="p-1.5 hover:bg-white/20 rounded-lg transition-colors text-white/80 hover:text-white"
            aria-label="Dismiss welcome message"
          >
            <X className="w-5 h-5" strokeWidth={2} />
          </button>
        </div>

        {/* Purpose / Information & About Section */}
        <div className="bg-white/10 backdrop-blur-sm rounded-xl p-5 mb-5 border border-white/30 shadow-inner">
          <div className="grid grid-cols-1 lg:grid-cols-[1.4fr,1.1fr] gap-5">
            {/* System Purpose / Information */}
            <div className="space-y-3">
              <div className="flex items-start gap-3">
                <div className="mt-0.5 p-2 rounded-lg bg-white/15 border border-white/30">
                  <Shield className="w-5 h-5 text-white" strokeWidth={2} />
                </div>
                <div>
                  <h3 className="text-base font-bold text-white tracking-wide uppercase mb-1.5">
                    System Information
                  </h3>
                  <p className="text-sm text-white/95 leading-relaxed">
                    This Compliance Tracker keeps every member of staff <strong>verified, compliant and visible</strong> at a glance.
                    It brings together security checks, HR oversight and credential monitoring into one clear, easy-to-use workspace.
                  </p>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-2">
                <div className="rounded-lg border border-white/25 bg-white/10 px-3 py-2.5">
                  <p className="text-[11px] text-white/80 font-semibold uppercase tracking-wide flex items-center gap-1.5">
                    <Eye className="w-3.5 h-3.5" strokeWidth={2} />
                    Live Oversight
                  </p>
                  <p className="mt-1 text-[11px] text-white/80 leading-snug">
                    Dashboard views keep your whole workforce status in one place.
                  </p>
                </div>
                <div className="rounded-lg border border-white/25 bg-white/10 px-3 py-2.5">
                  <p className="text-[11px] text-white/80 font-semibold uppercase tracking-wide flex items-center gap-1.5">
                    <Target className="w-3.5 h-3.5" strokeWidth={2} />
                    Zero Gaps
                  </p>
                  <p className="mt-1 text-[11px] text-white/80 leading-snug">
                    Smart alerts highlight expiring or missing credentials early.
                  </p>
                </div>
                <div className="rounded-lg border border-white/25 bg-white/10 px-3 py-2.5">
                  <p className="text-[11px] text-white/80 font-semibold uppercase tracking-wide flex items-center gap-1.5">
                    <CheckCircle2 className="w-3.5 h-3.5" strokeWidth={2} />
                    Ready to Evidence
                  </p>
                  <p className="mt-1 text-[11px] text-white/80 leading-snug">
                    Instant view of who is compliant when auditors or partners ask.
                  </p>
                </div>
              </div>
            </div>

            {/* About Goodwill Staffing */}
            <div className="rounded-xl border border-white/25 bg-gradient-to-br from-white/12 via-white/6 to-white/2 p-4 lg:p-5">
              <div className="flex items-start gap-3 mb-2.5">
                <div className="p-2 rounded-full bg-white/15 border border-white/30">
                  <Heart className="w-5 h-5 text-white" strokeWidth={2} />
                </div>
                <div>
                  <h3 className="text-sm font-semibold text-white mb-0.5">
                    About Goodwill Staffing
                  </h3>
                  <p className="text-xs text-white/90 leading-relaxed">
                    Goodwill Staffing partners with healthcare organisations to supply <strong>reliable, fully-vetted professionals</strong> who
                    meet the highest standards of patient safety, dignity and care.
                  </p>
                </div>
              </div>

              <div className="mt-2 space-y-1.5 text-xs text-white/90">
                <div className="flex items-center gap-1.5">
                  <span className="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                  <span>End‑to‑end credential checks for nurses, carers and clinical staff.</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <span className="inline-flex h-1.5 w-1.5 rounded-full bg-sky-300"></span>
                  <span>Transparent audit trail of every document, expiry and sign‑off.</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <span className="inline-flex h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                  <span>Built with HR teams in mind – quick to scan, simple to explain.</span>
                </div>
              </div>

              <div className="mt-3 rounded-lg bg-black/10 border border-white/20 px-3 py-2">
                <p className="text-[11px] text-white/85 leading-snug">
                  New here? Start by <strong>adding your candidates</strong>, uploading their core documents,
                  then use the dashboard filters to focus on what needs your attention today.
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Content Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {/* Security Verification */}
          <div className="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
            <div className="flex items-center gap-2 mb-2">
              <Lock className="w-5 h-5 text-white" strokeWidth={2} />
              <h3 className="text-sm font-semibold text-white">Security Verification</h3>
            </div>
            <p className="text-xs text-white/90 leading-relaxed">
              Comprehensive security checks and background verification to ensure all staff meet safety and security requirements. 
              All credentials are verified and monitored continuously.
            </p>
          </div>

          {/* Staff Monitoring */}
          <div className="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
            <div className="flex items-center gap-2 mb-2">
              <Eye className="w-5 h-5 text-white" strokeWidth={2} />
              <h3 className="text-sm font-semibold text-white">Staff Monitoring</h3>
            </div>
            <p className="text-xs text-white/90 leading-relaxed">
              Real-time monitoring of all staff credentials, certifications, and compliance status. 
              Automated alerts ensure immediate awareness of any compliance issues or expiring credentials.
            </p>
          </div>

          {/* Compliance Checking */}
          <div className="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
            <div className="flex items-center gap-2 mb-2">
              <FileCheck className="w-5 h-5 text-white" strokeWidth={2} />
              <h3 className="text-sm font-semibold text-white">Compliance Checking</h3>
            </div>
            <p className="text-xs text-white/90 leading-relaxed">
              Automated compliance verification against regulatory requirements. 
              Continuous checking ensures all staff maintain valid licenses, certifications, and professional qualifications.
            </p>
          </div>

          {/* Reliability Assurance */}
          <div className="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
            <div className="flex items-center gap-2 mb-2">
              <CheckCircle2 className="w-5 h-5 text-white" strokeWidth={2} />
              <h3 className="text-sm font-semibold text-white">Reliability Assurance</h3>
            </div>
            <p className="text-xs text-white/90 leading-relaxed">
              Ensuring staff reliability through comprehensive credential tracking and validation. 
              Proactive management prevents compliance gaps and maintains operational continuity.
            </p>
          </div>

          {/* HR Responsibilities */}
          <div className="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
            <div className="flex items-center gap-2 mb-2">
              <Users className="w-5 h-5 text-white" strokeWidth={2} />
              <h3 className="text-sm font-semibold text-white">HR Responsibilities</h3>
            </div>
            <p className="text-xs text-white/90 leading-relaxed">
              Centralized HR management system for credential oversight, staff verification, 
              and compliance documentation. Streamlines HR processes while maintaining regulatory compliance.
            </p>
          </div>

          {/* Quality Assurance */}
          <div className="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
            <div className="flex items-center gap-2 mb-2">
              <Award className="w-5 h-5 text-white" strokeWidth={2} />
              <h3 className="text-sm font-semibold text-white">Quality Assurance</h3>
            </div>
            <p className="text-xs text-white/90 leading-relaxed">
              Maintaining the highest quality standards through systematic monitoring, 
              verification, and continuous improvement of our compliance processes.
            </p>
          </div>
        </div>

        {/* Footer */}
        <div className="mt-4 pt-4 border-t border-white/20">
          <p className="text-xs text-white/80 text-center">
            Learn more about Goodwill Staffing at{' '}
            <a 
              href="https://goodwillstaffing.ca" 
              target="_blank" 
              rel="noopener noreferrer"
              className="text-white font-semibold hover:underline"
            >
              goodwillstaffing.ca
            </a>
          </p>
        </div>
      </div>
    </div>
  );
};

export default WelcomeBanner;

