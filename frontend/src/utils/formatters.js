export const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString()
}

export const formatDateTime = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString()
}

export const getBanStatusClass = (status) => {
  return status === 'active' ? 'danger' : 'secondary'
}

export const getActionColor = (action) => {
  const colors = {
    auth_login: 'success',
    auth_logout: 'secondary',
    config_update: 'info',
    unban_user: 'success',
    user_list_view: 'primary',
    user_details_view: 'primary',
  }
  return colors[action] || 'secondary'
}

export const formatAction = (action) => {
  const actions = {
    auth_login: 'Login',
    auth_logout: 'Logout',
    config_update: 'Config Update',
    user_list_view: 'View Users',
    user_details_view: 'View User',
    unban_user: 'Unban',
  }
  return actions[action] || action
}

export const getApiErrorMessage = (error) => {
  return error.response?.data?.error || error.response?.data?.message || error.message
}
