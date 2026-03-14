import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

vi.mock('@/utils/logger', () => ({
  default: {
    log: vi.fn(),
    warn: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
  },
}))

describe('api interceptors', () => {
  let api
  const originalLocation = window.location

  beforeEach(async () => {
    vi.resetModules()
    delete window.location
    window.location = { href: '' }
    const mod = await import('@/utils/api')
    api = mod.default
  })

  afterEach(() => {
    window.location = originalLocation
  })

  it('skips redirect for 401 on auth endpoints', async () => {
    const interceptor = api.interceptors.response.handlers[0]
    const error = {
      response: { status: 401 },
      config: { url: '/api/admin/auth/validate/' },
      message: 'Unauthorized',
    }

    await expect(interceptor.rejected(error)).rejects.toEqual(error)
    expect(window.location.href).toBe('')
  })

  it('redirects to /admin/auth/expired for 401 on non-auth endpoints', async () => {
    const interceptor = api.interceptors.response.handlers[0]
    const error = {
      response: { status: 401 },
      config: { url: '/api/admin/chats' },
      message: 'Unauthorized',
    }

    await expect(interceptor.rejected(error)).rejects.toEqual(error)
    expect(window.location.href).toBe('/admin/auth/expired')
  })

  it('does not redirect for non-401 errors', async () => {
    const interceptor = api.interceptors.response.handlers[0]
    const error = {
      response: { status: 500 },
      config: { url: '/api/admin/chats' },
      message: 'Server Error',
    }

    await expect(interceptor.rejected(error)).rejects.toEqual(error)
    expect(window.location.href).toBe('')
  })
})
