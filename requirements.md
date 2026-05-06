## **PROJECT OVERVIEW**
TodaMS is a role-based management system for transportation association operations, including member records, violations, payments, and audits.  
This document defines user responsibilities, dashboard scope, workflows, technical direction, and implementation phases.

---

## **TECH STACK**
**Core Technologies**
*   **Backend:** PHP
*   **Frontend Structure:** HTML
*   **Frontend Interactivity:** JavaScript (JS)
*   **Frontend Styling:** CSS
*   **Database:** MySQL managed through `phpMyAdmin` (XAMPP localhost)
*   **Web Server:** `XAMPP` localhost environment with PHP + Apache

**Suggested PHP Approach**
*   Use a simple MVC-style structure for clean separation of concerns.
*   Keep business logic inside services, not directly in views or route files.
*   Centralize authentication, authorization, and validation.

---

## **RECOMMENDED PROJECT STRUCTURE**
```text
TodaMS/
├── app/
│   ├── config/
│   │   ├── app.php
│   │   ├── database.php
│   │   └── roles.php
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── MemberController.php
│   │   ├── ViolationController.php
│   │   ├── PaymentController.php
│   │   ├── ReportController.php
│   │   └── AuditController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Member.php
│   │   ├── Violation.php
│   │   ├── Payment.php
│   │   ├── Notification.php
│   │   └── AuditLog.php
│   ├── services/
│   │   ├── AuthService.php
│   │   ├── PaymentService.php
│   │   ├── ViolationService.php
│   │   ├── NotificationService.php
│   │   └── ReportService.php
│   ├── middleware/
│   │   ├── AuthMiddleware.php
│   │   └── RoleMiddleware.php
│   ├── repositories/
│   │   ├── MemberRepository.php
│   │   ├── PaymentRepository.php
│   │   └── ViolationRepository.php
│   └── helpers/
│       ├── Validator.php
│       ├── Response.php
│       └── Utils.php
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   │   ├── base.css
│   │   │   ├── components.css
│   │   │   └── dashboard.css
│   │   ├── js/
│   │   │   ├── app.js
│   │   │   ├── dashboard.js
│   │   │   ├── payments.js
│   │   │   └── violations.js
│   │   └── images/
│   └── uploads/
│       ├── member-docs/
│       ├── payment-proofs/
│       └── violation-evidence/
├── routes/
│   ├── web.php
│   └── api.php
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   ├── auth/
│   │   ├── dashboards/
│   │   ├── members/
│   │   ├── violations/
│   │   ├── payments/
│   │   └── reports/
│   └── templates/
├── storage/
│   ├── logs/
│   └── cache/
├── tests/
│   ├── unit/
│   └── integration/
├── database/
│   ├── migrations/
│   └── seeders/
├── .env.example
├── README.md
└── requirements.md
```

**Organization Rules**
*   Keep each module (Members, Violations, Payments, Reports) isolated by controller, service, and view.
*   Use consistent naming (`EntityController`, `EntityService`, `EntityRepository`).
*   Store uploads in dedicated folders by type for easier audit and maintenance.
*   Keep role checks in middleware, not in view templates.

---

## **SUPER ADMIN (FULL CONTROL)**
**CORE RESPONSIBILITY**
*   Overall control of the entire system
*   System-wide monitoring of all modules
*   Security and data integrity management

**FUNCTIONS**
1.  **System Management**
    *   Manage users (Vice President, Treasurer, Secretary, Compliance Officer, Drivers)
    *   Role and permission control
    *   System configuration settings
2.  **System Monitoring (GLOBAL ONLY)**
    *   Monitor overall system activity (excluding detailed financial data)
    *   Monitor user actions across all modules
    *   Track system performance
3.  **Reports**
    *   Generate system-wide reports (users, violations, payment summaries)
4.  **Security**
    *   Manage access control
    *   Maintain audit integrity
    *   Ensure system logs are active and protected

---

## **VICE PRESIDENT (DECISION & APPROVAL)**
**CORE RESPONSIBILITY**
*   Final decision-maker for violations and member approval

**FUNCTIONS**
1.  Approve/Reject violation reports (includes evidence review)
2.  Approve/Reject new members
3.  Resolve disputes between members and reports

