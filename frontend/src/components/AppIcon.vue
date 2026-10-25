<template>
  <svg
    class="icon"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
  >
    <path v-for="(d, index) in paths[name]" :key="index" :d="d" />
  </svg>
</template>

<script setup>
defineProps({
  name: { type: String, required: true },
})

// Ikon digambar sendiri dengan goresan yang sama, tanpa library ikon.
const paths = {
  plus: ['M12 5v14', 'M5 12h14'],
  search: ['M10.5 4a6.5 6.5 0 1 1 0 13 6.5 6.5 0 0 1 0-13z', 'M20 20l-4.6-4.6'],
  edit: ['M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4z', 'M13.5 6.5l4 4'],
  trash: ['M4 7h16', 'M9 7V4h6v3', 'M6.5 7l1 13h9l1-13', 'M10 11v5', 'M14 11v5'],
  close: ['M6 6l12 12', 'M18 6L6 18'],
  check: ['M5 12.5l4.5 4.5L19 7.5'],
  alert: ['M12 4l9 16H3z', 'M12 10v4', 'M12 17.2v.1'],
  logout: ['M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4', 'M10 16l-4-4 4-4', 'M6 12h10'],
  in: ['M12 4v11', 'M7.5 10.5L12 15l4.5-4.5', 'M5 20h14'],
  out: ['M12 15V4', 'M7.5 8.5L12 4l4.5 4.5', 'M5 20h14'],
}
</script>
