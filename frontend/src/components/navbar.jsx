import "../css/navbar.css";
import { Link } from "react-router-dom";
import { useNavigate } from "react-router-dom";
import React, { useState, useEffect } from "react";
import socket from "../JS/socket";

export default function Navbar() {
  const navigate = useNavigate();
  const [isAuthenticated, setAuthenticated] = useState(false);

  useEffect(() => {
    if (sessionStorage.getItem("token")) {
      setAuthenticated(true);
    }
  }, [sessionStorage]);

  function logout() {
    if (sessionStorage.getItem("token")) {
      socket.disconnect(); // clear socket id
      sessionStorage.clear();
      setAuthenticated(false);
      if (location.pathname !== "/") {
        navigate("/"); // this one
      } else {
        window.location.reload();
      }
    }
  }

  return (
    <>
      <div className="navbar">
        <div className="heading_navbar" style={{ cursor: "pointer" }}>
          <Link to="/" className="link_login">
            Chat App
          </Link>
        </div>
        <ul>
          {isAuthenticated ? (
            <li onClick={logout} style={{ cursor: "pointer" }}>
              Logout
            </li>
          ) : (
            <li>
              <Link to="/login" className="link_login">
                login
              </Link>
            </li>
          )}
        </ul>
      </div>
    </>
  );
}
