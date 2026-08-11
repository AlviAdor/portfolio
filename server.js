const express = require("express");
const path = require("path");

const app = express();
const port = process.env.PORT || 3000;
const rootDir = __dirname;

app.disable("x-powered-by");

app.use(express.json());
app.use(express.urlencoded({ extended: false }));
app.use("/assets", express.static(path.join(rootDir, "assets")));

app.get("/", (_req, res) => {
  res.sendFile(path.join(rootDir, "index.html"));
});

app.get("/security-authentication", (_req, res) => {
  res.sendFile(path.join(rootDir, "security-authentication.html"));
});

app.get("/security-validation", (_req, res) => {
  res.sendFile(path.join(rootDir, "security-validation.html"));
});

app.get("/security-data-integrity", (_req, res) => {
  res.sendFile(path.join(rootDir, "security-data-integrity.html"));
});

app.get("/security-messaging", (_req, res) => {
  res.sendFile(path.join(rootDir, "security-messaging.html"));
});

app.get("/*.html", (req, res) => {
  res.sendFile(path.join(rootDir, req.path));
});

app.post("/api/contact", (req, res) => {
  const name = String(req.body?.name || "").trim();
  const email = String(req.body?.email || "").trim();
  const message = String(req.body?.message || "").trim();
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (name.length < 2 || name.length > 60) {
    return res.status(400).json({ ok: false, error: "Invalid name" });
  }

  if (!emailPattern.test(email) || email.length > 120) {
    return res.status(400).json({ ok: false, error: "Invalid email" });
  }

  if (message.length < 10 || message.length > 1000) {
    return res.status(400).json({ ok: false, error: "Invalid message" });
  }

  return res.status(200).json({ ok: true });
});

app.use((_req, res) => {
  res.status(404).sendFile(path.join(rootDir, "index.html"));
});

app.listen(port, () => {
  console.log(`Portfolio server running on http://localhost:${port}`);
});