---

## **COMPLIANCE OFFICER (DATA VALIDATION & AUDIT ONLY)**
**CORE RESPONSIBILITY**
*   Ensure accuracy and validity of system data

**FUNCTIONS**
1.  Validate violation reports (check for completeness and evidence)
2.  Audit system records (members, violations, payments)
3.  Detect suspicious or inconsistent data
4.  Generate audit reports

**LIMITATIONS**
1.  Cannot approve violations
2.  Cannot approve payments
3.  Cannot modify official records

---

## **SECRETARY (DATA ENCODING)**
**CORE RESPONSIBILITY**
*   Encoding and documentation of records

**FUNCTIONS**
1.  Add and update member records
2.  Encode violation reports with evidence
3.  Upload documents and files
4.  Maintain system records

---

## **TREASURER (FINANCIAL MANAGEMENT)**
**CORE RESPONSIBILITY**
*   Handle all financial records and transactions

**FUNCTIONS**
1.  Encode and manage all payments
2.  Track income (daily/monthly)
3.  Generate financial reports
4.  Monitor unpaid accounts

**LIMITATIONS**
1.  Cannot approve violations
2.  Cannot modify payment history after confirmation

---

## **DRIVER (END USER)**
**CORE RESPONSIBILITY**
*   End user of the system

**FUNCTIONS**
1.  View profile information
2.  View violations
3.  View payment records
4.  Receive notifications

---

## **DASHBOARD OVERVIEWS**

### **Super Admin Dashboard**
*   **User Management:** Manage Users (Add/Edit/Deactivate), Role & Permissions Control
*   **System Monitoring:** System Overview, All Drivers List, All Violations, All Payments (high-level)
*   **Reports:** Generate System Reports, Analytics Dashboard
*   **Security:** Audit Logs, Activity Monitoring, Backup/Restore System

### **Vice President Dashboard**
*   **Violation Management:** Pending Violations, View Evidence, Approve/Reject Violations
*   **Member Approval:** Approve/Reject Members
*   **Summary:** Approved Cases, Rejected Cases

### **Compliance Officer Dashboard**
*   **Data Review:** View Violations, Payments, and Members (Read-only)
*   **Audit Tools:** Flag Suspicious Records, Check Duplicates, Audit Reports
*   **System Logs:** Activity Logs (Read-only)

### **Secretary Dashboard**
*   **Member Management:** Add/Edit/View Members
*   **Violation Encoding:** Encode Violation Report, Upload Evidence, Submit Report
*   **Documents:** Upload Files, Records Management

### **Treasurer Dashboard**
*   **Payment Management:** Record Payment, Verify Payment Proof, Payment List
*   **Financial Reports:** Income Summary, Unpaid Accounts, Monthly Reports
*   **Collections:** Activity Fees, Penalties, Member Dues

### **Driver Dashboard**
*   **Profile:** View Profile, Update Info (limited)
*   **Violations:** View My Violations, Violation Status
*   **Payments:** View Payment Records, Outstanding Balance, Payment History
*   **Notifications:** Alerts, Announcements, Payment Due, Violation Notices

---

## **PAYMENT WORKFLOW**

1.  **Payment Creation (System)**
    *   **Triggers:** Approved Violations/Penalties, Monthly Dues/Membership Fees, Activity Fees.
    *   **Result:** System creates a billing record for the driver.
2.  **Notification (Automatic)**
    *   **Action:** System sends notification to the driver.
    *   **Content:** Amount due, reason, due date, and reference number.
3.  **Payment Submission (Driver)**
    *   **Action:** Driver pays dues.
    *   **Methods:** Cash (recorded by Treasurer) or Upload Proof (GCash/Maya/Bank screenshot).
    *   **Result:** Status set to **PENDING VERIFICATION**.
4.  **Payment Verification (Treasurer Only)**
    *   **Action:** Check proof, match with billing record, confirm amount.
    *   **Result:** Mark as **PAID** or **REJECTED (INVALID PAYMENT)**.
