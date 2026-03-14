import { describe, it, expect } from 'vitest'
import {
  formatDate,
  formatDateTime,
  getBanStatusClass,
  getActionColor,
  formatAction,
  getApiErrorMessage,
} from '@/utils/formatters'

describe('formatDate', () => {
  it('returns dash for falsy input', () => {
    expect(formatDate(null)).toBe('-')
    expect(formatDate(undefined)).toBe('-')
    expect(formatDate('')).toBe('-')
  })

  it('formats a valid date string', () => {
    const result = formatDate('2024-01-15T10:30:00Z')
    expect(result).toBeTruthy()
    expect(result).not.toBe('-')
  })
})

describe('formatDateTime', () => {
  it('returns dash for falsy input', () => {
    expect(formatDateTime(null)).toBe('-')
    expect(formatDateTime(undefined)).toBe('-')
    expect(formatDateTime('')).toBe('-')
  })

  it('formats a valid date-time string with both date and time parts', () => {
    const result = formatDateTime('2024-01-15T10:30:00Z')
    expect(result).toBeTruthy()
    expect(result).not.toBe('-')
    expect(result).toContain(' ')
  })
})

describe('getBanStatusClass', () => {
  it('returns danger for active status', () => {
    expect(getBanStatusClass('active')).toBe('danger')
  })

  it('returns secondary for any other status', () => {
    expect(getBanStatusClass('resolved')).toBe('secondary')
    expect(getBanStatusClass('expired')).toBe('secondary')
    expect(getBanStatusClass('')).toBe('secondary')
  })
})

describe('getActionColor', () => {
  it('returns correct color for known actions', () => {
    expect(getActionColor('auth_login')).toBe('success')
    expect(getActionColor('auth_logout')).toBe('secondary')
    expect(getActionColor('config_update')).toBe('info')
    expect(getActionColor('unban_user')).toBe('success')
    expect(getActionColor('user_list_view')).toBe('primary')
    expect(getActionColor('user_details_view')).toBe('primary')
  })

  it('returns secondary for unknown actions', () => {
    expect(getActionColor('unknown_action')).toBe('secondary')
    expect(getActionColor('')).toBe('secondary')
  })
})

describe('formatAction', () => {
  it('returns human-readable label for known actions', () => {
    expect(formatAction('auth_login')).toBe('Login')
    expect(formatAction('auth_logout')).toBe('Logout')
    expect(formatAction('config_update')).toBe('Config Update')
    expect(formatAction('unban_user')).toBe('Unban')
  })

  it('returns raw action string for unknown actions', () => {
    expect(formatAction('custom_thing')).toBe('custom_thing')
  })
})

describe('getApiErrorMessage', () => {
  it('extracts error from response.data.error', () => {
    const err = { response: { data: { error: 'Bad request' } }, message: 'fallback' }
    expect(getApiErrorMessage(err)).toBe('Bad request')
  })

  it('falls back to response.data.message', () => {
    const err = { response: { data: { message: 'Not found' } }, message: 'fallback' }
    expect(getApiErrorMessage(err)).toBe('Not found')
  })

  it('falls back to error.message when no response data', () => {
    const err = { message: 'Network Error' }
    expect(getApiErrorMessage(err)).toBe('Network Error')
  })

  it('prefers response.data.error over response.data.message', () => {
    const err = {
      response: { data: { error: 'First', message: 'Second' } },
      message: 'Third',
    }
    expect(getApiErrorMessage(err)).toBe('First')
  })
})
