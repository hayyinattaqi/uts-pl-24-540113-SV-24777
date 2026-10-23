<template>
  <main class="login-page">
    <section class="login-card" aria-labelledby="login-title">
      <div class="login-brand">
        <span class="brand-mark" aria-hidden="true">✚</span>
        <div>
          <strong class="brand">Inventaris Klinik</strong>
          <div class="brand-caption">Sistem pengelolaan obat</div>
        </div>
      </div>

      <h1 id="login-title">Selamat datang</h1>
      <p class="login-intro">Masuk untuk melanjutkan ke inventaris obat klinik.</p>

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

        <p v-if="generalError" class="form-error" role="alert">{{ generalError }}</p>

        <button class="button" type="submit" :disabled="loading">
          {{ loading ? 'Memproses...' : 'Masuk' }}
        </button>
      </form>
    </section>
  </main>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { login, setToken } from '../api/auth'

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
    await router.replace({ name: 'medicines' })
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
