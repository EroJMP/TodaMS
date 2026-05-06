### HOW THE PAYMENT WILL WORK

#### 1. PAYMENT CREATION (SYSTEM)
**WHAT GENERATES THE FEE?**
* Approved Violations and Penalty Fee
* Monthly Dues and Membership Fee
* Activities and Activity Fee (meetings, seminars, etc.)

**RESULT:**
* System creates billing record per driver

---

#### 2. NOTIFICATION (SYSTEM AUTOMATIC)
**SYSTEM ACTION:**
* Sends notification to driver

**CONTENT:**
* Amount due
* Reason (violation / dues / activity)
* Due date
* Reference number

---

#### 3. PAYMENT SUBMISSION (DRIVER)
**DRIVER ACTION:**
* Pay the dues

**METHODS (SIMULATION ONLY):**
* Cash (recorded by Treasurer)
* Upload proof image(GCash/Maya/bank screenshot simulation)

**RESULT:**
* Status = PENDING VERIFICATION

---

#### 4. PAYMENT VERIFICATION (TREASURER ONLY)
**TREASURER ACTION:**
* Check payment proof
* Match with the billing record
* Confirm amount

**RESULT:**
* Mark as PAID
* Reject INVALID PAYMENT

---

#### 5. AUDIT (COMPLIANCE OFFICER)
**COMPLIANCE ACTION:**
* View all payments
* Check irregularities
* Flag suspicious transactions

**LIMITATION:**
* Cannot approve payments
* Cannot edit records
* Role = AUDITOR ONLY

---

#### 6. SYSTEM UPDATE (AUTOMATIC)
**When confirmed:**
* Status = PAID
* Receipt/reference generated
* Record saved in database
* Audit log created

**PAYMENT STATUS:**
* Pending
* Paid
* Rejected

---

#### FULL PAYMENT FLOW
* System generates fee (violation / dues / activity)
* Driver receives notification
* Driver submits payment
* Treasurer verifies payment
* System updates status (Paid / Rejected)
* Compliance audits records (read-only)
* Audit log stored

---

---

### HOW THE REPORT WILL WORK

#### 1. REPORT SUBMISSION (DRIVER)
**DRIVER ACTION:**
* Report a fellow driver

**REQUIRED DETAILS:**
* Reported driver (name / ID / plate no.)
* Violation type
* Description
* Date & time
* Location
* Evidence (image/video)

**RESULT:**
* Status = SUBMITTED

---

#### 2. ENCODING & COMPLETENESS CHECK (SECRETARY)
**SECRETARY ACTION:**
* Encode the report into the system
* Check if the details are complete

**CHECK:**
* Is there evidence?
* Is the info complete?
* Is the format valid?

**RESULT:**
* If Complete then PENDING VALIDATION
* If Incomplete it will be returned to the driver

---

#### 3. VALIDATION (COMPLIANCE OFFICER)
**COMPLIANCE ACTION:**
* Verify if the report is legit

**CHECK:**
* Is the violation valid?
* Does the evidence match?
* Is there a duplicate from another driver's report?
* Is there a false accusation?

**RESULT:**
* If Valid forward to Vice
* If Invalid reject report

---

#### 4. FINAL DECISION (VICE PRESIDENT)
**VICE ACTION:**
* Approve or reject report

**RESULT:**
* If APPROVED official violation
* If REJECTED dismissed

---

#### 5. SYSTEM ACTION (AUTO)
**If APPROVED:**
* Violation recorded
* Penalty generated
* Payment created
* Driver notified

---

#### 6. NOTIFICATIONS (SYSTEM)
**REPORTER DRIVER:**
* Report submitted
* Report approved/rejected

**REPORTED DRIVER:**
* Violation notice
* Penalty details

**REPORT STATUS:**
* Submitted
* Pending Validation
* For Approval
* Approved
* Rejected

---

#### FULL REPORT FLOW
* Driver submits report
* Secretary encodes & checks completeness
* Compliance validates report
* Vice President approves/rejects

**If approved:**
* Violation recorded
* Penalty generated
* Payment created
* System sends notifications
* Audit log recorded

---

---

### HOW TO ADD A NEW MEMBER (SECRETARY ONLY)

#### PHASE 1: SECRETARY (DATA ENCODING)
**Step-by-step:**
1. Secretary logs in
2. Go to Member Management
3. Click “Add Member”

**Fill up Member Information:**
* Full Name
* Address
* Contact Number
* License Number
* Plate Number

**Upload Documents (optional/required):**
* ID
* License
* OR/CR

**Validation Check (SYSTEM)**
* System checks:
  * Are all required fields complete?
  * Is the format correct? (e.g. contact number, license)

**If there is anything missing:**
* Error → cannot be saved

**If complete:**
* Proceed

**Submit Record**
* Secretary clicks Save / Submit

**RESULT:**
* Status = PENDING APPROVAL
* Not yet an official member

---

#### PHASE 2: SYSTEM ACTION
1. System saves record in the database
2. System creates audit log
3. System notifies Vice President

---

#### PHASE 3: VICE PRESIDENT (APPROVAL)
**Step-by-step:**
* VP logs in
* Go to Pending Members
* Select the new member

**Review Details:**
* Personal info
* Documents
* Completeness

**Decision:**
* **APPROVE**
  * Member becomes ACTIVE
  * Can now log in / use the system
* **REJECT**
  * Member is DECLINED
  * Will not be activated

---

#### PHASE 4: SYSTEM FINAL ACTION
**If APPROVED:**
1. Status = ACTIVE MEMBER
2. Create member account
3. Send notification

**If REJECTED:**
1. Status = REJECTED
2. Notify Secretary

---

#### SIMPLE FLOW (EASY VERSION)
* Secretary → Encode Member
* System → Save (Pending)
* Vice President → Review
* Approve / Reject
* System → Activate / Decline