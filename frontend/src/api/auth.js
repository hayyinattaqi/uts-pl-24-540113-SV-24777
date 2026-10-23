import http from './http'

const TOKEN_KEY = 'auth_token'

export function getToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token) {
  localStorage.setItem(TOKEN_KEY, token)
}

export function clearToken() {
  localStorage.removeItem(TOKEN_KEY)
}

export function login(email, password) {
  return http.post('/login', { email, password })
}

export async function logout() {
  try {
    return await http.post('/logout')
  } finally {
    clearToken()
  }
}
