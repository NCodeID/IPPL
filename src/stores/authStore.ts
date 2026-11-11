import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

interface User {
  id: number
  name: string
  email: string
  role: string
}

interface LoginCredentials {
  email: string
  password: string
}

interface LoginResponse {
  access_token: string
  user: User
}

export const useAuthStore = defineStore('auth', () => {
  // ---------------------------------------------------------------------------
  // State
  // ---------------------------------------------------------------------------
  const token = ref<string | null>(localStorage.getItem('token'))

  const user = ref<User | null>(
    (() => {
      const stored = localStorage.getItem('user')
      if (stored) {
        try {
          return JSON.parse(stored) as User
        } catch {
          return null
        }
      }
      return null
    })(),
  )

  // ---------------------------------------------------------------------------
  // Computed / Getters
  // ---------------------------------------------------------------------------
  const isAuthenticated = computed<boolean>(() => !!token.value)
  const userRole = computed<string>(() => user.value?.role ?? '')

  // ---------------------------------------------------------------------------
  // Actions
  // ---------------------------------------------------------------------------
  async function login(credentials: LoginCredentials): Promise<void> {
    const { data } = await api.post<LoginResponse>('/login', credentials)

    // The API may return the token as `access_token` or `token`
    const receivedToken = data.access_token

    token.value = receivedToken
    user.value = data.user

    localStorage.setItem('token', receivedToken)
    localStorage.setItem('user', JSON.stringify(data.user))
  }

  function logout(): void {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  return {
    token,
    user,
    isAuthenticated,
    userRole,
    login,
    logout,
  }
})

