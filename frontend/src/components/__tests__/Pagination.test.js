import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Pagination from '@/components/Pagination.vue'

describe('Pagination', () => {
  it('renders nothing when totalPages is 1', () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 0, totalPages: 1 },
    })
    expect(wrapper.find('.pagination').exists()).toBe(false)
  })

  it('renders pagination when totalPages > 1', () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 0, totalPages: 3 },
    })
    expect(wrapper.find('.pagination').exists()).toBe(true)
  })

  it('displays correct page info', () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 1, totalPages: 5, totalItems: 50 },
    })
    const info = wrapper.find('.pagination-info').text()
    expect(info).toContain('Page 2 of 5')
    expect(info).toContain('50 total')
  })

  it('does not show total items when totalItems is 0', () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 0, totalPages: 3, totalItems: 0 },
    })
    const info = wrapper.find('.pagination-info').text()
    expect(info).not.toContain('total')
  })

  it('disables previous button on first page', () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 0, totalPages: 3, hasMore: true },
    })
    const buttons = wrapper.findAll('button')
    expect(buttons[0].attributes('disabled')).toBeDefined()
  })

  it('enables previous button on non-first page', () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 1, totalPages: 3, hasMore: true },
    })
    const buttons = wrapper.findAll('button')
    expect(buttons[0].attributes('disabled')).toBeUndefined()
  })

  it('disables next button when hasMore is false', () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 1, totalPages: 3, hasMore: false },
    })
    const buttons = wrapper.findAll('button')
    expect(buttons[1].attributes('disabled')).toBeDefined()
  })

  it('enables next button when hasMore is true', () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 0, totalPages: 3, hasMore: true },
    })
    const buttons = wrapper.findAll('button')
    expect(buttons[1].attributes('disabled')).toBeUndefined()
  })

  it('emits prev event when previous button is clicked', async () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 1, totalPages: 3, hasMore: true },
    })
    await wrapper.findAll('button')[0].trigger('click')
    expect(wrapper.emitted('prev')).toHaveLength(1)
  })

  it('emits next event when next button is clicked', async () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 0, totalPages: 3, hasMore: true },
    })
    await wrapper.findAll('button')[1].trigger('click')
    expect(wrapper.emitted('next')).toHaveLength(1)
  })
})
