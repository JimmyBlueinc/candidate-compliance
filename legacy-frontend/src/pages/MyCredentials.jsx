import { useState, useEffect } from 'react';
import { FileText, Plus, Edit, Trash2, Download, Calendar, AlertCircle } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import CredentialForm from '../components/CredentialForm';
import StatusTag from '../components/StatusTag';
import api from '../config/api';
import { useAuth } from '../contexts/AuthContext';
import { CSVLink } from 'react-csv';

const MyCredentials = () => {
  const { user } = useAuth();
  const [credentials, setCredentials] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCredential, setEditingCredential] = useState(null);
  const [deletingId, setDeletingId] = useState(null);

  useEffect(() => {
    fetchMyCredentials();
  }, []);

  const fetchMyCredentials = async () => {
    try {
      setLoading(true);
      // Fetch credentials where the candidate email matches the user's email
      const response = await api.get('/credentials', {
        params: { email: user?.email }
      });
      setCredentials(response.data.data || response.data || []);
      setError(null);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to fetch credentials');
      console.error('Error fetching credentials:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleAdd = () => {
    setEditingCredential(null);
    setIsFormOpen(true);
  };

  const handleEdit = (credential) => {
    setEditingCredential(credential);
    setIsFormOpen(true);
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure you want to delete this credential?')) {
      return;
    }

    setDeletingId(id);
    try {
      await api.delete(`/credentials/${id}`);
      fetchMyCredentials();
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to delete credential');
    } finally {
      setDeletingId(null);
    }
  };

  const handleFormClose = () => {
    setIsFormOpen(false);
    setEditingCredential(null);
    fetchMyCredentials();
  };

  // Prepare CSV data
  const csvData = credentials.map(cred => ({
    'Credential Type': cred.credential_type,
    'Issue Date': cred.issue_date ? new Date(cred.issue_date).toLocaleDateString() : '-',
    'Expiry Date': cred.expiry_date ? new Date(cred.expiry_date).toLocaleDateString() : '-',
    'Status': cred.status,
  }));

  // Calculate statistics
  const stats = credentials.reduce((acc, cred) => {
    if (!cred.expiry_date) {
      acc.pending++;
      return acc;
    }

    const now = new Date();
    now.setHours(0, 0, 0, 0);
    const expiryDate = new Date(cred.expiry_date);
    expiryDate.setHours(0, 0, 0, 0);
    const daysUntilExpiry = Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24));

    if (daysUntilExpiry <= 0) {
      acc.expired++;
    } else if (daysUntilExpiry <= 30) {
      acc.expiringSoon++;
    } else {
      acc.active++;
    }

    return acc;
  }, { active: 0, expiringSoon: 0, expired: 0, pending: 0 });

  return (
    <Layout>
      <div className="space-y-4 animate-fade-in-up">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
                <FileText className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
                My Credentials
              </h1>
              <p className="text-xs text-goodwill-text-muted mt-1">Manage your own credentials</p>
            </div>
            <div className="flex items-center gap-2">
              <CSVLink
                data={csvData}
                filename={`my-credentials-${new Date().toISOString().split('T')[0]}.csv`}
                className="px-3 py-2 bg-goodwill-light border border-goodwill-border rounded-lg text-xs font-medium text-goodwill-dark hover:bg-goodwill-primary hover:text-white transition-all duration-300 flex items-center gap-1.5 hover:scale-105 active:scale-95"
              >
                <Download className="w-3.5 h-3.5" strokeWidth={2} />
                Export CSV
              </CSVLink>
              <button
                onClick={handleAdd}
                className="px-3 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all duration-300 flex items-center gap-1.5 hover:scale-105 active:scale-95"
              >
                <Plus className="w-3.5 h-3.5" strokeWidth={2} />
                Add Credential
              </button>
            </div>
          </div>
        </div>

        {/* Statistics */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
          {/* Active - Green */}
          <div className="bg-green-500 border border-green-600 rounded-lg shadow-sm shadow-green-500/25 p-3 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-white/90 font-medium uppercase tracking-wide mb-1">Active</p>
                <p className="text-xl font-bold text-white">{stats.active}</p>
              </div>
              <div className="h-8 w-8 rounded-lg bg-white/20 border border-white/30 flex items-center justify-center">
                <FileText className="w-5 h-5 text-white" strokeWidth={2} />
              </div>
            </div>
          </div>
          {/* Expiring Soon - Yellow */}
          <div className="bg-yellow-500 border border-yellow-600 rounded-lg shadow-sm shadow-yellow-500/25 p-3 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-white/90 font-medium uppercase tracking-wide mb-1">Expiring Soon</p>
                <p className="text-xl font-bold text-white">{stats.expiringSoon}</p>
              </div>
              <div className="h-8 w-8 rounded-lg bg-white/20 border border-white/30 flex items-center justify-center">
                <AlertCircle className="w-5 h-5 text-white" strokeWidth={2} />
              </div>
            </div>
          </div>
          {/* Expired - Red */}
          <div className="bg-red-500 border border-red-600 rounded-lg shadow-sm shadow-red-500/25 p-3 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-white/90 font-medium uppercase tracking-wide mb-1">Expired</p>
                <p className="text-xl font-bold text-white">{stats.expired}</p>
              </div>
              <div className="h-8 w-8 rounded-lg bg-white/20 border border-white/30 flex items-center justify-center">
                <AlertCircle className="w-5 h-5 text-white" strokeWidth={2} />
              </div>
            </div>
          </div>
          {/* Total - Blue (Goodwill Primary) */}
          <div className="bg-goodwill-primary border border-goodwill-primary rounded-lg shadow-sm shadow-goodwill-primary/25 p-3 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-white/90 font-medium uppercase tracking-wide mb-1">Total</p>
                <p className="text-xl font-bold text-white">{credentials.length}</p>
              </div>
              <div className="h-8 w-8 rounded-lg bg-white/20 border border-white/30 flex items-center justify-center">
                <Calendar className="w-5 h-5 text-white" strokeWidth={2} />
              </div>
            </div>
          </div>
        </div>

        {/* Credentials List */}
        <div className="bg-white rounded-lg shadow-sm overflow-hidden border border-goodwill-border/50">
          {loading ? (
            <div className="p-8 text-center">
              <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-goodwill-primary border-t-transparent"></div>
              <p className="mt-3 text-xs text-goodwill-dark font-medium">Loading credentials...</p>
            </div>
          ) : error ? (
            <div className="p-8 text-center text-goodwill-secondary font-semibold">{error}</div>
          ) : credentials.length === 0 ? (
            <div className="p-8 text-center">
              <FileText className="w-12 h-12 mx-auto mb-3 text-goodwill-text-muted/50" strokeWidth={1.5} />
              <p className="text-xs font-medium text-goodwill-text-muted mb-4">No credentials found</p>
              <button
                onClick={handleAdd}
                className="px-4 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all duration-300"
              >
                Add Your First Credential
              </button>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full divide-y divide-goodwill-border/30">
                <thead className="bg-goodwill-primary">
                  <tr>
                    <th className="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase">Type</th>
                    <th className="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase hidden md:table-cell">Issue Date</th>
                    <th className="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase">Expiry Date</th>
                    <th className="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase">Status</th>
                    <th className="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase hidden lg:table-cell">Days Until Expiry</th>
                    <th className="px-3 py-2.5 text-right text-xs font-semibold text-white uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-goodwill-border/30">
                  {credentials.map((credential, index) => {
                    const now = new Date();
                    now.setHours(0, 0, 0, 0);
                    const expiryDate = credential.expiry_date ? new Date(credential.expiry_date) : null;
                    const daysUntilExpiry = expiryDate
                      ? Math.ceil((expiryDate.setHours(0, 0, 0, 0) - now) / (1000 * 60 * 60 * 24))
                      : null;

                    return (
                      <tr
                        key={credential.id}
                        className={`hover:bg-goodwill-light/50 transition-all duration-200 ${
                          index % 2 === 0 ? 'bg-white' : 'bg-goodwill-light/30'
                        } animate-fade-in`}
                        style={{ animationDelay: `${index * 0.03}s` }}
                      >
                        <td className="px-3 py-2 text-sm font-medium text-goodwill-dark">{credential.credential_type}</td>
                        <td className="px-3 py-2 text-xs text-goodwill-text-muted hidden md:table-cell">
                          {credential.issue_date ? new Date(credential.issue_date).toLocaleDateString() : '-'}
                        </td>
                        <td className="px-3 py-2 text-xs text-goodwill-text-muted font-medium">
                          {credential.expiry_date ? new Date(credential.expiry_date).toLocaleDateString() : '-'}
                        </td>
                        <td className="px-3 py-2">
                          <StatusTag status={credential.status} />
                        </td>
                        <td className="px-3 py-2 text-xs font-medium hidden lg:table-cell">
                          {daysUntilExpiry !== null ? (
                            <span className={daysUntilExpiry <= 0 ? 'text-red-600' : daysUntilExpiry <= 30 ? 'text-yellow-600' : 'text-green-600'}>
                              {daysUntilExpiry <= 0 ? 'Expired' : `${daysUntilExpiry} days`}
                            </span>
                          ) : (
                            <span className="text-gray-500">-</span>
                          )}
                        </td>
                        <td className="px-3 py-2 text-right">
                          <div className="flex justify-end gap-1.5">
                            <button
                              onClick={() => handleEdit(credential)}
                              className="p-1.5 bg-goodwill-primary text-white rounded hover:bg-goodwill-primary/90 transition-all duration-300 hover:scale-110 active:scale-95"
                              title="Edit"
                            >
                              <Edit className="w-3.5 h-3.5" strokeWidth={2} />
                            </button>
                            <button
                              onClick={() => handleDelete(credential.id)}
                              disabled={deletingId === credential.id}
                              className="p-1.5 bg-goodwill-secondary text-white rounded hover:bg-goodwill-secondary/90 transition-all duration-300 disabled:opacity-50 hover:scale-110 active:scale-95"
                              title="Delete"
                            >
                              {deletingId === credential.id ? (
                                <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                              ) : (
                                <Trash2 className="w-3.5 h-3.5" strokeWidth={2} />
                              )}
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {/* Credential Form Modal */}
      {isFormOpen && (
        <CredentialForm
          isOpen={isFormOpen}
          credential={editingCredential}
          onClose={handleFormClose}
          onSuccess={fetchMyCredentials}
          defaultEmail={user?.email}
          defaultCandidateName={user?.name}
        />
      )}
    </Layout>
  );
};

export default MyCredentials;

