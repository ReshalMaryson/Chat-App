import React, { useState, useEffect, useRef } from "react";
import Navbar from "../components/navbar";
import { useNavigate } from "react-router-dom";
import backArrow from "../assets/images/backarrow-white.png";
import messageicon from "../assets/images/messagebox.png";
import sendicon from "../assets/images/send.png";
import findicon from "../assets/images/find.png";
import deletemsgicon from "../assets/images/deleteicon_black.png";
import "../css/chat.css";
import UserChats from "../components/userchats";
// socket instance
import socket from "../JS/socket";

// import api methods
import {
  getMessages,
  searchUser,
  checkUserChats,
  DeleteMessage,
  savePrivateMessage,
  saveChat,
  updateReadStatus,
} from "../services/chatApi";

export default function Chat() {
  const navigate = useNavigate();
  const [userId, setUserId] = useState(null);
  const [user_name, setUser_Name] = useState("");
  const [message, setMessage] = useState("");
  const [messages, setMessages] = useState([]);
  const [token, setToken] = useState(sessionStorage.getItem("token"));
  const [chatexist, setChatexist] = useState(false); // chat saving
  const [unreadChats, setUnreadChats] = useState([]);

  //DOM Varibales.
  const [isUserslider_open, setUserslider] = useState(false);
  const [loadingmessages, setmessageloading] = useState(false);
  const [isSugesstionBox_open, setSugestionBox] = useState(false);
  const [searchedUser, setSearchedUser] = useState([]);
  const [userfound, setUserfound] = useState(false);
  const [chat_user_name, setChat_user_name] = useState("");
  const [refreshchatlist, setRefreshchatlist] = useState(0);
  const [showDeleteMsgId, setShowDeleteMsgId] = useState(null);
  const [showNewMessage, setshowNewMessage] = useState(false);
  const [mesgUpdate, setMsgUpdated] = useState(Date.now());
  const chatBoxRef = useRef(null); // used to keep messages scrolled down

  // search query
  const [searchuser_query, setSearchquery] = useState("");

  //chat box open ref
  const [isChatbox_open, setChatboxopen] = useState(false);
  const chatboxStatusRef = useRef(null); // used to tell if chat box is open or not
  useEffect(() => {
    chatboxStatusRef.current = isChatbox_open;
  }, [isChatbox_open]);

  // recevier id ref
  const [receiverId, setReceiverId] = useState("");
  const receiverIdRef = useRef("");
  useEffect(() => {
    receiverIdRef.current = receiverId;
  }, [receiverId]);

  //------------Queries and events--------------//

  // check for token.
  useEffect(() => {
    if (!token) {
      navigate("/");
      return;
    } else {
      // Fetch and set user details from sessionStorage on mount
      const storedUserName = sessionStorage.getItem("user");
      const storedUserId = sessionStorage.getItem("user_id");

      if (storedUserId) {
        setUser_Name(storedUserName);
        setUserId(storedUserId);
      }
    }
  }, []);

  // deletion event
  useEffect(() => {
    socket.on("messageDeleted", (data) => {
      setMessages((prevMessages) =>
        prevMessages.map((msg) =>
          msg.id === data.message_id
            ? {
                ...msg,
                is_deleted: true,
                deleted_by: data.deleted_by,
              }
            : msg
        )
      );
    });

    return () => {
      socket.off("messageDeleted");
    };
  }, []);

  // Receive new messages listener
  useEffect(() => {
    socket.on("receiveMessage", (data) => {
      const isMeSender = Number(data.sender_id) === Number(userId);
      const isMeReceiver = Number(data.receiver_id) === Number(userId);
      const isChatOpen = chatboxStatusRef.current;
      const isFromCurrentChatUser =
        Number(data.sender_id) === Number(receiverIdRef.current) ||
        Number(data.receiver_id) === Number(receiverIdRef.current);

      // Only add the message if chatbox is open & sender=current_chat_id
      if (isChatOpen && isFromCurrentChatUser) {
        setMessages((prev) => [...prev, data]);

        // Update read status if it's not my own message
        if (!isMeSender) {
          update_msg_read(data.sender_id, userId);
        }
      } else {
        setUnreadChats((prev) => {
          if (prev.includes(Number(data.sender_id))) return prev;
          return [...prev, Number(data.sender_id)];
        });
      }

      setRefreshchatlist((prev) => (prev > 1000 ? 0 : prev + 1));
    });

    socket.on("messageDeleted", () => {
      setRefreshchatlist((prev) => (prev > 1000 ? 0 : prev + 1));
    });

    return () => {
      socket.off("receiveMessage");
    };
  }, [userId]);

  //check for connection distrubance and re-connect the user on socket.
  useEffect(() => {
    const user_id = sessionStorage.getItem("user_id");

    if (user_id && !socket.connected) {
      socket.connect();
    }

    const handleConnect = () => {
      if (user_id) {
        socket.emit("join", user_id);
      }
    };

    socket.on("connect", handleConnect);

    return () => {
      socket.off("connect", handleConnect);
    };
  }, []);

  // Send and save private message
  function sendMessage() {
    if (!message?.trim()) return;

    // Save and emit message
    if (receiverId) {
      savePrivateMessage(userId, receiverId, message, token)
        .then((result) => {
          if (result.status === "success") {
            const newMessage = {
              id: result.data.id,
              sender_id: userId,
              message: result.data.message,
              created_at: result.data.created_at,
            };

            socket.emit("privateMessage", {
              ...newMessage,
              receiver_id: receiverId,
            });
          } else {
            setRefreshchatlist(0);
          }
        })
        .catch((err) => console.error("sendMessage error:", err));

      setMessage("");
    }

    // Save chat if it doesn't exist
    if (!chatexist) {
      saveChat(userId, receiverId, token)
        .then((result) => {
          if (result.status === "success" || result.status === "chatexists") {
            setChatexist(true);
          } else {
            setChatexist(false);
          }
        })
        .catch((err) => {
          console.error("saveChat error:", err);
          setChatexist(false);
        });
    }
  }

  //search user to chat.
  useEffect(() => {
    if (!searchuser_query.trim()) {
      setSearchedUser([]);
      if (isSugesstionBox_open) {
        setSugestionBox(false);
      }
      return;
    }

    const debounceTimer = setTimeout(() => {
      setSugestionBox(true);

      searchUser(searchuser_query, token)
        .then((result) => {
          if (result.status === "success") {
            setSearchedUser(result.users);
            setUserfound(true);
          } else {
            setUserfound(false);
            setSearchedUser([]);
          }
        })
        .catch((err) => {
          console.error("Error searching user:", err);
          setSearchedUser([]);
        });
    }, 300);

    return () => clearTimeout(debounceTimer);
  }, [searchuser_query]);

  // check user chats. exists or not exists.
  useEffect(() => {
    if (userId && receiverId) {
      checkUserChats(userId, receiverId, token)
        .then((result) => {
          if (result.status === "success") {
            setChatexist(true);
          } else if (result.status === "notfound") {
            setChatexist(false); // this will trigger save chat
          }
        })
        .catch((err) => {
          console.error("checkUserChats error:", err);
        });
    }
  }, [isChatbox_open]);

  // delete message
  function deletemessage(msg_id) {
    DeleteMessage(msg_id, userId, token)
      .then((result) => {
        if (result.status === "success") {
          socket.emit("messageDeleted", {
            message_id: msg_id,
            deleted_by: userId,
          });
          setRefreshchatlist((prev) => (prev > 1000 ? 0 : prev + 1));
        }
      })
      .catch((err) => {
        console.error("deletemessage error:", err);
      });
  }

  // update the messages read status.
  // this is also being used in userchat.jsx upon sedning new message.
  function update_msg_read(s_id, r_id) {
    updateReadStatus(s_id, r_id, token)
      .then((res) => {
        if (res.success === true) {
          setMsgUpdated(true);
        } else {
          console.error("Failed to update read status.");
          setMsgUpdated(Date.now()); // still trigger an update
        }
      })
      .catch((err) => {
        console.error("update_msg_read error:", err);
        setMsgUpdated(false);
      });
  }

  //--------DOM Manupilation functions---------//
  function chatopen(r_id, name) {
    setRefreshchatlist((prev) => (prev > 1000 ? 0 : prev + 1));
    setReceiverId(r_id);
    setChat_user_name(name);
    setUserslider(false);
    setChatboxopen(true);

    // setting new message.
    getMessages(userId, r_id, token).then((result) => {
      if (result.status === "success") {
        setMessages(result.data);
      } else {
        console.log("Failed to get messages");
      }
    });
  }

  function chatclose() {
    setRefreshchatlist((prev) => (prev > 1000 ? 0 : prev + 1));
    setChatboxopen(false);
    setMessages([]);
    setChat_user_name("");
    setSearchquery("");
    setChatexist(false);
    setReceiverId("");
  }

  // chat box opened event
  useEffect(() => {
    if (isChatbox_open) {
      socket.emit("chatboxopen", {
        userId: userId,
        receiverId: receiverId,
      });
      // console.log("chat box opened");
      return;
    } else {
      // console.log("chat box closed");
      return;
    }
  }, [isChatbox_open]);

  function formatmessagetime(timestamp) {
    const dateObj = new Date(timestamp);
    let hours = dateObj.getHours();
    let minutes = dateObj.getMinutes().toString().padStart(2, "0");
    let meridiem = hours >= 12 ? "PM" : "AM";

    // Convert to 12-hour format
    hours = hours % 12 || 12;

    return `${hours}:${minutes} ${meridiem}`;
  }

  function showdelete_msg(msgId, senderId) {
    if (Number(userId) === Number(senderId)) {
      setShowDeleteMsgId((prevId) => (prevId === msgId ? null : msgId));
    }
  }
  // keep chats scrolled down.
  useEffect(() => {
    // this so div keep scrolling down for new message.
    if (chatBoxRef.current) {
      chatBoxRef.current.scrollTop = chatBoxRef.current.scrollHeight;
    }
  }, [messages]);

  // send message by pressing enter key after typing message.
  function SendMessage_enterKey(e) {
    if (e.key === "Enter") {
      e.preventDefault();
      if (message.trim() !== "" && message !== null) {
        sendMessage();
        setRefreshchatlist(true);
      }
    } else {
      return;
    }
  }

  return (
    <>
      <div className="chat-container">
        <Navbar />
        <p className="username_p">Welcome {user_name}</p>

        <div className={`show_userslider ${isUserslider_open ? "active" : ""}`}>
          <button
            onClick={() => {
              setUserslider(true);
              isChatbox_open ? setChatboxopen(false) : null;
            }}
          >
            Chats
            <img src={findicon} alt="" />
          </button>
          {unreadChats.length > 0 ? (
            <div className="new_msg_indicator">{unreadChats.length}</div>
          ) : null}
        </div>

        <div className="wrap_userchat_chatbox">
          <div className={`usersilder ${isUserslider_open ? "active" : ""}`}>
            <div className="goback">
              <img
                src={backArrow}
                alt=""
                width="30"
                onClick={() => {
                  setUserslider(false);
                  setSearchquery("");
                }}
              />
            </div>
            <div className="silder_content">
              <div className="search_users">
                <input
                  type="search"
                  className="slider_searchfeild"
                  placeholder="Search by UserName"
                  value={searchuser_query}
                  onChange={(e) => {
                    setSearchquery(e.target.value);
                  }}
                />
              </div>
              <div
                className={`display_users ${
                  isSugesstionBox_open ? "active" : ""
                }`}
              >
                {/* showing user suggestions */}
                {userfound ? (
                  <div>
                    {searchedUser.map((user) => (
                      <div className="displayuser_chat" key={user.id}>
                        <p>{user.username}</p>
                        <img
                          src={messageicon}
                          alt=""
                          onClick={() => {
                            chatopen(user.id, user.username);
                          }}
                        />
                      </div>
                    ))}
                  </div>
                ) : (
                  <p style={{ textAlign: "center", fontSize: "0.6rem" }}>
                    USER NOT FOUND
                  </p>
                )}
              </div>
              <div className="showchats">
                <UserChats
                  u_id={userId}
                  open_chat={chatopen}
                  refresh={refreshchatlist}
                  loggedID={userId}
                  setNewmessage={setshowNewMessage}
                  newmessage={showNewMessage}
                  UnReadChatsInicator={unreadChats}
                  setUnreadChats={setUnreadChats}
                  update_msg_status={update_msg_read}
                  chatbox_ref={chatboxStatusRef}
                  receiverId={receiverId} // to compare with
                  MsgUpdated_flag={mesgUpdate} // used to refresh userchat.
                />
              </div>
            </div>
          </div>

          {loadingmessages ? (
            <div className="loading">
              <p>loading Chat...</p>
            </div>
          ) : (
            <div className={`chatbox ${isChatbox_open ? "active" : ""}`}>
              <div className="user_chat">
                <p>{chat_user_name}</p>
                <p
                  onClick={() => {
                    chatclose();
                  }}
                >
                  ❌
                </p>
              </div>
              <div className="chat_window" ref={chatBoxRef}>
                {messages.length > 0 ? (
                  messages.map((msg) => (
                    <div
                      key={msg.id}
                      className={`message ${
                        Number(userId) === Number(msg.sender_id)
                          ? "sent"
                          : "received"
                      }`}
                      onClick={() => showdelete_msg(msg.id, msg.sender_id)}
                    >
                      <p className="p_message">
                        {msg.is_deleted
                          ? Number(msg.deleted_by) === Number(userId)
                            ? "You deleted this message"
                            : "This message was deleted"
                          : msg.message}
                      </p>
                      <p className="p_time">
                        {formatmessagetime(msg.created_at)}
                      </p>

                      {/* this is to show delete message icon */}
                      <div>
                        {showDeleteMsgId === msg.id && (
                          <div className="delete active">
                            <img
                              src={deletemsgicon}
                              alt=""
                              className="deletemsg active"
                              onClick={() => deletemessage(msg.id)}
                            />
                          </div>
                        )}
                      </div>
                    </div>
                  ))
                ) : (
                  <p
                    style={{
                      textAlign: "center",
                      fontSize: "0.8rem",
                      color: "wheat",
                    }}
                  >
                    Start Chat By Sending a Message to {chat_user_name}
                  </p>
                )}
              </div>
              <div className="send_message">
                <textarea
                  placeholder="Type Message..."
                  value={message}
                  onChange={(e) => {
                    setMessage(e.target.value);
                  }}
                  onKeyDown={SendMessage_enterKey}
                ></textarea>
                {message === "" || message === null ? null : (
                  <img src={sendicon} alt="" onClick={sendMessage} />
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </>
  );
}
