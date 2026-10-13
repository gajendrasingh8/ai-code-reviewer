<template>
  <div class="app-shell">

    <!-- ── Header ── -->
    <header class="site-header">
      <div class="header-inner">
        <div class="brand">
          <div class="brand-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
          </div>
          <div>
            <h1 class="brand-name">AI Code Review</h1>
            <!-- <p class="brand-sub">Powered by Mock AI Engine</p> -->
          </div>
        </div>
        <div class="header-stats" v-if="stats.total_reviews > 0">
          <span class="stat-pill">
            <span class="stat-dot"></span>
            {{ stats.total_reviews }} reviews
          </span>
          <span class="stat-pill">
            ⭐ {{ stats.average_quality_score }}/10 avg
          </span>
        </div>
      </div>
    </header>

    <!-- ── Main ── -->
    <main class="main-grid">

      <!-- LEFT: Input Panel -->
      <section class="panel">
        <div class="panel-header">
          <h2 class="panel-title">
            <span class="panel-icon">📝</span> Submit Code
          </h2>
        </div>

        <!-- Language Tabs -->
        <div class="lang-tabs">
          <button
            v-for="lang in languages"
            :key="lang.value"
            class="lang-tab"
            :class="{ active: selectedLanguage === lang.value }"
            @click="selectedLanguage = lang.value"
          >
            <span class="lang-emoji">{{ lang.emoji }}</span>
            {{ lang.label }}
          </button>
        </div>

        <!-- Code Editor -->
        <div class="editor-wrap">
          <div class="editor-topbar">
            <span class="editor-lang-badge">{{ selectedLanguage }}</span>
            <span class="char-count" :class="{ warn: codeInput.length > 4500 }">
              {{ codeInput.length }} / 5000
            </span>
          </div>
          <textarea
            id="code-input"
            v-model="codeInput"
            class="code-editor"
            :placeholder="`Paste your ${selectedLanguage} code here…`"
            spellcheck="false"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="input-actions">
          <button id="load-example-btn" class="btn btn-ghost" @click="loadExample">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Load Example
          </button>
          <button id="clear-btn" class="btn btn-ghost" @click="resetReview" v-if="codeInput">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Clear
          </button>
        </div>

        <button
          id="analyze-btn"
          class="btn btn-primary"
          :disabled="isLoading || codeInput.length < 10"
          @click="submitReview"
        >
          <template v-if="!isLoading">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            Analyze Code
          </template>
          <template v-else>
            <span class="spinner"></span>
            Analyzing… (1–3s)
          </template>
        </button>
      </section>

      <!-- RIGHT: Results Panel -->
      <section class="panel results-col">

        <!-- Empty State -->
        <div v-if="!currentReview && !isLoading" class="empty-state">
          <div class="empty-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
          </div>
          <h3 class="empty-title">No Analysis Yet</h3>
          <p class="empty-sub">Paste your code on the left and click <strong>Analyze Code</strong> to get instant feedback.</p>
        </div>

        <!-- Skeleton Loader -->
        <div v-if="isLoading" class="skeleton-wrap">
          <div class="skeleton-score"></div>
          <div class="skeleton-line w-3/4"></div>
          <div class="skeleton-line w-1/2"></div>
          <div class="skeleton-card"></div>
          <div class="skeleton-card"></div>
          <div class="skeleton-card"></div>
        </div>

        <!-- Results -->
        <div v-if="currentReview && !isLoading" class="results-wrap animate-in">

          <!-- Score Card -->
          <div class="score-card">
            <div class="score-ring-wrap">
              <svg class="score-ring" viewBox="0 0 100 100">
                <circle class="ring-bg" cx="50" cy="50" r="40" fill="none" stroke-width="8"/>
                <circle
                  class="ring-fill"
                  cx="50" cy="50" r="40" fill="none" stroke-width="8"
                  :stroke="scoreColor"
                  :stroke-dasharray="`${currentReview.quality_score * 25.13} 251.3`"
                  stroke-linecap="round"
                  transform="rotate(-90 50 50)"
                />
              </svg>
              <div class="score-center">
                <span class="score-num">{{ currentReview.quality_score }}</span>
                <span class="score-denom">/10</span>
              </div>
            </div>
            <div class="score-info">
              <div class="score-label" :style="{ color: scoreColor }">{{ scoreLabel }}</div>
              <div class="score-meta">
                <span>🕐 {{ currentReview.analysis_time_ms }}ms</span>
                <span>🌐 {{ selectedLanguage }}</span>
              </div>
              <div class="score-bar-wrap">
                <div class="score-bar" :style="{ width: `${currentReview.quality_score * 10}%`, background: scoreColor }"></div>
              </div>
            </div>
          </div>

          <!-- Issues List -->
          <div class="issues-wrap">
            <div class="issues-header">
              <h3 class="issues-title">Issues Found</h3>
              <div class="issues-badges">
                <span v-if="highCount" class="badge badge-high">{{ highCount }} High</span>
                <span v-if="medCount"  class="badge badge-med">{{ medCount }} Med</span>
                <span v-if="lowCount"  class="badge badge-low">{{ lowCount }} Low</span>
              </div>
            </div>

            <div class="issues-list">
              <div
                v-for="(issue, i) in currentReview.issues"
                :key="i"
                class="issue-item"
                :class="`sev-${issue.severity}`"
                :style="{ animationDelay: `${i * 80}ms` }"
              >
                <div class="issue-left">
                  <span class="sev-dot"></span>
                </div>
                <div class="issue-body">
                  <div class="issue-top">
                    <span class="sev-tag">{{ issue.severity }}</span>
                    <span class="issue-title">{{ issue.issue }}</span>
                  </div>
                  <p class="issue-hint">💡 {{ issue.suggestion }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Toolbar -->
          <div class="result-toolbar">
            <button id="copy-btn" class="btn btn-ghost btn-sm" @click="copyResults">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              {{ copied ? 'Copied!' : 'Copy' }}
            </button>
            <button id="download-btn" class="btn btn-ghost btn-sm" @click="downloadJSON">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Download JSON
            </button>
            <button id="new-btn" class="btn btn-primary btn-sm" @click="resetReview">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              New Analysis
            </button>
          </div>
        </div>
      </section>
    </main>

    <!-- Toast -->
    <transition name="toast">
      <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
        <span class="toast-icon">{{ toast.type === 'error' ? '❌' : '✅' }}</span>
        {{ toast.message }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// ── State ──────────────────────────────────────────────────────────────────
const codeInput       = ref('')
const selectedLanguage = ref('javascript')
const currentReview   = ref(null)
const isLoading       = ref(false)
const copied          = ref(false)
const stats           = ref({ total_reviews: 0, average_quality_score: 0 })
const toast           = ref({ show: false, type: 'error', message: '' })

const API_URL = 'http://localhost:8000/api/v1'

// ── Languages ─────────────────────────────────────────────────────────────
const languages = [
  { value: 'javascript', label: 'JavaScript', emoji: '🟨' },
  { value: 'php',        label: 'PHP',        emoji: '🐘' },
  { value: 'laravel',    label: 'Laravel',    emoji: '🔺' },
  { value: 'python',     label: 'Python',     emoji: '🐍' },
]

// ── Computed ───────────────────────────────────────────────────────────────
const scoreColor = computed(() => {
  const s = currentReview.value?.quality_score ?? 0
  if (s >= 8) return '#10b981'
  if (s >= 6) return '#4f8ef7'
  if (s >= 4) return '#f59e0b'
  return '#ef4444'
})

const scoreLabel = computed(() => {
  const s = currentReview.value?.quality_score ?? 0
  if (s >= 9) return '⭐ Excellent'
  if (s >= 7) return '👍 Good Code'
  if (s >= 5) return '⚠️ Needs Work'
  return '❌ Poor Quality'
})

const highCount = computed(() => currentReview.value?.issues.filter(i => i.severity === 'high').length ?? 0)
const medCount  = computed(() => currentReview.value?.issues.filter(i => i.severity === 'medium').length ?? 0)
const lowCount  = computed(() => currentReview.value?.issues.filter(i => i.severity === 'low').length ?? 0)

// ── Lifecycle ──────────────────────────────────────────────────────────────
onMounted(() => fetchStats())

// ── Methods ────────────────────────────────────────────────────────────────
const fetchStats = async () => {
  try {
    const res  = await fetch(`${API_URL}/stats`)
    const data = await res.json()
    if (data.success) stats.value = data.stats
  } catch { /* silently ignore */ }
}

const showToast = (message, type = 'error') => {
  toast.value = { show: true, type, message }
  setTimeout(() => { toast.value.show = false }, 4000)
}

const loadExample = () => {
  const examples = {
    javascript: `async function fetchUser(id) {
  try {
    const response = await fetch(\`/api/user/\${id}\`);
    if (!response.ok) throw new Error('User not found');
    return await response.json();
  } catch (error) {
    console.error('Failed to fetch user:', error);
    throw error;
  }
}`,
    php: `function calculateTotal($items) {
  $total = 0;
  foreach ($items as $item) {
    if (!isset($item['price'])) {
      continue;
    }
    $total += $item['price'];
  }
  return $total;
}`,
    laravel: `public function store(Request $request)
{
  $validated = $request->validate([
    'name'  => 'required|string|max:255',
    'email' => 'required|email|unique:users'
  ]);
  $user = User::create($validated);
  return response()->json($user);
}`,
    python: `def calculate_average(numbers):
    if not numbers:
        return 0
    total   = sum(numbers)
    average = total / len(numbers)
    return round(average, 2)`,
  }
  codeInput.value = examples[selectedLanguage.value]
}

const submitReview = async () => {
  if (codeInput.value.length < 10) {
    showToast('Code must be at least 10 characters')
    return
  }

  isLoading.value    = true
  currentReview.value = null

  try {
    const res  = await fetch(`${API_URL}/reviews`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ code: codeInput.value, language: selectedLanguage.value }),
    })
    const data = await res.json()

    if (!res.ok) {
      // 503 = Gemini is temporarily overloaded
      if (res.status === 503) {
        showToast('⏳ AI is busy right now. Please wait a moment and try again.')
      } else {
        showToast(data.error || 'Analysis failed. Please try again.')
      }
      return
    }

    if (data.success) {
      currentReview.value = data.review
      fetchStats()
    }
  } catch (err) {
    showToast('Network error. Is the server running?')
  } finally {
    isLoading.value = false
  }
}

