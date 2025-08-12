import { useEffect, useState } from "react";
import "../css/userchat.css";

import socket from "../JS/socket";

export default function UserChats({
  u_id,
  open_chat,
  refresh,
  loggedID,
  setNewmessage,
  UnReadChatsInicator,
  setUnreadChats,
  update_msg_status,
  chatbox_ref,
  MsgUpdated_flag,
}) {
  const [userchats, setUserchats] = useState([]); // List of user chats
  const [chatfound, setChatfound] = useState(false);
  const [token, setToken] = useState("");

  useEffect(() => {
    getuserchat();
  }, [MsgUpdated_flag]);

  // Set token from sessionStorage on mount
  useEffect(() => {
    setToken(sessionStorage.getItem("token"));
    return () => socket.disconnect(); // cleanup socket on component unmount
  }, []);

  // Refresh chat list whenever `refresh` changes
  useEffect(() => {
    getuserchat();
  }, [refresh]);

  // Listen to socket event for new messages
  useEffect(() => {
    if (token) getuserchat(); // Fetch chats initially

    // listen for new message event.
    socket.on("newMessageIndicator", ({ from }) => {
      getuserchat();
      setNewmessage(true);
    });

    return () => {
      socket.off("newMessageIndicator"); // clean after notifying for new message
    };
  }, [token]);

  // Fetch chats for this user from your PHP API
  function getuserchat() {
    const obj = { user_id: u_id };
    const req_body = JSON.stringify(obj);

    fetch(
      "http://localhost/API/react/new-chat-app/backend/class/getuserchats.php",
      {
        body: req_body,
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    )
      .then((res) => res.json())
      .then((result) => {
        if (result.status === "success") {
          setUserchats(result.data); // Set chat list
          setChatfound(true);
        } else if (result.status === "Database_error") {
          return;
        }
      })
      .catch((err) => {
        console.log(err);
      });
  }

  // Format timestamps
  function formatmessagetime(timestamp) {
    const dateObj = new Date(timestamp);
    let hours = dateObj.getHours();
    let minutes = dateObj.getMinutes().toString().padStart(2, "0");
    let meridiem = hours >= 12 ? "PM" : "AM";
    hours = hours % 12 || 12;
    return `${hours}:${minutes} ${meridiem}`;
  }

  return (
    <div className="userchats_container">
      <div className="heading_chats">
        <h1>Chats</h1>
      </div>
      {chatfound ? (
        <div className="userchats">
          <div className="chats">
            {userchats.map((chat) => (
              <div
                className="chat"
                style={{ cursor: "pointer" }}
                key={chat.chat_id}
                onClick={() => {
                  open_chat(chat.chat_partner_id, chat.chat_partner_name);
                  getuserchat();

                  // if chat box is open on receiver side then dont update msg status from here.
                  chatbox_ref.current && chat.reciverID === chat.senderID
                    ? null
                    : update_msg_status(chat.senderID, chat.reciverID);

                  // clear new message indicator
                  UnReadChatsInicator.length > 0
                    ? setUnreadChats((prev) =>
                        prev.filter((id) => id != chat.senderID)
                      )
                    : null;
                }}
              >
                <p className="name">{chat.chat_partner_name}</p>
                {/* message preview logic */}
                <p className="last_message">
                  {chat.deleted ? (
                    chat.deleted_by === null ? (
                      <span style={{ color: "white" }}>
                        This message was deleted.
                      </span>
                    ) : Number(chat.deleted_by) === Number(loggedID) ? (
                      <span style={{ color: "white" }}>
                        You deleted this message.
                      </span>
                    ) : (
                      <span style={{ color: "white" }}>
                        This message was deleted.
                      </span>
                    )
                  ) : Number(chat.senderID) === Number(loggedID) ? (
                    <>
                      <span style={{ color: "white" }}>You: </span>
                      <span>{chat.last_message}</span>
                    </>
                  ) : (
                    <span
                      className={`message_show ${
                        Number(chat.senderID) !== Number(loggedID) &&
                        chat.is_read === 0
                          ? "unread"
                          : "read"
                      }`}
                    >
                      {Number(chat.senderID) !== Number(loggedID) &&
                      chat.is_read === 0 ? (
                        <>
                          <span style={{ color: "white", fontWeight: "bold" }}>
                            New :
                          </span>{" "}
                          {chat.last_message}
                        </>
                      ) : (
                        chat.last_message
                      )}
                    </span>
                  )}
                </p>

                <p className="time">
                  {formatmessagetime(chat.last_message_time)}
                </p>
              </div>
            ))}
          </div>
        </div>
      ) : (
        <div>
          <p>No Previous Chats.</p>
        </div>
      )}
    </div>
  );
}
