import { useState, useEffect } from 'react';
import { FileCheck, Plus, Edit, Trash2, Copy, Loader2 } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import { useNavigate } from 'react-router-dom';
import api from '../config/api';

const Templates = () => {
  const navigate = useNavigate();
  const [templates, setTemplates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showAddModal, setShowAddModal] = useState(false);
  const [editingTemplate, setEditingTemplate] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    credential_type: '',
    position: '',
    default_days: 365,
    description: '',
  });

  useEffect(() => {
    fetchTemplates();
  }, []);

  const fetchTemplates = async () => {
    setLoading(true);
    try {
      const response = await api.get('/templates');
      setTemplates(response.data);
    } catch (err) {
      console.error('Error fetching templates:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async () => {
    try {
      if (editingTemplate) {
        await api.put(`/templates/${editingTemplate.id}`, formData);
      } else {
        await api.post('/templates', formData);
      }
      fetchTemplates();
      setShowAddModal(false);
      setEditingTemplate(null);
      setFormData({ name: '', credential_type: '', position: '', default_days: 365, description: '' });
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to save template');
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure you want to delete this template?')) return;
    try {
      await api.delete(`/templates/${id}`);
      fetchTemplates();
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to delete template');
    }
  };

  const useTemplate = (template) => {
    // Navigate to dashboard with template data pre-filled
    navigate('/dashboard', { state: { template } });
  };

  if (loading) {
    return (
      <Layout>
        <div className="p-8 text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-goodwill-primary"></div>
          <p className="mt-3 text-xs text-goodwill-dark font-medium">Loading templates...</p>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
                <FileCheck className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
                Credential Templates
              </h1>
              <p className="text-xs text-goodwill-text-muted mt-1">Quick credential creation from templates</p>
            </div>
            <button
              onClick={() => {
                setEditingTemplate(null);
                setFormData({ name: '', credential_type: '', position: '', default_days: 365, description: '' });
                setShowAddModal(true);
              }}
              className="px-3 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all flex items-center gap-1.5"
            >
              <Plus className="w-3.5 h-3.5" />
              New Template
            </button>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          {templates.map((template) => (
            <div
              key={template.id}
              className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50 hover-lift"
            >
              <div className="flex items-start justify-between mb-3">
                <div className="flex-1">
                  <h3 className="text-sm font-semibold text-goodwill-dark mb-1">{template.name}</h3>
                  <p className="text-xs text-goodwill-text-muted">{template.description}</p>
                </div>
                <div className="flex items-center gap-1">
                  <button
                    onClick={() => {
                      setEditingTemplate(template);
                      setFormData({
                        name: template.name,
                        credential_type: template.credential_type,
                        position: template.position || '',
                        default_days: template.default_days,
                        description: template.description || '',
                      });
                      setShowAddModal(true);
                    }}
                    className="p-1 hover:bg-goodwill-light rounded transition-colors" title="Edit"
                  >
                    <Edit className="w-4 h-4 text-goodwill-text-muted" />
                  </button>
                  <button
                    onClick={() => handleDelete(template.id)}
                    className="p-1 hover:bg-goodwill-light rounded transition-colors" title="Delete"
                  >
                    <Trash2 className="w-4 h-4 text-goodwill-text-muted" />
                  </button>
                </div>
              </div>
              <div className="space-y-2 mb-4">
                <div className="flex items-center justify-between text-xs">
                  <span className="text-goodwill-text-muted">Type:</span>
                  <span className="text-goodwill-dark font-medium">{template.credential_type}</span>
                </div>
                <div className="flex items-center justify-between text-xs">
                  <span className="text-goodwill-text-muted">Position:</span>
                  <span className="text-goodwill-dark font-medium">{template.position || 'N/A'}</span>
                </div>
                <div className="flex items-center justify-between text-xs">
                  <span className="text-goodwill-text-muted">Default Validity:</span>
                  <span className="text-goodwill-dark font-medium">{template.default_days} days</span>
                </div>
              </div>
              <button
                onClick={() => useTemplate(template)}
                className="w-full px-3 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all flex items-center justify-center gap-1.5"
              >
                <Copy className="w-3.5 h-3.5" />
                Use Template
              </button>
            </div>
          ))}
        </div>

        {/* Add/Edit Modal */}
        {showAddModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div className="bg-white rounded-lg shadow-lg p-6 max-w-md w-full mx-4 border border-goodwill-border/50">
              <h3 className="text-base font-bold text-goodwill-dark mb-4">
                {editingTemplate ? 'Edit Template' : 'New Template'}
              </h3>
              <div className="space-y-3">
                <div>
                  <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">Name</label>
                  <input
                    type="text"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">Credential Type</label>
                  <input
                    type="text"
                    value={formData.credential_type}
                    onChange={(e) => setFormData({ ...formData, credential_type: e.target.value })}
                    className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">Position</label>
                  <input
                    type="text"
                    value={formData.position}
                    onChange={(e) => setFormData({ ...formData, position: e.target.value })}
                    className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">Default Days</label>
                  <input
                    type="number"
                    value={formData.default_days}
                    onChange={(e) => setFormData({ ...formData, default_days: parseInt(e.target.value) || 365 })}
                    className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">Description</label>
                  <textarea
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                    rows="3"
                  />
                </div>
              </div>
              <div className="flex items-center gap-2 mt-6">
                <button
                  onClick={handleSave}
                  className="flex-1 px-4 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all"
                >
                  Save
                </button>
                <button
                  onClick={() => {
                    setShowAddModal(false);
                    setEditingTemplate(null);
                  }}
                  className="px-4 py-2 bg-goodwill-light border border-goodwill-border text-goodwill-dark rounded-lg text-xs font-medium hover:bg-goodwill-primary hover:text-white transition-all"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </Layout>
  );
};

export default Templates;

