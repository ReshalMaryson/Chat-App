import { Route, Routes } from "react-router-dom";
import Chat from "./routes/chat";
import Login from "./routes/login";
import Register from "./routes/register";
import Home from "./routes/home";

function App() {
  return (
    <>
      <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/chat" element={<Chat />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
      </Routes>
    </>
  );
}

export default App;
