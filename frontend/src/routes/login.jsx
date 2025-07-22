import "../css/login.css";
import React, { useState, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import Navbar from "../components/navbar";

export default function Login() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loginErr, setLoginErr] = useState("");
  const navigate = useNavigate();

  function login() {
    if (email === "" || password === "") {
      setLoginErr("Please Fill Fields");
      return;
    }

    const obj = {
      email: email,
      password: password,
    };
    const data = JSON.stringify(obj);

    fetch("http://localhost/API/react/new-chat-app/backend/class/login.php", {
      method: "POST",
      body: data,
      headers: { "Content-Type": "application/json" },
    })
      .then((response) => {
        return response.json();
      })
      .then((result) => {
        if (result.status === "success") {
          sessionStorage.setItem("token", result.token);
          sessionStorage.setItem("user", result.user.username);
          sessionStorage.setItem("user_id", result.user.id);

          setLoginErr("");
          navigate("/chat");
        } else if (result.status === "unauthorized") {
          setLoginErr("incorrect Email or Password");
          return;
        }
      })
      .catch((error) => {
        console.log(error);
        setLoginErr("Error While Logging in, Please try Later.");
      });
  }

  useEffect(() => {
    const timer = setTimeout(() => {
      setLoginErr("");
    }, 2500);
    return () => clearTimeout(timer);
  }, [loginErr]);

  return (
    <>
      <Navbar />
      <div className="login_container">
        <h1>login</h1>
        <div className="login_box">
          <div className="get-email">
            <label>Email</label>
            <input
              type="email"
              value={email}
              onChange={(e) => {
                setEmail(e.target.value);
              }}
            />
          </div>

          <div className="get-password">
            <label>Password</label>
            <br />
            <input
              type="password"
              value={password}
              onChange={(e) => {
                setPassword(e.target.value);
              }}
            />
          </div>
          {loginErr ? (
            <p style={{ color: "red", fontSize: "0.8rem" }}>{loginErr}</p>
          ) : null}
          <div className="login_btn">
            <button onClick={login} type="button" style={{ cursor: "pointer" }}>
              Login
            </button>
          </div>
        </div>
        <div style={{ fontSize: "0.8rem" }}>
          <p>
            don't have a Account? <Link to="/register">Sign-up</Link> Now.
          </p>
        </div>
      </div>
    </>
  );
}
