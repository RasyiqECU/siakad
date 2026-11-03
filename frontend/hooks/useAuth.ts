'use client'
import { useQuery } from '@tanstack/react-query'
import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { fetchMe } from '@/services/api/auth'
import { clearAuth } from '@/lib/auth'
import type { AuthUser } from '@/types/api'

/**
 * Hook sesi: memanggil /auth/me dengan bearer token dan status yang jelas.
 * dipakai di (admin) layout & komponen profil.
 */
export function useAuth() {
  const router = useRouter()

  const query = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: fetchMe,
    retry: false,
    staleTime: 60_000,
  })

  // 401 = token invalid; 401 dari interceptor sudah membersihkan sesi.
  useEffect(() => {
    const status = (query.error as { response?: { status?: number } })?.response?.status
    if (status === 401) {
      clearAuth()
      router.replace('/login')
    }
  }, [query.error, router])

  return {
    user: (query.data ?? null) as AuthUser | null,
    isLoading: query.isLoading,
    isError: query.isError,
    isAuthenticated: Boolean(query.data) && !query.isError,
  }
}
