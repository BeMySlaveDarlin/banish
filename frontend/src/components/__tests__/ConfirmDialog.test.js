import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

describe('ConfirmDialog', () => {
  const mountDialog = (props = {}) => {
    return mount(ConfirmDialog, {
      props: { visible: true, ...props },
      global: {
        stubs: {
          Teleport: true,
        },
      },
    })
  }

  it('renders nothing when visible is false', () => {
    const wrapper = mount(ConfirmDialog, {
      props: { visible: false },
      global: { stubs: { Teleport: true } },
    })
    expect(wrapper.find('.dialog-overlay').exists()).toBe(false)
  })

  it('renders dialog when visible is true', () => {
    const wrapper = mountDialog()
    expect(wrapper.find('.dialog-overlay').exists()).toBe(true)
    expect(wrapper.find('.dialog-card').exists()).toBe(true)
  })

  it('displays default title and message', () => {
    const wrapper = mountDialog()
    expect(wrapper.find('.dialog-header h3').text()).toBe('Confirm action')
    expect(wrapper.find('.dialog-body p').text()).toBe('Are you sure?')
  })

  it('displays custom title and message', () => {
    const wrapper = mountDialog({
      title: 'Delete user',
      message: 'This cannot be undone',
    })
    expect(wrapper.find('.dialog-header h3').text()).toBe('Delete user')
    expect(wrapper.find('.dialog-body p').text()).toBe('This cannot be undone')
  })

  it('displays custom confirm button text', () => {
    const wrapper = mountDialog({ confirmText: 'Delete' })
    const buttons = wrapper.findAll('button')
    const confirmBtn = buttons[buttons.length - 1]
    expect(confirmBtn.text()).toBe('Delete')
  })

  it('applies confirm variant class to confirm button', () => {
    const wrapper = mountDialog({ confirmVariant: 'warning' })
    const buttons = wrapper.findAll('button')
    const confirmBtn = buttons[buttons.length - 1]
    expect(confirmBtn.classes()).toContain('btn-warning')
  })

  it('emits confirm event when confirm button is clicked', async () => {
    const wrapper = mountDialog()
    const buttons = wrapper.findAll('button')
    const confirmBtn = buttons[buttons.length - 1]
    await confirmBtn.trigger('click')
    expect(wrapper.emitted('confirm')).toHaveLength(1)
  })

  it('emits cancel event when cancel button is clicked', async () => {
    const wrapper = mountDialog()
    const cancelBtn = wrapper.findAll('button')[0]
    expect(cancelBtn.text()).toBe('Cancel')
    await cancelBtn.trigger('click')
    expect(wrapper.emitted('cancel')).toHaveLength(1)
  })

  it('emits cancel event when overlay is clicked', async () => {
    const wrapper = mountDialog()
    await wrapper.find('.dialog-overlay').trigger('click')
    expect(wrapper.emitted('cancel')).toHaveLength(1)
  })
})
