# 💬 Chat App

A **one-to-one real-time chat web app** that supports live messaging, read/unread status, message deletion like WhatsApp, and responsive mobile/desktop UI.

---

## 🛠️ Tech Stack

| Layer         | Tech Used         |
|---------------|-------------------|
| Frontend      | React             |
| Realtime Comm | Node.js + Socket.IO |
| Backend API   | PHP               |
| Database      | MySQL             |
| Auth          | JWT               |

---

## ✨ Features

- 🔐 **Login with email and password**
  - Passwords are securely **hashed**
  - JWT-based authentication

- 📝 **Register**
  - Username, email, password

- 🔍 **Search users** to chat with

- 💬 **Private one-to-one messaging**
  - Real-time via Socket.IO
  - Previous chat history

- 🔔 **Unread messages indicator**
  - Shows which chats have new messages

- 👁️‍🗨️ **Read/unread UI**
  - Seen messages marked visually

- ❌ **Delete messages**
  - Sender sees: "You deleted this message"
  - Receiver sees: "This message was deleted"

- 🔢 **Unread chat counter**
  - Number of chats with unread messages

- 📱💻 Fully responsive
  - Works great on **mobile and desktop**

---

## 🚀 Project Structure
chat-app/
├── backend/ → PHP APIs & DB scripts
| └── class/
│ └── config/
│     └── chat_app.sql → Sample DB import
├── frontend/ → React client
├── server/ → Node.js + Socket.IO server


## ⚙️ Setup Instructions

### 🔌 Backend (PHP + MySQL)

1. Make sure XAMPP (or similar) is installed.
2. Place the project in:  
   `htdocs/API/react/new-chat-app/`
3. Import the provided database:
   - File path: `backend/config/chat_app.sql`
   - Import into your local MySQL DB using phpMyAdmin or CLI

> ℹ️ Default login credentials (if using saved DB users):
> Use any email from the DB, and password = `username123`

---

### 💻 Frontend (React)
cd frontend
npm install
npm start
---


### 🔌 Node.js Server (Socket.IO)
cd server
npm install
node index.js


###🧪 Manual Testing Guide
Follow these steps to test the complete chat flow:

- Register two users, or use existing users from the imported DB.

- Login with:
Email from DB
Password: (username + 123) e.g Alex + 123 -> Alex123

- Open two browser tabs, login as each user.

Start a chat and test:

🔄 Messages show up in real-time

✅ Read status updates when user views message

❌ Message deletion shows proper UI on both sides

🔔 Unread chat indicators work


### 🌐 API Notes
API is currently set to:
http://localhost/API/react/new-chat-app/backend/class/filename.php

If you clone this project into a different folder, make sure to update the API paths in:
- frontend/src/services/chatApi.js
- frontend/src/components/UserChat.jsx

Thanks.
