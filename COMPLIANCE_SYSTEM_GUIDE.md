# Compliance System Guide

## How Compliance is Measured and Detected

The Goodwill Staffing Compliance Tracker automatically measures and detects compliance status for all candidates. Here's how it works:

---

## 📊 Compliance Calculation

### Formula
```
Compliance Score = (Valid Documents ÷ Total Documents) × 100
```

### What Counts as "Valid"?
A document is considered **valid** if:
1. **Has an expiry date**: The expiry date is in the future (not expired)
2. **Has a valid status**: Status is one of:
   - `active` (for credentials)
   - `verified` (for background checks)
   - `valid` (for work authorizations)
   - `up_to_date` (for health records)

### What Counts as "Invalid"?
A document is considered **invalid** if:
1. **Expired**: Expiry date is in the past
2. **Failed status**: Status is `failed`, `revoked`, or `expired`
3. **Pending verification**: Status is `pending` and not yet verified

---

## 🎯 Compliance Levels

### 1. **Compliant** (≥90% Score)
- **Status**: ✅ Compliant
- **Meaning**: All or nearly all documents are valid
- **Action**: Candidate is ready for assignment
- **Color**: Green

### 2. **At Risk** (70-89% Score)
- **Status**: ⚠️ At Risk
- **Meaning**: Some documents are expiring soon or need attention
- **Action**: Monitor closely, renew expiring documents
- **Color**: Yellow

### 3. **Non-Compliant** (<70% Score)
- **Status**: ❌ Non-Compliant
- **Meaning**: Multiple documents are expired or missing
- **Action**: Immediate action required, do not assign until compliant
- **Color**: Red

---

## 📋 Documents Tracked

The system tracks compliance across **4 main categories**:

### 1. Professional Licenses & Credentials
- Nursing licenses (RN, LPN, RPN, NP)
- Professional certifications
- Specialty credentials
- **Status**: Active, Expiring Soon, Expired, Pending

### 2. Security Clearances & Background Checks
- Criminal Record Checks (CRC)
- Vulnerable Sector Checks (VSC)
- Security Clearances
- **Status**: Verified, Pending, Failed, Expired

### 3. Health Compliance Records
- Immunizations (COVID-19, Flu, Hepatitis B, etc.)
- TB Tests
- Health Screenings
- Medical Clearances
- **Status**: Up to Date, Expired, Due, Pending

### 4. Legal Work Status
- Work Permits
- Visas
- SIN Verification
- Professional Liability Insurance
- **Status**: Valid, Expired, Pending Renewal, Revoked

---

## 🔍 Where to Check Compliance

### 1. **Compliance Dashboard** (`/compliance`)
- View all staff compliance at a glance
- See overall statistics
- Filter by compliance level
- Click "View Profile" for details

### 2. **Staff Profile Page** (`/staff/:candidateName`)
- **Compliance Details Tab**: Detailed breakdown
  - Shows exact compliance score
  - Lists all documents with status
  - Explains how score is calculated
  - Shows documents by category
- **Overview Tab**: Quick summary
- **Individual Tabs**: View documents by type

### 3. **Dashboard**
- Compliance overview section
- Quick access to Compliance Dashboard
- Real-time status indicators

---

## 📈 Understanding the Compliance Score

### Example Calculation

**Candidate: John Doe**

**Documents:**
- RN License: Valid (expires in 6 months) ✅
- Criminal Record Check: Verified ✅
- COVID-19 Vaccination: Up to Date ✅
- Work Permit: Expired ❌
- TB Test: Expiring in 15 days ⚠️

**Calculation:**
- Total Documents: 5
- Valid Documents: 3 (RN License, CRC, COVID-19)
- Expired: 1 (Work Permit)
- Expiring Soon: 1 (TB Test)
- **Score: (3/5) × 100 = 60%**
- **Status: Non-Compliant** ❌

**Action Required:**
1. Renew Work Permit (critical)
2. Schedule TB Test renewal (expiring soon)

---

## 🚨 Automatic Detection Features

### 1. **Expiry Detection**
- System automatically checks expiry dates daily
- Documents expiring within 30 days are flagged
- Expired documents are immediately marked invalid

### 2. **Status Monitoring**
- Real-time status updates
- Automatic recalculation when documents are added/updated
- Instant compliance score updates

### 3. **Alerts & Notifications**
- Email reminders for expiring documents
- Dashboard notifications for compliance issues
- Visual indicators (red/yellow/green badges)

---

## ✅ How to Know if a Candidate is Compliant

### Quick Check:
1. Go to **Compliance Dashboard** (`/compliance`)
2. Look for the candidate's name
3. Check the **Compliance Score** column:
   - **Green (≥90%)**: ✅ Compliant - Ready for assignment
   - **Yellow (70-89%)**: ⚠️ At Risk - Monitor closely
   - **Red (<70%)**: ❌ Non-Compliant - Action required

### Detailed Check:
1. Click **"View Profile"** on the candidate
2. Go to **"Compliance Details"** tab
3. Review:
   - Overall compliance status
   - Document breakdown by category
   - List of expired/expiring documents
   - Specific issues that need attention

### Visual Indicators:
- **Green Badge**: Compliant
- **Yellow Badge**: At Risk
- **Red Badge**: Non-Compliant
- **Progress Bar**: Visual representation of score
- **Document Status Icons**: ✅ Valid, ❌ Expired, ⚠️ Expiring, ⏳ Pending

---

## 🔧 Maintaining Compliance

### For Administrators:
1. Regularly check Compliance Dashboard
2. Review "At Risk" candidates weekly
3. Address "Non-Compliant" candidates immediately
4. Set up email reminders for expiring documents
5. Verify new documents promptly

### For Candidates:
1. Upload documents before they expire
2. Keep all credentials current
3. Respond to renewal reminders
4. Check "My Credentials" page regularly

---

## 📊 Compliance Reports

The system provides:
- Overall compliance statistics
- Compliance by category
- Expiring documents list
- Non-compliant candidates report
- Compliance trends over time

---

## 💡 Best Practices

1. **Set Reminders**: Use the email reminder system for 30, 14, and 7 days before expiry
2. **Regular Audits**: Review Compliance Dashboard weekly
3. **Proactive Management**: Renew documents before they expire
4. **Document Verification**: Verify all uploaded documents promptly
5. **Clear Communication**: Notify candidates of compliance issues immediately

---

## 🎯 Compliance Thresholds

- **Minimum for Assignment**: 90% (Compliant)
- **Warning Level**: 70-89% (At Risk)
- **Blocking Level**: <70% (Non-Compliant)

These thresholds ensure:
- Only fully compliant staff are assigned
- Early warning for potential issues
- Clear action items for HR team

