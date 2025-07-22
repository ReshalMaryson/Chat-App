const express = require("express");
const http = require("http");
const { Server } = require("socket.io");
const cors = require("cors");

const app = express();
app.use(express.json());
app.use(cors());

const server = http.createServer(app);
const io = new Server(server, {
  cors: { origin: "http://localhost:5173", methods: ["GET", "POST"] },
});

const connectedUsers = {};
const chatboxStates = {}; // New: { userId: receiverId }

io.on("connection", (socket) => {
  console.log(`Socket Id Generted : ${socket.id}`);

  // event join
  socket.on("join", (userId) => {
    if (!userId) {
      console.log("Received undefined userId!");
    }
    connectedUsers[userId] = socket.id;
    console.log(`User ${userId} connected with socket ID: ${socket.id}`);
    // online status server.js
    // socket.broadcast.emit("user-online", userId);
  });

  // Private message
  socket.on(
    "privateMessage",
    ({ id, sender_id, receiver_id, message, created_at }) => {
      const receiverSocketId = connectedUsers[receiver_id];

      const newMessage = {
        id,
        sender_id,
        receiver_id,
        message,
        created_at,
      };

      // Always send to sender and receiver if online
      if (receiverSocketId) {
        io.to(receiverSocketId).emit("receiveMessage", newMessage);
        socket.emit("receiveMessage", newMessage);
      } else {
        socket.emit("receiveMessage", newMessage);
      }

      // 🔍 Check if receiver has chat open with sender
      const isReceiverChattingWithSender =
        chatboxStates[receiver_id] === sender_id;

      if (!isReceiverChattingWithSender && receiverSocketId) {
        io.to(receiverSocketId).emit("newMessageIndicator", {
          from: sender_id,
          message,
        });
        console.log(
          `User ${receiver_id} not chatting with ${sender_id}, sending indicator`
        );
      }
    }
  );

  // deletion of messages
  socket.on("messageDeleted", (data) => {
    io.emit("messageDeleted", data);
  });

  // Chat box open
  socket.on("chatboxopen", ({ userId, receiverId }) => {
    if (userId && receiverId) {
      chatboxStates[userId] = receiverId;
      console.log(`User ${userId} opened chat with ${receiverId}`);
    }
  });

  //  Chat box close (currently not using it)
  socket.on("chatboxclose", ({ userId }) => {
    delete chatboxStates[userId];
    console.log(`User ${userId} closed chat`);
  });

  //event disconnect
  socket.on("disconnect", () => {
    for (let userId in connectedUsers) {
      if (connectedUsers[userId] === socket.id) {
        console.log(`User ${userId} disconnected`);
        delete connectedUsers[userId];
        break;
      }
    }
  });
});

server.listen(5000, () => console.log("Server running on port 5000"));
