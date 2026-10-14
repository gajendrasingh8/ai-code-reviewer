# 🤖 AI Code Review — Backend (Laravel API)

A Laravel REST API that analyzes code using **Google Gemini AI** and returns quality scores and improvement suggestions.

## ⚙️ Tech Stack
- **PHP 8.3** + **Laravel 11**
- **MySQL** database
- **Google Gemini AI** (`gemini-3.8-flash`)
- **UUID** primary keys

## 🚀 Setup

### 1. Clone & Install
```bash
git clone <your-repo-url>
cd code-review-api
composer install
```

### 2. Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and fill in:
```env
DB_DATABASE=code_review
DB_USERNAME=root
DB_PASSWORD=

# Get free key at https://aistudio.google.com
GEMINI_API_KEY=your_key_here
```

### 3. Database
```bash
php artisan migrate
```

### 4. Run
```bash
php artisan serve
# API runs at http://localhost:8000
```

## 📡 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/reviews` | Submit code for AI review |
| `GET`  | `/api/v1/stats` | Get global statistics |

### POST `/api/v1/reviews`
**Request:**
```json
{
  "code": "function hello() { console.log('hi') }",
  "language": "javascript"
}
```
**Response:**
```json
{
  "success": true,
  "review": {
    "id": "uuid-here",
    "quality_score": 7,
    "issues": [
      {
        "severity": "low",
        "issue": "Missing semicolons",
        "suggestion": "Add semicolons for consistency"
      }
    ],
    "analysis_time_ms": 1200
  }
}
```

**Supported languages:** `javascript`, `php`, `laravel`, `python`

## 📁 Project Structure
```
app/
├── Http/
│   ├── Controllers/Api/CodeReviewController.php
│   └── Requests/CodeReviewRequest.php
├── Models/CodeReview.php
└── Services/GeminiService.php
routes/
└── api.php
```


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
