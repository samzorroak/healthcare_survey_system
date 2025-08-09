# Healthcare Survey Management System

The **Healthcare Survey Management System** is a web-based application designed to manage, conduct, and analyze healthcare-related surveys. It streamlines the process of survey creation, participant allocation, questionnaire management, response collection, and payout processing. The system is ideal for healthcare research organizations, clinics, or institutions conducting surveys with targeted participants.

---

## 📋 Features

### **Survey Management**

* Create, edit, and manage surveys with details like title, description, budget, and schedule.
* Dynamically add survey questionnaires with **single-choice** or **multiple-choice** questions.
* Manage agreements and letters such as **Welcome Letter**, **Participant Agreement**, and **Consent Letter** with placeholder support.

### **Participant Management**

* Register and manage participants with complete details (personal info, specialization, documents, location).
* Allocate participants to surveys based on **state**, **city**, and **specialization**.
* Invite participants via email with survey participation links.

### **Questionnaire & Response Handling**

* Dynamically render questions with adjustable answer choices.
* Store participant responses securely in the database.
* Track survey completion status per participant.

### **Payout Management**

* View participants’ survey completion status.
* Verify survey responses before releasing payouts.
* Manage **payment amounts** and **payment statuses** (Pending/Done) with an easy UI.

### **Reporting & Analytics**

* Generate detailed survey reports with organization details, budget, and participant statistics.
* Visualize responses per question using interactive charts.

---

## 🛠️ Technologies Used

* **Frontend:** HTML5, CSS3, JavaScript (jQuery, AJAX), Bootstrap
* **Backend:** PHP (OOP-based structure), AJAX-powered requests
* **Database:** MySQL (with optimized queries and indexing)
* **Text Editing:** TinyMCE for agreements and letters
* **Charting:** Chart.js for report visualizations
* **Email Sending:** PHPMailer

---

## 📂 Project Structure

```
/config.php              # Database connection
/manage_entity.php       # Backend entity handler
/EntityManager.php       # Reusable DB interaction methods
/survey.php              # Survey management UI
/participant.php         # Participant management UI
/questionnaire.php       # Survey questionnaire for participants
/report.php              # Survey report page
/agreement.php           # Agreement & letter management
/payout.php              # Payout management page
/assets/                 # CSS, JS, images
```

---

## 🚀 How It Works

1. **Admin/Client** creates a survey and adds questions.
2. **Participants** are allocated based on criteria and invited via email.
3. Participants view **Welcome Letter**, accept **Agreement & Consent**, and complete the questionnaire.
4. Responses are stored, and admin verifies them before processing payouts.
5. Reports and analytics are generated for decision-making.

---

## 🔒 Security Features

* Input validation and SQL injection prevention.
* Session-based authentication.
* Secure file uploads for participant documents.

---

## 📈 Future Enhancements

* Role-based access control (RBAC).
* Export survey reports in PDF/Excel formats.
* Multi-language support.
* Integration with SMS gateways for participant notifications.
