import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ErrorAlert from '@/components/ErrorAlert.vue'

describe('ErrorAlert', () => {
  it('renders nothing when message is empty', () => {
    const wrapper = mount(ErrorAlert, {
      props: { message: '' },
    })
    expect(wrapper.find('.alert-danger').exists()).toBe(false)
  })

  it('renders the error message', () => {
    const wrapper = mount(ErrorAlert, {
      props: { message: 'Something went wrong' },
    })
    expect(wrapper.find('.alert-danger').exists()).toBe(true)
    expect(wrapper.find('.error-text').text()).toBe('Something went wrong')
  })

  it('shows dismiss button by default', () => {
    const wrapper = mount(ErrorAlert, {
      props: { message: 'Error' },
    })
    expect(wrapper.find('.error-dismiss').exists()).toBe(true)
  })

  it('hides dismiss button when dismissible is false', () => {
    const wrapper = mount(ErrorAlert, {
      props: { message: 'Error', dismissible: false },
    })
    expect(wrapper.find('.error-dismiss').exists()).toBe(false)
  })

  it('emits dismiss event when dismiss button is clicked', async () => {
    const wrapper = mount(ErrorAlert, {
      props: { message: 'Error' },
    })
    await wrapper.find('.error-dismiss').trigger('click')
    expect(wrapper.emitted('dismiss')).toHaveLength(1)
  })
})
