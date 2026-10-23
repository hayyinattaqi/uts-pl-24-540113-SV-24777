<template>
  <div class="modal-backdrop" @click.self="requestClose">
    <section
      class="medicine-modal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="medicine-form-title"
    >
      <header class="modal-header">
        <div>
          <p class="modal-eyebrow">{{ isEditing ? 'PERBARUI INVENTARIS' : 'INVENTARIS KLINIK' }}</p>
          <h2 id="medicine-form-title">{{ isEditing ? 'Ubah obat' : 'Tambah obat' }}</h2>
          <p class="modal-description">
            Isi informasi obat pada kolom di bawah.
          </p>
        </div>
        <button
          class="close-button"
          type="button"
          aria-label="Tutup formulir"
          :disabled="isSubmitting"
          @click="requestClose"
        >
          ×
        </button>
      </header>

      <form class="medicine-form" @submit.prevent="submitForm">
        <div class="form-field">
          <label for="medicine-code">Kode obat</label>
          <input
            id="medicine-code"
            v-model.trim="form.code"
            type="text"
            maxlength="30"
            autocomplete="off"
            placeholder="Contoh: OBT-001"
            required
            :aria-invalid="Boolean(fieldErrors.code)"
            :aria-describedby="fieldErrors.code ? 'medicine-code-error' : undefined"
            @input="clearFieldError('code')"
          />
          <p v-if="fieldErrors.code" id="medicine-code-error" class="field-error" role="alert">
            {{ fieldErrors.code }}
          </p>
        </div>

        <div class="form-field">
          <label for="medicine-name">Nama obat</label>
          <input
            id="medicine-name"
            v-model.trim="form.name"
            type="text"
            maxlength="150"
            autocomplete="off"
            placeholder="Masukkan nama obat"
            required
            :aria-invalid="Boolean(fieldErrors.name)"
            :aria-describedby="fieldErrors.name ? 'medicine-name-error' : undefined"
            @input="clearFieldError('name')"
          />
          <p v-if="fieldErrors.name" id="medicine-name-error" class="field-error" role="alert">
            {{ fieldErrors.name }}
          </p>
        </div>

        <div class="form-grid">
          <div class="form-field">
            <label for="medicine-unit">Satuan</label>
            <input
              id="medicine-unit"
              v-model.trim="form.unit"
              type="text"
              maxlength="20"
              autocomplete="off"
              placeholder="Contoh: tablet"
              required
              :aria-invalid="Boolean(fieldErrors.unit)"
              :aria-describedby="fieldErrors.unit ? 'medicine-unit-error' : undefined"
              @input="clearFieldError('unit')"
            />
            <p v-if="fieldErrors.unit" id="medicine-unit-error" class="field-error" role="alert">
              {{ fieldErrors.unit }}
            </p>
          </div>

          <div class="form-field">
            <label for="medicine-min-stock">Stok minimum</label>
            <input
              id="medicine-min-stock"
              v-model.number="form.min_stock"
              type="number"
              min="0"
              step="1"
              required
              :aria-invalid="Boolean(fieldErrors.min_stock)"
              :aria-describedby="fieldErrors.min_stock ? 'medicine-min-stock-error' : undefined"
              @input="clearFieldError('min_stock')"
            />
            <p
              v-if="fieldErrors.min_stock"
              id="medicine-min-stock-error"
              class="field-error"
              role="alert"
            >
              {{ fieldErrors.min_stock }}
            </p>
          </div>
        </div>

        <p v-if="generalError" class="form-error" role="alert">{{ generalError }}</p>

        <footer class="modal-actions">
          <button
            class="button button-secondary"
            type="button"
            :disabled="isSubmitting"
            @click="requestClose"
          >
            Batal
          </button>
          <button class="button" type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? 'Menyimpan...' : isEditing ? 'Simpan perubahan' : 'Simpan obat' }}
          </button>
        </footer>
      </form>
    </section>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import http from '../api/http'

const props = defineProps({
  medicine: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'saved'])

const isEditing = computed(() => Boolean(props.medicine))
const isSubmitting = ref(false)
const form = reactive({
  code: '',
  name: '',
  unit: '',
  min_stock: 0,
})
const fieldErrors = reactive({
  code: '',
  name: '',
  unit: '',
  min_stock: '',
})
const generalError = ref('')

watch(
  () => props.medicine,
  (medicine) => {
    form.code = medicine?.code ?? ''
    form.name = medicine?.name ?? ''
    form.unit = medicine?.unit ?? ''
    form.min_stock = medicine?.min_stock ?? 0
    clearErrors()
  },
  { immediate: true },
)