5.  **Audit (Compliance Officer)**
    *   **Action:** View payments, check for irregularities, flag suspicious transactions.
    *   **Limitation:** Read-only; cannot approve or edit.
6.  **System Update (Automatic)**
    *   **Upon Confirmation:** Status becomes **PAID**, receipt/reference generated, record saved, and audit log created.

---

## **REPORT WORKFLOW (Violations)**

1.  **Report Submission (Driver)**
    *   Driver reports a fellow driver with details (Name/Plate No, Violation Type, Description, Date/Time, Location, Evidence).
    *   **Status:** **SUBMITTED**.
2.  **Encoding & Completeness Check (Secretary)**
    *   Encodes report; checks for evidence and valid information format.
    *   **Result:** If complete, status becomes **PENDING VALIDATION**. If incomplete, returned to the driver.
3.  **Validation (Compliance Officer)**
    *   Verifies if the report is legitimate (Valid violation? Matching evidence? No duplicates or false accusations?).
    *   **Result:** If valid, forward to VP. If invalid, reject report.
4.  **Final Decision (Vice President)**
    *   Approve or Reject the report.
    *   **Result:** If **APPROVED**, it becomes an official violation. If **REJECTED**, it is closed.
5.  **System Action (Automatic)**
    *   If **APPROVED**: Violation recorded, penalty generated, payment created, driver notified.
6.  **Notifications**
    *   **Reporter:** Notified of submission and final decision.
    *   **Reported Driver:** Notified of violation and penalty details.

---

## **NEW MEMBER ONBOARDING (Secretary Only)**

**Sample Flow**
**Phase 1: Encoding (Secretary)**
1.  Login and navigate to Member Management.
2.  Input details: Name, Address, Contact Number, License Number, Plate Number.
3.  Upload Documents: ID, License, OR/CR.
4.  **System Validation:** Checks for required fields and format.
5.  **Submit:** Status becomes **PENDING APPROVAL**.

**Phase 2: System Action**
1.  Record saved to database; Audit log created; Vice President notified.

**Phase 3: Approval (Vice President)**
1.  Review pending member details and documents.
2.  **Decision:**
    *   **APPROVE:** Member becomes **ACTIVE** and can log in.
    *   **REJECT:** Member is **DECLINED** and not activated.

**Phase 4: Final Action**
1.  System updates status and sends notifications to the member and the Secretary.

---

## **DEVELOPMENT PHASES**
### **Phase 1: Project Setup & Foundation**
*   Initialize PHP project structure and routing.
*   Configure database connection and environment variables.
*   Build base layout templates (HTML/CSS/JS) and authentication pages.
*   Implement role and permission matrix.

### **Phase 2: Core Modules (CRUD + Workflows)**
*   Build Member Management (Secretary + VP approval).
*   Build Violation Management (submission, encoding, validation, decision).
*   Build Payment Management (billing, submission, verification, status updates).
*   Build Notification system for key workflow events.

### **Phase 3: Dashboard & Reporting**
*   Create role-specific dashboards.
*   Implement filters/search for records and transactions.
*   Build reports: system reports, audit reports, and financial summaries.

### **Phase 4: Security, Audit, and Quality**
*   Add middleware protections and strict role-based access controls.
*   Ensure all key actions write to audit logs.
*   Add input validation and basic error handling across modules.
*   Conduct unit/integration testing for critical flows.

### **Phase 5: UAT, Deployment, and Handover**
*   Perform user acceptance testing with each role.
*   Fix defects and optimize performance for production.
*   Prepare deployment checklist and backup/restore process.
*   Finalize documentation and end-user training materials.

---

## **IMPLEMENTATION NOTES (FINAL TOUCH)**
*   Keep all statuses standardized across modules: `SUBMITTED`, `PENDING VALIDATION`, `PENDING APPROVAL`, `PENDING VERIFICATION`, `APPROVED`, `REJECTED`, `PAID`, `DECLINED`, `ACTIVE`.
*   Require audit trail entries for create, update, approve, reject, and payment verification actions.
*   Maintain read-only enforcement for Compliance Officer in operational modules.
*   Prioritize clear UI labels and role-based menu visibility to reduce user error.
*   Apply consistent validation messages so users understand required actions immediately.

