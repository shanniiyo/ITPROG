<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="style.css">

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Locker Reservation FAQ</title>

<style>
body {
  margin: 0;
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
  color: #0f172a;
  background: #ffffff;
}

.container {
  width: min(1120px, 92vw);
  margin: 0 auto;
}

h1 {
    text-align: center;
    margin-bottom: 10px;
}

.note {
    background: #fff3cd;
    border-left: 5px solid #ffc107;
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 6px;
    font-size: 14px;
}

/* FAQ */
.faq {
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.faq-item {
    border-bottom: 1px solid #e5e7eb;
      padding: 20px 0;
}

.faq-item:last-child {
    border-bottom: none;
}

.faq-question {
    padding: 15px 20px;
    cursor: pointer;
    font-weight: 600;
    position: relative;
}

.faq-question::after {
    position: absolute;
    right: 20px;
    font-size: 18px;
}

input[type="checkbox"] {
    display: none;
    
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    padding: 0 40px;
    font-size: 14px;
    transition: max-height 0.3s ease;
}

input[type="checkbox"]:checked ~ .faq-answer {
    max-height: 200px;
    padding-bottom: 15px;
}

</style>
</head>

<body>

<?php include 'navbar_client.php'; ?>

    <div class = "hero">

<div class="container">
    <h1>📦 Locker Reservation FAQ</h1>

    <div class="note">
        ⚠️ <strong>Important:</strong> All locker reservations must be booked at least 
        <strong>24 hours in advance</strong>. Same-day bookings are not allowed.
    </div>

    <div class="faq">

        <div class="faq-item">
            <input type="checkbox" id="q1">
            <label class="faq-question" for="q1">What is this system?</label>
            <div class="faq-answer">
                This system allows you to reserve lockers online, choose duration, and securely access them using a generated code.
            </div>
        </div>

        <div class="faq-item">
            <input type="checkbox" id="q2">
            <label class="faq-question" for="q2">Do I need an account?</label>
            <div class="faq-answer">
                Yes, you need to create an account to reserve lockers, manage bookings, and receive notifications.
            </div>
        </div>

        <div class="faq-item">
            <input type="checkbox" id="q3">
            <label class="faq-question" for="q3">How do I reserve a locker?</label>
            <div class="faq-answer">
                Log in, choose a locker, select your schedule, and confirm your reservation. Remember that booking must be done at least 24 hours in advance.
            </div>
        </div>

        <div class="faq-item">
            <input type="checkbox" id="q4">
            <label class="faq-question" for="q4">Can I extend my reservation?</label>
            <div class="faq-answer">
                Yes, you can extend your reservation depending on availability. Additional charges may apply.
            </div>
        </div>

        <div class="faq-item">
            <input type="checkbox" id="q5">
            <label class="faq-question" for="q5">What payment methods are available?</label>
            <div class="faq-answer">
                You can pay using card or e-wallet options supported by the system.
            </div>
        </div>

        <div class="faq-item">
            <input type="checkbox" id="q6">
            <label class="faq-question" for="q6">How do I access my locker?</label>
            <div class="faq-answer">
                After payment, you will receive a unique access code that allows you to open your assigned locker.
            </div>
        </div>

        <div class="faq-item">
            <input type="checkbox" id="q7">
            <label class="faq-question" for="q7">What if I cannot open my locker?</label>
            <div class="faq-answer">
                You can report the issue through the system, and support will assist you as soon as possible.
            </div>
        </div>

        <div class="faq-item">
            <input type="checkbox" id="q8">
            <label class="faq-question" for="q8">Will I receive notifications?</label>
            <div class="faq-answer">
                Yes, you will receive updates such as confirmations, reminders, and status changes.
            </div>
        </div>

        <div class="faq-item">
            <input type="checkbox" id="q9">
            <label class="faq-question" for="q9">Can I cancel my reservation?</label>
            <div class="faq-answer">
                Yes, cancellations are allowed based on system policies. Refunds may depend on payment status.
            </div>
        </div>

        <div class="faq-item">
            <input type="checkbox" id="q10">
            <label class="faq-question" for="q10">How do I contact support?</label>
            <div class="faq-answer">
                You can use the support form by providing your name, email, subject, and message.
            </div>
        </div>

    </div>

</div>
            </div>


</body>
</html>