
export type PageId =
  | 'compliance'
  | 'personnel'
  | 'pipeline'
  | 'analytics'
  | 'platform_organizations'
  | 'org_users'
  | 'config'
  | 'access'
  | 'change_password'
  | 'credentials'
  | 'background_checks'
  | 'health_records'
  | 'work_authorizations'
  | 'activity_logs'
  | 'email_settings'
  | 'admin_users'
  | 'profile'
  | 'templates'
  | 'filters';

export interface ActionItem {
  id: string;
  title: string;
  deadline: string;
  assignee: string;
  facility: string;
  urgent?: boolean;
}

export interface OnboardingStep {
  label: string;
  active: boolean;
}
