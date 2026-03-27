import { useState } from 'react';
import { HelpCircle, Book, MessageCircle, Mail, FileText, Video, ChevronDown, ChevronRight } from 'lucide-react';
import Layout from '../components/Layout/Layout';

const HelpSupport = () => {
  const [expandedSection, setExpandedSection] = useState(null);

  const faqSections = [
    {
      id: 'getting-started',
      title: 'Getting Started',
      icon: <Book className="w-4 h-4" />,
      items: [
        {
          q: 'How do I create a credential?',
          a: 'Navigate to Dashboard and click "Add Credential" button. Fill in the required information and upload any supporting documents.',
        },
        {
          q: 'How are credential statuses calculated?',
          a: 'Statuses are automatically calculated based on expiry dates: Active (>30 days), Expiring Soon (≤30 days), Expired (past date).',
        },
        {
          q: 'Can I manually override a status?',
          a: 'Yes, you can manually set a status when creating or editing a credential. The manual status takes precedence over the calculated status.',
        },
      ],
    },
    {
      id: 'credentials',
      title: 'Managing Credentials',
      icon: <FileText className="w-4 h-4" />,
      items: [
        {
          q: 'What file types can I upload?',
          a: 'You can upload PDF, DOC, DOCX, and image files (JPG, PNG) up to 10MB in size.',
        },
        {
          q: 'How do I export credentials?',
          a: 'Use the Export CSV button on the Dashboard or go to Import/Export page for more export options.',
        },
        {
          q: 'Can I bulk update credentials?',
          a: 'Yes, use the Bulk Operations page to select multiple credentials and perform bulk actions like status updates or deletion.',
        },
      ],
    },
    {
      id: 'notifications',
      title: 'Email & Notifications',
      icon: <Mail className="w-4 h-4" />,
      items: [
        {
          q: 'When are reminder emails sent?',
          a: 'Automatic reminders are sent at 30, 14, and 7 days before expiry. You can also manually trigger reminders from the Dashboard.',
        },
        {
          q: 'Who receives summary emails?',
          a: 'Daily summary emails are sent to all admin and super admin users with credentials expiring in the next 30 days.',
        },
        {
          q: 'How do I configure email settings?',
          a: 'Go to Email Settings page to configure SMTP settings, email templates, and notification preferences.',
        },
      ],
    },
    {
      id: 'account',
      title: 'Account & Profile',
      icon: <HelpCircle className="w-4 h-4" />,
      items: [
        {
          q: 'How do I change my password?',
          a: 'Go to Profile page and use the "Change Password" section. You\'ll need to enter your current password.',
        },
        {
          q: 'Can I update my profile picture?',
          a: 'Yes, go to Profile page and click on your avatar to upload a new image. Supported formats: JPG, PNG, GIF.',
        },
        {
          q: 'What are the different user roles?',
          a: 'Super Admin: Full system access. Admin: Can manage credentials and users. Recruiter: View-only access. Candidate: Manage own credentials.',
        },
      ],
    },
  ];

  const toggleSection = (id) => {
    setExpandedSection(expandedSection === id ? null : id);
  };

  return (
    <Layout>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
            <HelpCircle className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
            Help & Support
          </h1>
          <p className="text-xs text-goodwill-text-muted mt-1">Documentation, FAQs, and support resources</p>
        </div>

        {/* Quick Links */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50 hover-lift cursor-pointer">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-goodwill-primary/10 rounded-lg">
                <Book className="w-5 h-5 text-goodwill-primary" />
              </div>
              <div>
                <h3 className="text-xs font-semibold text-goodwill-dark">Documentation</h3>
                <p className="text-xs text-goodwill-text-muted">User guides and tutorials</p>
              </div>
            </div>
          </div>
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50 hover-lift cursor-pointer">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-goodwill-primary/10 rounded-lg">
                <Video className="w-5 h-5 text-goodwill-primary" />
              </div>
              <div>
                <h3 className="text-xs font-semibold text-goodwill-dark">Video Tutorials</h3>
                <p className="text-xs text-goodwill-text-muted">Step-by-step video guides</p>
              </div>
            </div>
          </div>
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50 hover-lift cursor-pointer">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-goodwill-primary/10 rounded-lg">
                <MessageCircle className="w-5 h-5 text-goodwill-primary" />
              </div>
              <div>
                <h3 className="text-xs font-semibold text-goodwill-dark">Contact Support</h3>
                <p className="text-xs text-goodwill-text-muted">Get help from our team</p>
              </div>
            </div>
          </div>
        </div>

        {/* FAQ Sections */}
        <div className="bg-white rounded-lg shadow-sm border border-goodwill-border/50 overflow-hidden">
          <div className="p-4 border-b border-goodwill-border/50">
            <h3 className="text-sm font-semibold text-goodwill-dark">Frequently Asked Questions</h3>
          </div>
          <div className="divide-y divide-goodwill-border/30">
            {faqSections.map((section) => (
              <div key={section.id}>
                <button
                  onClick={() => toggleSection(section.id)}
                  className="w-full p-4 flex items-center justify-between hover:bg-goodwill-light/50 transition-colors"
                >
                  <div className="flex items-center gap-3">
                    <div className="text-goodwill-primary">{section.icon}</div>
                    <span className="text-sm font-semibold text-goodwill-dark">{section.title}</span>
                  </div>
                  {expandedSection === section.id ? (
                    <ChevronDown className="w-4 h-4 text-goodwill-text-muted" />
                  ) : (
                    <ChevronRight className="w-4 h-4 text-goodwill-text-muted" />
                  )}
                </button>
                {expandedSection === section.id && (
                  <div className="px-4 pb-4 space-y-3 bg-goodwill-light/30">
                    {section.items.map((item, index) => (
                      <div key={index} className="pt-3 border-t border-goodwill-border/30 first:border-t-0 first:pt-0">
                        <h4 className="text-xs font-semibold text-goodwill-dark mb-1">{item.q}</h4>
                        <p className="text-xs text-goodwill-text-muted">{item.a}</p>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Contact Support */}
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <h3 className="text-sm font-semibold text-goodwill-dark mb-3">Need More Help?</h3>
          <div className="space-y-2">
            <div className="flex items-center gap-2 text-xs text-goodwill-text-muted">
              <Mail className="w-4 h-4" />
              <span>Email: support@goodwillstaffing.ca</span>
            </div>
            <div className="flex items-center gap-2 text-xs text-goodwill-text-muted">
              <MessageCircle className="w-4 h-4" />
              <span>Live Chat: Available 9 AM - 5 PM EST</span>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default HelpSupport;