const copyResults = async () => {
  const text = `Quality Score: ${currentReview.value.quality_score}/10\n\nIssues:\n${
    currentReview.value.issues.map(i => `[${i.severity.toUpperCase()}] ${i.issue}\n  → ${i.suggestion}`).join('\n\n')
  }`
  await navigator.clipboard.writeText(text)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
  showToast('Results copied to clipboard!', 'success')
}

const downloadJSON = () => {
  const blob = new Blob([JSON.stringify(currentReview.value, null, 2)], { type: 'application/json' })
  const url  = URL.createObjectURL(blob)
  const a    = Object.assign(document.createElement('a'), { href: url, download: `review-${Date.now()}.json` })
  a.click()
  URL.revokeObjectURL(url)
}

const resetReview = () => {
  codeInput.value    = ''
  currentReview.value = null
}
</script>

<style scoped>
/* ── Layout ────────────────────────────────────────────────────────────────── */
.app-shell {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* ── Header ─────────────────────────────────────────────────────────────────── */
.site-header {
  position: sticky;
  top: 0;
  z-index: 50;
  background: rgba(9,14,26,0.85);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border);
}
.header-inner {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0.85rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
.brand { display: flex; align-items: center; gap: 0.75rem; }
.brand-icon {
  width: 38px; height: 38px;
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: white;
  box-shadow: 0 0 16px var(--accent-glow);
  flex-shrink: 0;
}
.brand-name { font-size: 1rem; font-weight: 700; color: var(--text-1); }
.brand-sub  { font-size: 0.7rem; color: var(--text-3); margin-top: 1px; }

.header-stats { display: flex; gap: 0.6rem; flex-wrap: wrap; }
.stat-pill {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.3rem 0.75rem;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 999px;
  font-size: 0.7rem;
  color: var(--text-2);
}
.stat-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--success);
  box-shadow: 0 0 6px var(--success);
  animation: pulse 2s ease infinite;
}
@keyframes pulse { 0%,100%{ opacity:1 } 50%{ opacity:.4 } }

