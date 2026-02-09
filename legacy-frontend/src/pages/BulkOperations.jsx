import { useState, useEffect } from 'react';
import { Archive, CheckSquare, Trash2, Edit, Mail, Download } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import CredentialForm from '../components/CredentialForm';
import api from '../config/api';

const BulkOperations = () => {
  const [credentials, setCredentials] = useState([]);
  const [selectedIds, setSelectedIds] = useState([]);
  const [loading, setLoading] = useState(true);
  const [operation, setOperation] = useState(null);
  const [processing, setProcessing] = useState(false);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCredential, setEditingCredential] = useState(null);

  const handleAdd = () => {
    setEditingCredential(null);
    setIsFormOpen(true);
  };

  const handleFormSuccess = async () => {
    await fetchCredentials();
  };

  useEffect(() => {
    fetchCredentials();
  }, []);

  const fetchCredentials = async () => {
    try {
      const response = await api.get('/credentials');
      setCredentials(response.data.data || response.data || []);
    } catch (err) {
      console.error('Error fetching credentials:', err);
    } finally {
      setLoading(false);
    }
  };

  const toggleSelect = (id) => {
    setSelectedIds(prev => 
      prev.includes(id) 
        ? prev.filter(i => i !== id)
        : [...prev, id]
    );
  };

  const toggleSelectAll = () => {
    if (selectedIds.length === credentials.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(credentials.map(c => c.id));
    }
  };

  const handleBulkOperation = async (op) => {
    if (selectedIds.length === 0) {
      alert('Please select at least one credential');
      return;
    }

    setOperation(op);
    setProcessing(true);

    try {
      switch (op) {
        case 'delete':
          if (!window.confirm(`Are you sure you want to delete ${selectedIds.length} credential(s)?`)) {
            setProcessing(false);
            return;
          }
          await Promise.all(selectedIds.map(id => api.delete(`/credentials/${id}`)));
          break;
        case 'status':
          // TODO: Implement bulk status update
          break;
        case 'export':
          // TODO: Implement bulk export
          break;
      }
      setSelectedIds([]);
      fetchCredentials();
      alert(`Successfully ${op === 'delete' ? 'deleted' : op} ${selectedIds.length} credential(s)`);
    } catch (err) {
      alert(`Failed to ${op} credentials: ${err.response?.data?.message || err.message}`);
    } finally {
      setProcessing(false);
      setOperation(null);
    }
  };

  if (loading) {
    return (
      <Layout>
        <div className="p-8 text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-goodwill-primary"></div>
          <p className="mt-3 text-xs text-goodwill-dark font-medium">Loading credentials...</p>
        </div>
      </Layout>
    );
  }

  return (
    <Layout onAddClick={handleAdd}>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
                <Archive className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
                Bulk Operations
              </h1>
              <p className="text-xs text-goodwill-text-muted mt-1">
                {selectedIds.length > 0 ? `${selectedIds.length} credential(s) selected` : 'Select credentials to perform bulk actions'}
              </p>
            </div>
            {selectedIds.length > 0 && (
              <div className="flex items-center gap-2">
                <button
                  onClick={() => handleBulkOperation('status')}
                  disabled={processing}
                  className="px-3 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all disabled:opacity-50 flex items-center gap-1.5"
                >
                  <Edit className="w-3.5 h-3.5" />
                  Update Status
                </button>
                <button
                  onClick={() => handleBulkOperation('export')}
                  disabled={processing}
                  className="px-3 py-2 bg-goodwill-light border border-goodwill-border text-goodwill-dark rounded-lg text-xs font-medium hover:bg-goodwill-primary hover:text-white transition-all disabled:opacity-50 flex items-center gap-1.5"
                >
                  <Download className="w-3.5 h-3.5" />
                  Export Selected
                </button>
                <button
                  onClick={() => handleBulkOperation('delete')}
                  disabled={processing}
                  className="px-3 py-2 bg-goodwill-secondary text-white rounded-lg text-xs font-medium hover:bg-goodwill-secondary/90 transition-all disabled:opacity-50 flex items-center gap-1.5"
                >
                  <Trash2 className="w-3.5 h-3.5" />
                  Delete Selected
                </button>
              </div>
            )}
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border border-goodwill-border/50 overflow-hidden">
          <div className="p-3 border-b border-goodwill-border/50 bg-goodwill-light/30">
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={selectedIds.length === credentials.length && credentials.length > 0}
                onChange={toggleSelectAll}
                className="w-4 h-4 text-goodwill-primary"
              />
              <span className="text-xs font-semibold text-goodwill-dark">Select All</span>
            </label>
          </div>
          <div className="divide-y divide-goodwill-border/30 max-h-[600px] overflow-y-auto">
            {credentials.map((cred) => (
              <div
                key={cred.id}
                className="p-3 hover:bg-goodwill-light/50 transition-colors flex items-center gap-3"
              >
                <input
                  type="checkbox"
                  checked={selectedIds.includes(cred.id)}
                  onChange={() => toggleSelect(cred.id)}
                  className="w-4 h-4 text-goodwill-primary"
                />
                <div className="flex-1 min-w-0">
                  <p className="text-xs font-semibold text-goodwill-dark truncate">{cred.candidate_name}</p>
                  <p className="text-xs text-goodwill-text-muted truncate">{cred.credential_type}</p>
                </div>
                <div className="text-right">
                  <span className={`px-2 py-0.5 rounded text-xs font-medium ${
                    cred.status === 'active' ? 'bg-green-100 text-green-700' :
                    cred.status === 'expiring_soon' ? 'bg-yellow-100 text-yellow-700' :
                    cred.status === 'expired' ? 'bg-red-100 text-red-700' :
                    'bg-gray-100 text-gray-700'
                  }`}>
                    {cred.status}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Credential Form */}
      <CredentialForm
        isOpen={isFormOpen}
        onClose={() => setIsFormOpen(false)}
        onSuccess={handleFormSuccess}
        credential={editingCredential}
      />
    </Layout>
  );
};

export default BulkOperations;

