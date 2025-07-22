import "../css/register.css";
import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import Navbar from "../components/navbar";

export default function Register() {
  const [Username, setUsername] = useState("");
  const [Email, setEmail] = useState("");
  const [Password, setPassword] = useState("");
  const [err, setErr] = useState("");

  const navigate = useNavigate();

  function signUp() {
    if (Username === "" || Email === "" || Password === "") {
      setErr("Please Fill all fields");
      return;
    }

    const obj = {
      username: Username,
      email: Email,
      password: Password,
    };

    const send_obj = JSON.stringify(obj);
    fetch("http://localhost/API/react/chat-app/backend/class/register.php", {
      method: "POST",
      body: send_obj,
      headers: { "Content-Type": "application/json" },
    })
      .then((res) => {
        return res.json();
      })
      .then((result) => {
        if (result.status === "success") {
          navigate("/login");
          setUsername("");
          setEmail("");
          setPassword("");
          setErr("");
          return;
        } else if (result.status === "emailexist") {
          setErr("Email already exists");
          return;
        }
      })
      .catch((err) => {
        setErr("Error Signing in. Please try Later.");
        return;
      });
  }

  useEffect(() => {
    const timer = setTimeout(() => {
      setErr("");
    }, 2500);

    return () => clearTimeout(timer);
  }, [err]);

  return (
    <>
      {" "}
      <Navbar />
      <div className="register_container">
        <h1>Sign-up</h1>
        <div className="register_box">
          <div className="register_item">
            <label htmlFor="">Username</label>
            <input
              type="text"
              name=""
              id=""
              value={Username}
              onChange={(e) => {
                setUsername(e.target.value);
              }}
            />
          </div>
          <div className="register_item">
            <label htmlFor="">Email</label>
            <input
              type="email"
              name=""
              id="sign-in-email"
              className={err === "Email already exists" ? "exists" : ""}
              value={Email}
              onChange={(e) => {
                setEmail(e.target.value);
              }}
            />
          </div>
          <div className="register_item">
            <label htmlFor="">password</label>
            <input
              type="text"
              name=""
              id=""
              value={Password}
              onChange={(e) => {
                setPassword(e.target.value);
              }}
            />
          </div>
          {err ? (
            <p style={{ color: "red", fontSize: "0.8rem" }}>{err}</p>
          ) : null}
          <div className="send">
            <button onClick={signUp} style={{ cursor: "pointer" }}>
              Sign-Up
            </button>
          </div>
        </div>
      </div>
    </>
  );
}
