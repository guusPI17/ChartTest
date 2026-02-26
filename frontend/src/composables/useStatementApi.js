import { ref } from 'vue'

export function useStatementApi() {
  const data = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function parseStatement(file) {
    loading.value = true
    error.value = null
    data.value = null

    const formData = new FormData()
    formData.append('file', file)

    try {
      const response = await fetch('/api/parse', {
        method: 'POST',
        body: formData,
      })

      let json
      try {
        json = await response.json()
      } catch {
        error.value = `Ошибка сервера (${response.status})`
        return
      }

      if (!response.ok) {
        error.value = (json && typeof json === 'object' && json.error) || `Ошибка сервера (${response.status})`
        return
      }

      data.value = json
    } catch (e) {
      error.value = 'Ошибка сети. Проверьте подключение и попробуйте снова.'
    } finally {
      loading.value = false
    }
  }

  function reset() {
    data.value = null
    error.value = null
    loading.value = false
  }

  return { data, loading, error, parseStatement, reset }
}
