// src/services/chatApi.js

// get private messages
export async function getMessages(senderId, receiverId, token) {
  try {
    const obj = {
      sender_id: senderId,
      receiver_id: receiverId,
    };

    const response = await fetch(
      "http://localhost/API/react/new-chat-app/backend/class/getprivatemessage.php",
      {
        method: "POST",
        body: JSON.stringify(obj),
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );
    const result = await response.json();
    return result;
  } catch (error) {
    // console.error("getMessages() error:", error);
    throw error;
  }
}

// search user to chat with.
export async function searchUser(username, token) {
  try {
    const response = await fetch(
      "http://localhost/API/react/new-chat-app/backend/class/getsearchquery.php",
      {
        method: "POST",
        body: JSON.stringify({ username }),
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );

    const result = await response.json();
    return result;
  } catch (error) {
    console.error("searchUser() error:", error);
    throw error;
  }
}

// check user chats. exists or not exists.
export async function checkUserChats(userId, receiverId, token) {
  try {
    const response = await fetch(
      "http://localhost/API/react/new-chat-app/backend/class/getuserchats.php",
      {
        method: "POST",
        body: JSON.stringify({
          user_id: userId,
          receiver_id: receiverId,
        }),
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );

    const result = await response.json();
    return result;
  } catch (error) {
    console.error("checkUserChats() error:", error);
    throw error;
  }
}

// delete message.
export async function DeleteMessage(msgId, userId, token) {
  try {
    const response = await fetch(
      "http://localhost/API/react/new-chat-app/backend/class/deletemessage.php",
      {
        method: "POST",
        body: JSON.stringify({
          m_id: msgId,
          sdr_id: userId,
        }),
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );

    const result = await response.json();
    return result;
  } catch (error) {
    console.error("deleteMessage() error:", error);
    throw error;
  }
}

// save private message
export async function savePrivateMessage(senderId, receiverId, message, token) {
  try {
    const response = await fetch(
      "http://localhost/API/react/new-chat-app/backend/class/saveprivatemessage.php",
      {
        method: "POST",
        body: JSON.stringify({
          s_id: senderId,
          r_id: receiverId,
          message: message,
        }),
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );

    const result = await response.json();
    return result;
  } catch (error) {
    console.error("savePrivateMessage error:", error);
    throw error;
  }
}

// save chat after saving private message .

export async function saveChat(senderId, receiverId, token) {
  try {
    const response = await fetch(
      "http://localhost/API/react/new-chat-app/backend/class/savechat.php",
      {
        method: "POST",
        body: JSON.stringify({
          s_id: senderId,
          r_id: receiverId,
        }),
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );

    const result = await response.json();
    return result;
  } catch (error) {
    console.error("saveChat error:", error);
    throw error;
  }
}

// update message read status
export async function updateReadStatus(senderId, receiverId, token) {
  try {
    const response = await fetch(
      "http://localhost/API/react/new-chat-app/backend/class/update_read_status.php",
      {
        method: "POST",
        body: JSON.stringify({
          sender_id: senderId,
          receiver_id: receiverId,
        }),
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );

    const result = await response.json();
    return result;
  } catch (error) {
    console.error("updateReadStatus error:", error);
    throw error;
  }
}