function clearErrors() {
  for (const field of Object.keys(fieldErrors)) {
    fieldErrors[field] = ''
  }
  generalError.value = ''
}

function clearFieldError(field) {
  fieldErrors[field] = ''
  generalError.value = ''
}

function requestClose() {
  if (!isSubmitting.value) {
    emit('close')
  }
}

async function submitForm() {
  if (isSubmitting.value) return

  clearErrors()
  isSubmitting.value = true

  const payload = {
    code: form.code,
    name: form.name,
    unit: form.unit,
    min_stock: Number(form.min_stock),
  }

  try {
    if (isEditing.value) {
      await http.put(`/medicines/${props.medicine.id}`, payload)
      emit('saved', 'Obat berhasil diperbarui.')
    } else {
      await http.post('/medicines', payload)
      emit('saved', 'Obat berhasil ditambahkan.')
    }
  } catch (error) {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors ?? {}
      const fields = Object.keys(fieldErrors)

      for (const [field, messages] of Object.entries(errors)) {
        if (fields.includes(field)) {
          fieldErrors[field] = messages[0]
        } else if (!generalError.value) {
          generalError.value = messages[0]
        }
      }

      if (!Object.keys(errors).length) {
        generalError.value = 'Data obat tidak valid. Periksa kembali isian formulir.'
      }
    } else if (error.response?.status === 404) {
      generalError.value = 'Obat tidak ditemukan. Muat ulang daftar, lalu coba lagi.'
    } else {
      generalError.value = 'Obat gagal disimpan. Periksa koneksi, lalu coba lagi.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  z-index: 30;
  inset: 0;
  display: grid;
  overflow-y: auto;
  align-items: center;
  justify-items: center;
  padding: 1.25rem;
  background: rgb(20 42 49 / 48%);
  backdrop-filter: blur(3px);
}

.medicine-modal {
  width: min(100%, 560px);
  overflow: hidden;
  border: 1px solid #e4ecee;
  border-radius: 1rem;
  background: #fff;
  box-shadow: 0 25px 80px rgb(12 35 43 / 25%);
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.6rem 1.75rem 1.3rem;
  border-bottom: 1px solid #edf1f3;
}

.modal-eyebrow {
  margin: 0 0 0.35rem;
  color: #16866e;
  font-size: 0.7rem;
  font-weight: 750;
  letter-spacing: 0.11em;
}

.modal-header h2 {
  margin: 0;
  color: #193c43;
  font-size: 1.45rem;
  letter-spacing: -0.035em;
}

.modal-description {
  margin: 0.4rem 0 0;
  color: #71858a;
  font-size: 0.88rem;
}

.close-button {
  display: grid;
  width: 2rem;
  height: 2rem;
  flex: 0 0 auto;
  place-items: center;
  border: 0;
  border-radius: 0.5rem;
  background: #f3f7f8;
  color: #5e7479;
  font-size: 1.4rem;
  line-height: 1;
}

.close-button:hover:not(:disabled) {
  background: #e8eff1;
}

.close-button:disabled {
  cursor: wait;
  opacity: 0.55;
}

.medicine-form {
  padding: 1.5rem 1.75rem 1.65rem;
}

.medicine-form .form-field {
  margin-bottom: 1rem;
}

.medicine-form label {
  display: block;
  margin-bottom: 0.42rem;
  color: #314f55;
  font-size: 0.86rem;
  font-weight: 650;
}

.medicine-form input {
  width: 100%;
  min-height: 44px;
  padding: 0.6rem 0.75rem;
  border: 1px solid #d7e3e6;
  border-radius: 0.6rem;
  outline: none;
  background: #fff;
  color: #172b4d;
  transition: border-color 150ms ease, box-shadow 150ms ease;
}

.medicine-form input:focus {
  border-color: #16866e;
  box-shadow: 0 0 0 3px rgb(22 134 110 / 10%);
}

.medicine-form input[aria-invalid="true"] {
  border-color: #c34848;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.field-error,
.form-error {
  color: #b23b3b;
  font-size: 0.82rem;
}

.field-error {
  margin: 0.35rem 0 0;
}

.form-error {
  margin: 0 0 1rem;
  padding: 0.7rem 0.8rem;
  border-radius: 0.55rem;
  background: #fff0ef;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.65rem;
  margin-top: 1.5rem;
}

.modal-actions .button {
  min-width: 112px;
}

@media (max-width: 480px) {
  .modal-header {
    padding-inline: 1.2rem;
  }

  .medicine-form {
    padding: 1.2rem;
  }

  .form-grid {
    grid-template-columns: 1fr;
    gap: 0;
  }

  .modal-actions .button {
    flex: 1;
    min-width: 0;
  }
}
</style>
