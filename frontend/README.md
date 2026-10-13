# 🤖 AI Code Review — Frontend (Vue.js)

A Vue.js Single Page Application that lets users submit code for AI-powered review and see quality scores with detailed improvement suggestions.

## ⚙️ Tech Stack
- **Vue 3** (Composition API + `<script setup>`)
- **Vite** build tool
- **Vanilla CSS** (no frameworks)

## 🚀 Setup

### 1. Clone & Install
```bash
git clone <your-repo-url>
cd code-review-ui
npm install
```

### 2. Run Development Server
```bash
npm run dev
# App runs at http://localhost:5173
```

> ⚠️ Make sure the Laravel API backend is running at `http://localhost:8000`

### 3. Build for Production
```bash
npm run build
```

## 📁 Project Structure
```
src/
├── App.vue          ← Main application component
├── main.js          ← Entry point
└── style.css        ← Global styles
```

## 🔗 Backend
This app requires the [code-review-api](../code-review-api) Laravel backend to be running.
