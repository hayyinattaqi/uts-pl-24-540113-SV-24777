<template>
  <main class="login-page">
    <section class="login-card" aria-labelledby="login-title">
      <header class="login-head">
        <BrandMark />
        <div>
          <strong>Inventaris Klinik</strong>
          <span>Stok obat per batch dan tanggal kedaluwarsa</span>
        </div>
      </header>

      <div class="login-body">
        <h1 id="login-title">Masuk</h1>
        <p class="login-intro">Gunakan akun petugas klinik.</p>

        <form @submit.prevent="handleSubmit">
          <div class="form-field">
            <label for="email">Email</label>
            <input
              id="email"
              v-model.trim="email"
              type="email"
              autocomplete="username"
              placeholder="nama@klinik.id"
              required
              :aria-invalid="Boolean(fieldErrors.email)"
              :aria-describedby="fieldErrors.email ? 'email-error' : undefined"
              @input="clearErrors"
            />
            <p v-if="fieldErrors.email" id="email-error" class="field-error" role="alert">
              {{ fieldErrors.email }}
            </p>
          </div>

          <div class="form-field">
            <label for="password">Kata sandi</label>
            <input
              id="password"
              v-model="password"
              type="password"
              autocomplete="current-password"
              placeholder="Masukkan kata sandi"
              required
              :aria-invalid="Boolean(fieldErrors.password)"
              :aria-describedby="fieldErrors.password ? 'password-error' : undefined"
              @input="clearErrors"
            />
            <p v-if="fieldErrors.password" id="password-error" class="field-error" role="alert">
              {{ fieldErrors.password }}
            </p>
          </div>

          <p v-if="generalError" class="alert alert-error" role="alert">
            <AppIcon name="alert" />
            <span>{{ generalError }}</span>
          </p>

          <button class="button login-submit" type="submit" :disabled="loading">
            {{ loading ? 'Memproses...' : 'Masuk' }}
          </button>
        </form>
      </div>
    </section>
  </main>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { login, setToken } from '../api/auth'
import AppIcon from '../components/AppIcon.vue'
import BrandMark from '../components/BrandMark.vue'

const router = useRouter()
const email = ref('')
const password = ref('')
const loading = ref(false)
const generalError = ref('')
const fieldErrors = reactive({
  email: '',
  password: '',
})

function clearErrors() {
  fieldErrors.email = ''
  fieldErrors.password = ''
  generalError.value = ''
}

async function handleSubmit() {
  if (loading.value) return

  clearErrors()
  loading.value = true

  try {
    const response = await login(email.value, password.value)
    setToken(response.data.data.token)
    await router.replace({ name: 'dashboard' })
  } catch (error) {
    const status = error.response?.status

    if (status === 422) {
      const errors = error.response.data.errors ?? {}
      fieldErrors.email = errors.email?.[0] ?? ''
      fieldErrors.password = errors.password?.[0] ?? ''
      generalError.value = Object.keys(errors).some((field) => !['email', 'password'].includes(field))
        ? 'Periksa kembali data yang dimasukkan.'
        : ''
    } else if (status === 401) {
      generalError.value = 'Email atau kata sandi salah'
    } else {
      generalError.value = 'Tidak dapat masuk saat ini. Periksa koneksi, lalu coba lagi.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  display: grid;
  min-height: 100vh;
  place-items: center;
  padding: 2rem 1rem;
  background: var(--ground);
}

.login-card {
  width: min(100%, 420px);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  background: var(--paper);
  overflow: hidden;
}

.login-head {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-bottom: 3px solid var(--green-300);
  background: var(--green-800);
  color: #fff;
}

.login-head .brand-mark {
  --mark-ground: var(--green-900);

  width: 40px;
  height: 40px;
}

.login-head strong {
  display: block;
  font-size: 1.05rem;
}

.login-head span {
  color: var(--head-ink-muted);
  font-size: 0.85rem;
}

.login-body {
  padding: 1.75rem 1.5rem 1.5rem;
}

.login-body h1 {
  margin: 0;
  font-size: 1.6rem;
  letter-spacing: -0.02em;
}

.login-intro {
  margin: 0.25rem 0 1.5rem;
  color: var(--ink-muted);
}

.login-submit {
  width: 100%;
  margin-top: 0.4rem;
}
</style>