/* ── Main Grid ───────────────────────────────────────────────────────────────── */
.main-grid {
  flex: 1;
  max-width: 1400px;
  margin: 0 auto;
  width: 100%;
  padding: 2rem;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  align-items: start;
}
@media(max-width:900px){ .main-grid{ grid-template-columns:1fr; } }

/* ── Panel ───────────────────────────────────────────────────────────────────── */
.panel {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.panel-header { display:flex; align-items:center; justify-content:space-between; }
.panel-title {
  display:flex; align-items:center; gap:.5rem;
  font-size:.95rem; font-weight:600; color:var(--text-1);
}
.panel-icon { font-size:1.1rem; }

/* ── Lang Tabs ───────────────────────────────────────────────────────────────── */
.lang-tabs {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.4rem;
  background: var(--bg-secondary);
  border-radius: 10px;
  padding: 0.3rem;
}
.lang-tab {
  display:flex; align-items:center; justify-content:center; gap:.35rem;
  padding: .45rem .5rem;
  border: none; border-radius: 7px;
  background: transparent;
  color: var(--text-3);
  font-size: .72rem; font-weight:500;
  cursor: pointer;
  transition: all .2s;
}
.lang-tab:hover { color: var(--text-2); background: var(--bg-elevated); }
.lang-tab.active {
  background: var(--accent);
  color: #fff;
  box-shadow: 0 2px 12px var(--accent-glow);
}
.lang-emoji { font-size: .9rem; }

/* ── Code Editor ─────────────────────────────────────────────────────────────── */
.editor-wrap {
  border: 1px solid var(--border-accent);
  border-radius: 10px;
  overflow: hidden;
  transition: border-color .2s;
}
.editor-wrap:focus-within { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }

.editor-topbar {
  display:flex; align-items:center; justify-content:space-between;
  padding: .4rem .75rem;
  background: var(--bg-elevated);
  border-bottom: 1px solid var(--border);
}
.editor-lang-badge {
  font-size: .65rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em;
  color: var(--accent); background: var(--accent-glow);
  padding:.15rem .5rem; border-radius:4px;
}
.char-count { font-size:.68rem; color:var(--text-3); font-family:'JetBrains Mono',monospace; }
.char-count.warn { color: var(--warning); }

.code-editor {
  display: block; width: 100%;
  min-height: 320px; max-height: 480px;
  background: var(--bg-secondary);
  color: var(--text-1);
  border: none; outline: none; resize: vertical;
  padding: 1rem;
  font-family: 'JetBrains Mono', 'Courier New', monospace;
  font-size: .82rem; line-height: 1.65;
  tab-size: 2;
}
.code-editor::placeholder { color: var(--text-3); }

/* ── Input Actions ───────────────────────────────────────────────────────────── */
.input-actions { display:flex; gap:.6rem; }

/* ── Buttons ─────────────────────────────────────────────────────────────────── */
.btn {
  display: inline-flex; align-items: center; gap: .4rem;
  border: none; border-radius: 8px;
  font-size: .82rem; font-weight: 600;
  cursor: pointer; transition: all .18s;
  padding: .6rem 1.1rem;
}
.btn:disabled { opacity:.4; cursor:not-allowed; transform:none !important; }

.btn-primary {
  width: 100%;
  justify-content: center;
  background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
  color: #fff;
  box-shadow: 0 4px 20px var(--accent-glow);
  padding: .8rem 1.5rem;
}
.btn-primary:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 6px 24px var(--accent-glow); }
.btn-primary:active:not(:disabled) { transform:translateY(0); }

