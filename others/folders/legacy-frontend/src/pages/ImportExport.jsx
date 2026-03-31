import { useState, useEffect } from 'react';
import { Upload, Download, FileText, CheckCircle, AlertCircle } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import api from '../config/api';
import { CSVLink } from 'react-csv';

const ImportExport = () => {
  const [importFile, setImportFile] = useState(null);
  const [importing, setImporting] = useState(false);
  const [importResult, setImportResult] = useState(null);
  const [credentials, setCredentials] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchCredentials();
  }, []);

  const fetchCredentials = async () => {
    setLoading(true);
    try {
      const response = await api.get('/credentials');
      setCredentials(response.data.data || response.data || []);
    } catch (err) {
      console.error('Error fetching credentials:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
      if (file.type === 'text/csv' || file.name.endsWith('.csv')) {
        setImportFile(file);
        setImportResult(null);
      } else {
        alert('Please select a CSV file');
      }
    }
  };

  const handleImport = async () => {
    if (!importFile) {
      alert('Please select a file to import');
      return;
    }

    setImporting(true);
    try {
      const formData = new FormData();
      formData.append('file', importFile);

      const response = await api.post('/import', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      setImportResult({
        success: true,
        imported: response.data.imported,
        errors: response.data.errors?.length || 0,
        message: response.data.message || `Successfully imported ${response.data.imported} credentials`,
        errorList: response.data.errors || [],
      });
      setImportFile(null);
      fetchCredentials();
    } catch (err) {
      setImportResult({
        success: false,
        imported: 0,
        errors: 1,
        message: err.response?.data?.message || 'Import failed',
        errorList: err.response?.data?.errors || [],
      });
    } finally {
      setImporting(false);
    }
  };

  const csvData = credentials.map(cred => ({
    'Candidate Name': cred.candidate_name,
    'Position': cred.position,
    'Credential Type': cred.credential_type,
    'Issue Date': cred.issue_date || '',
    'Expiry Date': cred.expiry_date || '',
    'Email': cred.email || '',
    'Status': cred.status || '',
  }));

  return (
    <Layout>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
            <Upload className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
            Import / Export
          </h1>
          <p className="text-xs text-goodwill-text-muted mt-1">Import credentials from CSV or export to various formats</p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
          {/* Import Section */}
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
            <h3 className="text-sm font-semibold text-goodwill-dark mb-4 flex items-center gap-2">
              <Upload className="w-4 h-4 text-goodwill-primary" />
              Import Credentials
            </h3>
            <div className="space-y-3">
              <div>
                <label className="block text-xs font-semibold text-goodwill-dark mb-2">Select CSV File</label>
                <div className="border-2 border-dashed border-goodwill-border/50 rounded-lg p-6 text-center hover:border-goodwill-primary/50 transition-colors">
                  <input
                    type="file"
                    accept=".csv"
                    onChange={handleFileSelect}
                    className="hidden"
                    id="file-upload"
                  />
                  <label
                    htmlFor="file-upload"
                    className="cursor-pointer flex flex-col items-center gap-2"
                  >
                    <Upload className="w-8 h-8 text-goodwill-primary/50" />
                    <span className="text-xs text-goodwill-text-muted">
                      {importFile ? importFile.name : 'Click to select CSV file'}
                    </span>
                  </label>
                </div>
              </div>
              {importFile && (
                <button
                  onClick={handleImport}
                  disabled={importing}
                  className="w-full px-4 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  {importing ? (
                    <>
                      <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                      Importing...
                    </>
                  ) : (
                    <>
                      <Upload className="w-4 h-4" />
                      Import Credentials
                    </>
                  )}
                </button>
              )}
              {importResult && (
                <div className={`p-3 rounded-lg ${
                  importResult.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
                }`}>
                  <div className="flex items-start gap-2">
                    {importResult.success ? (
                      <CheckCircle className="w-4 h-4 text-green-600 mt-0.5" />
                    ) : (
                      <AlertCircle className="w-4 h-4 text-red-600 mt-0.5" />
                    )}
                    <div className="flex-1">
                      <p className={`text-xs font-semibold ${
                        importResult.success ? 'text-green-800' : 'text-red-800'
                      }`}>
                        {importResult.message}
                      </p>
                      {importResult.success && (
                        <p className="text-xs text-green-700 mt-1">
                          Imported: {importResult.imported} | Errors: {importResult.errors}
                        </p>
                      )}
                      {importResult.errorList && importResult.errorList.length > 0 && (
                        <div className="mt-2 text-xs text-red-700">
                          <p className="font-semibold">Errors:</p>
                          <ul className="list-disc list-inside mt-1">
                            {importResult.errorList.slice(0, 5).map((error, idx) => (
                              <li key={idx}>{error}</li>
                            ))}
                          </ul>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              )}
              <div className="p-3 bg-goodwill-light/50 rounded-lg">
                <p className="text-xs font-semibold text-goodwill-dark mb-1">CSV Format:</p>
                <p className="text-xs text-goodwill-text-muted">
                  Candidate Name, Position, Credential Type, Issue Date, Expiry Date, Email, Status
                </p>
              </div>
            </div>
          </div>

          {/* Export Section */}
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
            <h3 className="text-sm font-semibold text-goodwill-dark mb-4 flex items-center gap-2">
              <Download className="w-4 h-4 text-goodwill-primary" />
              Export Credentials
            </h3>
            <div className="space-y-3">
              <div className="p-3 bg-goodwill-light/50 rounded-lg">
                <p className="text-xs text-goodwill-text-muted mb-3">
                  Export {credentials.length} credential{credentials.length !== 1 ? 's' : ''} to CSV format
                </p>
                <CSVLink
                  data={csvData}
                  filename={`credentials-export-${new Date().toISOString().split('T')[0]}.csv`}
                  className="w-full px-4 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all flex items-center justify-center gap-2"
                >
                  <Download className="w-4 h-4" />
                  Export to CSV
                </CSVLink>
              </div>
              <div className="p-3 bg-goodwill-light/50 rounded-lg">
                <p className="text-xs font-semibold text-goodwill-dark mb-2">Export Options:</p>
                <div className="space-y-2">
                  <button className="w-full px-3 py-2 bg-white border border-goodwill-border rounded-lg text-xs font-medium hover:bg-goodwill-primary hover:text-white transition-all text-left">
                    <FileText className="w-3.5 h-3.5 inline mr-2" />
                    Export as Excel (.xlsx)
                  </button>
                  <button className="w-full px-3 py-2 bg-white border border-goodwill-border rounded-lg text-xs font-medium hover:bg-goodwill-primary hover:text-white transition-all text-left">
                    <FileText className="w-3.5 h-3.5 inline mr-2" />
                    Export as PDF
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default ImportExport;

