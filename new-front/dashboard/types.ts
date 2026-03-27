
export type PageId = 'compliance' | 'personnel' | 'pipeline' | 'analytics' | 'config' | 'access';

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