.btn-ghost {
  background: var(--bg-elevated);
  color: var(--text-2);
  border: 1px solid var(--border);
}
.btn-ghost:hover { background: #243047; color:var(--text-1); transform:translateY(-1px); }

.btn-sm { padding: .45rem .85rem; font-size: .75rem; border-radius: 7px; }

/* Spinner */
.spinner {
  width:15px; height:15px;
  border: 2px solid rgba(255,255,255,.25);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to{ transform:rotate(360deg) } }

/* ── Empty State ─────────────────────────────────────────────────────────────── */
.results-col { min-height: 500px; justify-content:center; }

.empty-state {
  flex: 1;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  text-align: center; padding: 3rem 2rem; gap: 1rem;
  color: var(--text-3);
}
.empty-icon {
  width: 80px; height: 80px;
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  border-radius: 20px;
  display: flex; align-items: center; justify-content: center;
  color: var(--text-3);
  margin-bottom: .5rem;
}
.empty-title { font-size:1.1rem; font-weight:600; color:var(--text-2); }
.empty-sub { font-size:.82rem; line-height:1.6; max-width:280px; }
.empty-sub strong { color: var(--text-2); }

/* ── Skeleton ────────────────────────────────────────────────────────────────── */
.skeleton-wrap { display:flex; flex-direction:column; gap:1rem; width:100%; }
@keyframes shimmer {
  0%   { background-position: -400px 0 }
  100% { background-position: 400px 0 }
}
.skeleton-score, .skeleton-line, .skeleton-card {
  border-radius: 10px;
  background: linear-gradient(90deg, var(--bg-elevated) 25%, #1f2f45 50%, var(--bg-elevated) 75%);
  background-size: 800px 100%;
  animation: shimmer 1.4s infinite;
}
.skeleton-score { height: 110px; }
.skeleton-line  { height: 14px; }
.w-3\/4 { width: 75% }
.w-1\/2 { width: 50% }
.skeleton-card  { height: 72px; }

/* ── Results ─────────────────────────────────────────────────────────────────── */
.results-wrap {
  display: flex; flex-direction: column; gap: 1.25rem; width: 100%;
}
.animate-in { animation: fadeUp .35s ease both; }
@keyframes fadeUp {
  from { opacity:0; transform:translateY(12px) }
  to   { opacity:1; transform:translateY(0) }
}

/* Score Card */
.score-card {
  display: flex; align-items: center; gap: 1.5rem;
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
}
.score-ring-wrap { position:relative; width:90px; height:90px; flex-shrink:0; }
.score-ring { width:100%; height:100%; }
.ring-bg    { stroke: var(--bg-secondary); }
.ring-fill  { transition: stroke-dasharray .6s ease; }

.score-center {
  position: absolute; inset:0;
  display:flex; flex-direction:column; align-items:center; justify-content:center;
}
.score-num   { font-size:1.6rem; font-weight:700; line-height:1; }
.score-denom { font-size:.65rem; color:var(--text-3); }

.score-info { flex:1; display:flex; flex-direction:column; gap:.6rem; }
.score-label { font-size:1rem; font-weight:600; }
.score-meta  { display:flex; gap:1rem; font-size:.72rem; color:var(--text-3); }
.score-bar-wrap {
  height: 4px; background: var(--bg-secondary); border-radius: 2px; overflow:hidden;
}
.score-bar {
  height: 100%; border-radius: 2px;
  transition: width .6s cubic-bezier(.4,0,.2,1);
}

/* Issues */
.issues-wrap {
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
}
.issues-header {
  display:flex; align-items:center; justify-content:space-between;
  padding: .85rem 1.25rem;
  border-bottom: 1px solid var(--border);
}
.issues-title { font-size:.85rem; font-weight:600; color:var(--text-2); }
.issues-badges { display:flex; gap:.4rem; }
.badge {
  font-size:.62rem; font-weight:700; text-transform:uppercase;
  padding:.2rem .55rem; border-radius:4px;
}
.badge-high { background: var(--danger-glow);  color: var(--danger); }
.badge-med  { background: var(--warning-glow); color: var(--warning); }
.badge-low  { background: var(--accent-glow);  color: var(--accent); }

.issues-list { display:flex; flex-direction:column; }

.issue-item {
  display: flex; gap: .75rem;
  padding: .9rem 1.25rem;
  border-bottom: 1px solid var(--border);
  animation: fadeUp .3s ease both;
  transition: background .15s;
}
.issue-item:last-child { border-bottom:none; }
.issue-item:hover { background: rgba(255,255,255,.03); }

.issue-left { padding-top:.35rem; }
.sev-dot { display:block; width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.sev-high  .sev-dot { background: var(--danger);  box-shadow: 0 0 6px var(--danger); }
.sev-medium .sev-dot { background: var(--warning); box-shadow: 0 0 6px var(--warning); }
.sev-low   .sev-dot { background: var(--accent);   box-shadow: 0 0 6px var(--accent); }

.issue-body { flex:1; }
.issue-top  { display:flex; align-items:center; gap:.5rem; margin-bottom:.3rem; }
.sev-tag {
  font-size:.6rem; font-weight:700; text-transform:uppercase;
  padding:.15rem .45rem; border-radius:3px;
}
.sev-high   .sev-tag { background:var(--danger-glow);  color:var(--danger); }
.sev-medium .sev-tag { background:var(--warning-glow); color:var(--warning); }
.sev-low    .sev-tag { background:var(--accent-glow);  color:var(--accent); }

.issue-title { font-size:.82rem; font-weight:500; color:var(--text-1); }
.issue-hint  { font-size:.75rem; color:var(--text-3); line-height:1.5; }

/* Toolbar */
.result-toolbar {
  display:flex; gap:.6rem; flex-wrap:wrap;
}

/* ── Toast ───────────────────────────────────────────────────────────────────── */
.toast {
  position: fixed; bottom: 1.5rem; right: 1.5rem;
  display:flex; align-items:center; gap:.6rem;
  padding: .75rem 1.25rem;
  border-radius: 10px;
  font-size: .82rem; font-weight: 500;
  box-shadow: 0 8px 32px rgba(0,0,0,.4);
  z-index: 9999;
  max-width: 360px;
}
.toast-error   { background: var(--danger);  color:#fff; }
.toast-success { background: var(--success); color:#fff; }

.toast-enter-active, .toast-leave-active { transition: all .3s ease; }
.toast-enter-from, .toast-leave-to { transform: translateY(12px); opacity:0; }
</style>
