export const ROLE_PLATFORM_ADMIN = 'platform_admin';
export const ROLE_ORG_SUPER_ADMIN = 'org_super_admin';
export const ROLE_ADMIN = 'admin'; // Legacy mapped to Recruiter
export const ROLE_RECRUITER = 'recruiter';
export const ROLE_SCHEDULER = 'scheduler';
export const ROLE_COMPLIANCE = 'compliance';
export const ROLE_FINANCE = 'finance';
export const ROLE_LOGISTICS = 'logistics';
export const ROLE_CANDIDATE = 'candidate';
export const ROLE_FACILITY = 'facility';

export const STAFF_ROLES = [
    ROLE_ORG_SUPER_ADMIN,
    ROLE_ADMIN,
    ROLE_RECRUITER,
    ROLE_SCHEDULER,
    ROLE_COMPLIANCE,
    ROLE_FINANCE,
    ROLE_LOGISTICS,
];

export const ALL_ROLES = [
    ...STAFF_ROLES,
    ROLE_PLATFORM_ADMIN,
    ROLE_CANDIDATE,
    ROLE_FACILITY,
];
