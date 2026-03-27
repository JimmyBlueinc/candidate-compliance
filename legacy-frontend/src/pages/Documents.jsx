import { useState, useEffect } from 'react';
import { FolderOpen, Download, Eye, Trash2, FileText, Filter, Search } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import CredentialForm from '../components/CredentialForm';
import api from '../config/api';
import { useAuth } from '../contexts/AuthContext';

const Documents = () => {
  const { isAdmin } = useAuth();
  const [credentials, setCredentials] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
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

  const documents = credentials
    .filter(c => c.document_url)
    .map(c => ({
      id: c.id,
      name: `${c.candidate_name} - ${c.credential_type}`,
      type: c.credential_type,
      candidate: c.candidate_name,
      url: c.document_url,
      expiryDate: c.expiry_date,
      status: c.status,
    }));

  const filteredDocuments = documents.filter(doc => {
    if (filter !== 'all' && doc.status !== filter) return false;
    if (searchQuery && !doc.name.toLowerCase().includes(searchQuery.toLowerCase())) return false;
    return true;
  });

  if (loading) {
    return (
      <Layout>
        <div className="p-8 text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-goodwill-primary"></div>
          <p className="mt-3 text-xs text-goodwill-dark font-medium">Loading documents...</p>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
                <FolderOpen className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
                Documents Library
              </h1>
              <p className="text-xs text-goodwill-text-muted mt-1">
                {documents.length} document{documents.length !== 1 ? 's' : ''} available
              </p>
            </div>
            <div className="flex items-center gap-2">
              <div className="relative">
                <Search className="w-4 h-4 absolute left-2 top-1/2 transform -translate-y-1/2 text-goodwill-text-muted" />
                <input
                  type="text"
                  placeholder="Search documents..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-8 pr-3 py-2 border border-goodwill-border/50 rounded-lg text-xs w-48"
                />
              </div>
              <select
                value={filter}
                onChange={(e) => setFilter(e.target.value)}
                className="px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
              >
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="expiring_soon">Expiring Soon</option>
                <option value="expired">Expired</option>
              </select>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          {filteredDocuments.length === 0 ? (
            <div className="col-span-full bg-white rounded-lg shadow-sm p-8 text-center border border-goodwill-border/50">
              <FolderOpen className="w-12 h-12 mx-auto mb-3 text-goodwill-text-muted/50" />
              <p className="text-xs font-medium text-goodwill-text-muted">No documents found</p>
            </div>
          ) : (
            filteredDocuments.map((doc) => (
              <div
                key={doc.id}
                className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50 hover-lift group"
              >
                <div className="flex items-start justify-between mb-3">
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                      <FileText className="w-4 h-4 text-goodwill-primary flex-shrink-0" />
                      <h3 className="text-xs font-semibold text-goodwill-dark truncate">{doc.type}</h3>
                    </div>
                    <p className="text-xs text-goodwill-text-muted truncate">{doc.candidate}</p>
                    {doc.expiryDate && (
                      <p className="text-xs text-goodwill-text-muted mt-1">
                        Expires: {new Date(doc.expiryDate).toLocaleDateString()}
                      </p>
                    )}
                  </div>
                </div>
                <div className="flex items-center gap-2 pt-3 border-t border-goodwill-border/30">
                  <a
                    href={doc.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex-1 px-2 py-1.5 bg-goodwill-primary text-white rounded text-xs font-medium hover:bg-goodwill-primary/90 transition-all flex items-center justify-center gap-1"
                  >
                    <Eye className="w-3.5 h-3.5" />
                    View
                  </a>
                  <a
                    href={doc.url}
                    download
                    className="px-2 py-1.5 bg-goodwill-light border border-goodwill-border rounded text-xs font-medium hover:bg-goodwill-primary hover:text-white transition-all flex items-center justify-center gap-1"
                  >
                    <Download className="w-3.5 h-3.5" />
                  </a>
                </div>
              </div>
            ))
          )}
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

export default Documents;

