import { Link } from "react-router-dom";
import "../css/home.css";
import Navbar from "../components/navbar";

export default function Home() {
  return (
    <>
      <div className="home_container">
        <Navbar />
        <h3>Welcome to Chat App</h3>
        <div className="chatnow">
          {sessionStorage.getItem("token") ? (
            <Link className="link_chatnow" to="/chat">
              Let's Chat
            </Link>
          ) : (
            <Link className="link_chatnow" to="/login">
              Let's Chat
            </Link>
          )}
        </div>
      </div>
    </>
  );
}
