import { CheckCircle, XCircle, AlertTriangle, Clock, FileText, Shield, Heart, Lock } from 'lucide-react';

const ComplianceBreakdown = ({ staffData, complianceScore }) => {
  const allDocs = [
    ...staffData.credentials.map(doc => ({ ...doc, type: 'Credential', category: 'Professional License' })),
    ...staffData.backgroundChecks.map(doc => ({ ...doc, type: 'Background Check', category: 'Security Clearance' })),
    ...staffData.healthRecords.map(doc => ({ ...doc, type: 'Health Record', category: 'Health Compliance' })),
    ...staffData.workAuthorizations.map(doc => ({ ...doc, type: 'Work Authorization', category: 'Legal Status' })),
  ];

  const getDocStatus = (doc) => {
    // Check expiry date
    if (doc.expiry_date) {
      const expiry = new Date(doc.expiry_date);
      const today = new Date();
      const daysUntilExpiry = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
      
      if (daysUntilExpiry < 0) {
        return { status: 'expired', label: 'Expired', color: 'red', days: Math.abs(daysUntilExpiry) };
      } else if (daysUntilExpiry <= 30) {
        return { status: 'expiring', label: `Expiring in ${daysUntilExpiry} days`, color: 'yellow', days: daysUntilExpiry };
      } else {
        return { status: 'valid', label: `Valid (${daysUntilExpiry} days remaining)`, color: 'green', days: daysUntilExpiry };
      }
    }

    // Check status fields
    const status = doc.status || doc.verification_status || 'pending';
    const statusMap = {
      active: { status: 'valid', label: 'Active', color: 'green' },
      verified: { status: 'valid', label: 'Verified', color: 'green' },
      valid: { status: 'valid', label: 'Valid', color: 'green' },
      up_to_date: { status: 'valid', label: 'Up to Date', color: 'green' },
      expired: { status: 'expired', label: 'Expired', color: 'red' },
      failed: { status: 'invalid', label: 'Failed', color: 'red' },
      revoked: { status: 'invalid', label: 'Revoked', color: 'red' },
      pending: { status: 'pending', label: 'Pending Verification', color: 'yellow' },
      pending_renewal: { status: 'expiring', label: 'Pending Renewal', color: 'yellow' },
      due: { status: 'expiring', label: 'Due', color: 'yellow' },
    };

    return statusMap[status] || { status: 'unknown', label: 'Unknown', color: 'gray' };
  };

  const validDocs = allDocs.filter(doc => {
    const docStatus = getDocStatus(doc);
    return docStatus.status === 'valid';
  });

  const expiredDocs = allDocs.filter(doc => {
    const docStatus = getDocStatus(doc);
    return docStatus.status === 'expired';
  });

  const expiringDocs = allDocs.filter(doc => {
    const docStatus = getDocStatus(doc);
    return docStatus.status === 'expiring';
  });

  const pendingDocs = allDocs.filter(doc => {
    const docStatus = getDocStatus(doc);
    return docStatus.status === 'pending';
  });

  const getCategoryIcon = (category) => {
    const icons = {
      'Professional License': FileText,
      'Security Clearance': Shield,
      'Health Compliance': Heart,
      'Legal Status': Lock,
    };
    return icons[category] || FileText;
  };

  const getComplianceStatus = () => {
    if (complianceScore >= 90) {
      return {
        status: 'Compliant',
        description: 'All required documents are valid and up-to-date',
        color: 'green',
        icon: CheckCircle,
        bgClass: 'bg-green-50',
        borderClass: 'border-green-200',
        textClass: 'text-green-700',
        textLightClass: 'text-green-600',
        iconClass: 'text-green-600',
      };
    } else if (complianceScore >= 70) {
      return {
        status: 'At Risk',
        description: 'Some documents are expiring soon or need attention',
        color: 'yellow',
        icon: AlertTriangle,
        bgClass: 'bg-yellow-50',
        borderClass: 'border-yellow-200',
        textClass: 'text-yellow-700',
        textLightClass: 'text-yellow-600',
        iconClass: 'text-yellow-600',
      };
    } else {
      return {
        status: 'Non-Compliant',
        description: 'Multiple documents are expired or missing',
        color: 'red',
        icon: XCircle,
        bgClass: 'bg-red-50',
        borderClass: 'border-red-200',
        textClass: 'text-red-700',
        textLightClass: 'text-red-600',
        iconClass: 'text-red-600',
      };
    }
  };

  const complianceStatus = getComplianceStatus();
  const StatusIcon = complianceStatus.icon;

  return (
    <div className="space-y-4">
      {/* Compliance Status Summary */}
      <div className={`${complianceStatus.bgClass} border-2 ${complianceStatus.borderClass} rounded-lg p-4`}>
        <div className="flex items-start gap-3">
          <StatusIcon className={`w-6 h-6 ${complianceStatus.iconClass} flex-shrink-0 mt-0.5`} />
          <div className="flex-1">
            <h3 className={`text-lg font-bold ${complianceStatus.textClass} mb-1`}>
              {complianceStatus.status} - {complianceScore}%
            </h3>
            <p className={`text-sm ${complianceStatus.textLightClass}`}>
              {complianceStatus.description}
            </p>
            <div className="mt-3 text-xs text-goodwill-text-muted">
              <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                <div>
                  <span className="font-semibold text-green-600">{validDocs.length}</span> Valid
                </div>
                <div>
                  <span className="font-semibold text-yellow-600">{expiringDocs.length}</span> Expiring Soon
                </div>
                <div>
                  <span className="font-semibold text-red-600">{expiredDocs.length}</span> Expired
                </div>
                <div>
                  <span className="font-semibold text-gray-600">{pendingDocs.length}</span> Pending
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* How Compliance is Calculated */}
      <div className="bg-white rounded-lg border border-goodwill-border/50 p-4">
        <h4 className="text-sm font-semibold text-goodwill-dark mb-3 flex items-center gap-2">
          <Clock className="w-4 h-4 text-goodwill-primary" />
          How Compliance is Calculated
        </h4>
        <div className="space-y-2 text-xs text-goodwill-text-muted">
          <p>
            <strong className="text-goodwill-dark">Compliance Score = (Valid Documents ÷ Total Documents) × 100</strong>
          </p>
          <div className="ml-4 space-y-1">
            <p>• <strong>Valid:</strong> Documents with expiry date in the future OR status = verified/valid/active/up_to_date</p>
            <p>• <strong>Expired:</strong> Documents with expiry date in the past</p>
            <p>• <strong>Expiring Soon:</strong> Documents expiring within 30 days</p>
            <p>• <strong>Pending:</strong> Documents awaiting verification</p>
          </div>
          <div className="mt-3 pt-3 border-t border-goodwill-border/50">
            <p className="font-semibold text-goodwill-dark mb-1">Compliance Levels:</p>
            <div className="ml-4 space-y-1">
              <p>• <span className="text-green-600 font-semibold">Compliant (≥90%):</span> All documents valid, ready for assignment</p>
              <p>• <span className="text-yellow-600 font-semibold">At Risk (70-89%):</span> Some documents need renewal, monitor closely</p>
              <p>• <span className="text-red-600 font-semibold">Non-Compliant (&lt;70%):</span> Multiple issues, action required</p>
            </div>
          </div>
        </div>
      </div>

      {/* Document Breakdown by Category */}
      <div className="space-y-3">
        <h4 className="text-sm font-semibold text-goodwill-dark">Document Breakdown</h4>
        {['Professional License', 'Security Clearance', 'Health Compliance', 'Legal Status'].map((category) => {
          const categoryDocs = allDocs.filter(doc => doc.category === category);
          if (categoryDocs.length === 0) return null;

          const CategoryIcon = getCategoryIcon(category);
          const categoryValid = categoryDocs.filter(doc => getDocStatus(doc).status === 'valid').length;
          const categoryScore = categoryDocs.length > 0 
            ? Math.round((categoryValid / categoryDocs.length) * 100) 
            : 0;

          return (
            <div key={category} className="bg-goodwill-light rounded-lg p-3 border border-goodwill-border/50">
              <div className="flex items-center justify-between mb-2">
                <div className="flex items-center gap-2">
                  <CategoryIcon className="w-4 h-4 text-goodwill-primary" />
                  <span className="text-sm font-semibold text-goodwill-dark">{category}</span>
                </div>
                <span className={`text-xs font-bold ${
                  categoryScore >= 90 ? 'text-green-600' :
                  categoryScore >= 70 ? 'text-yellow-600' :
                  'text-red-600'
                }`}>
                  {categoryScore}%
                </span>
              </div>
              <div className="text-xs text-goodwill-text-muted">
                {categoryValid} of {categoryDocs.length} documents valid
              </div>
              <div className="mt-2 space-y-1">
                {categoryDocs.map((doc, idx) => {
                  const docStatus = getDocStatus(doc);
                  const StatusIcon = docStatus.status === 'valid' ? CheckCircle :
                                    docStatus.status === 'expired' ? XCircle :
                                    docStatus.status === 'expiring' ? AlertTriangle :
                                    Clock;
                  const colorClass = docStatus.color === 'green' ? 'text-green-600' :
                                    docStatus.color === 'red' ? 'text-red-600' :
                                    docStatus.color === 'yellow' ? 'text-yellow-600' :
                                    'text-gray-600';
                  return (
                    <div key={idx} className="flex items-center justify-between text-xs bg-white rounded px-2 py-1">
                      <div className="flex items-center gap-2">
                        <StatusIcon className={`w-3 h-3 ${colorClass}`} />
                        <span className="text-goodwill-dark">{doc.type}</span>
                        {doc.credential_type && <span className="text-goodwill-text-muted">- {doc.credential_type}</span>}
                        {doc.check_type && <span className="text-goodwill-text-muted">- {doc.check_type.replace(/_/g, ' ')}</span>}
                        {doc.record_type && <span className="text-goodwill-text-muted">- {doc.record_type.replace(/_/g, ' ')}</span>}
                        {doc.authorization_type && <span className="text-goodwill-text-muted">- {doc.authorization_type.replace(/_/g, ' ')}</span>}
                      </div>
                      <span className={`${colorClass} font-medium`}>
                        {docStatus.label}
                      </span>
                    </div>
                  );
                })}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default ComplianceBreakdown;

