/// <reference types="vite/client" />

// Augment vue-router RouteMeta so custom meta fields are type-safe
import 'vue-router'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    guestOnly?: boolean
    role?: string
  }
}
