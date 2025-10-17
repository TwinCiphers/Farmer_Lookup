// server.js

const express = require('express');
const nodemailer = require('nodemailer');
const app = express();
const port = 3000;

// --- IMPORTANT: CONFIGURE YOUR EMAIL SERVICE ---
// Using a service like Gmail, SendGrid, or Mailgun is recommended.
// For Gmail, you MUST use an App Password, not your regular password.
const transporter = nodemailer.createTransport({
    host: 'smtp.gmail.com',  // Replace with your email host (e.g., smtp.sendgrid.net)
    port: 587,
    secure: false, // true for 465, false for other ports
    auth: {
        user: 'YOUR_SENDER_EMAIL@gmail.com', // Your actual sending email address
        pass: 'YOUR_GMAIL_APP_PASSWORD'    // Your App Password or API Key
    }
});

// Middleware to parse JSON and URL-encoded bodies (for form data)
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// --- SECURITY NOTE: CORS SETUP ---
// This allows your front-end code (running on a different port/address) 
// to send requests to this server.
app.use((req, res, next) => {
    // Replace '*' with your actual front-end domain in a production environment
    res.header('Access-Control-Allow-Origin', '*'); 
    res.header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Content-Type');
    next();
});


// ----------------------------------------------------
// POST ROUTE TO HANDLE CONTACT FORM SUBMISSION
// ----------------------------------------------------
app.post('/submit-contact', async (req, res) => {
    const { name, email, subject, message } = req.body;

    // Basic validation to ensure all fields are present
    if (!name || !email || !subject || !message) {
        return res.status(400).json({ success: false, message: 'All fields are required.' });
    }

    // Email content configuration
    const mailOptions = {
        from: `"${name} (Local Market Inquiry)" <${email}>`, // Sender's name and email
        to: 'YOUR_RECEIVING_EMAIL@example.com',           // Where you want to receive the messages
        subject: `[Market Inquiry] ${subject}`,
        html: `
            <p>You have received a new contact message from the Local Food Market website.</p>
            <h3>Contact Details:</h3>
            <ul>
                <li><strong>Name:</strong> ${name}</li>
                <li><strong>Email:</strong> ${email}</li>
                <li><strong>Subject:</strong> ${subject}</li>
            </ul>
            <h3>Message:</h3>
            <p>${message.replace(/\n/g, '<br>')}</p>
        `
    };

    try {
        // Send the email
        await transporter.sendMail(mailOptions);
        console.log(`Email successfully sent from ${email}`);
        
        // Send a successful response back to the front-end
        res.status(200).json({ success: true, message: 'Message sent successfully!' });
    } catch (error) {
        console.error('Error sending email:', error);
        
        // Send an error response back to the front-end
        res.status(500).json({ success: false, message: 'Failed to send message. Please try again later.' });
    }
});

// Start the server
app.listen(port, () => {
    console.log(`\nMarket Contact Backend listening at http://localhost:${port}`);
    console.log('----------------------------------------------------');
    console.log('To start, run: node server.jss');
});