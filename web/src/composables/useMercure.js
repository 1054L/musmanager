import { onUnmounted } from 'vue'

export function useMercure() {
  let eventSource = null

  const subscribe = (topic, callback) => {
    const hubUrl = new URL(import.meta.env.VITE_MERCURE_URL || 'http://localhost:8081/.well-known/mercure')
    hubUrl.searchParams.append('topic', topic)

    console.log(`Mercure: Connecting to ${hubUrl.href}`)
    eventSource = new EventSource(hubUrl)
    
    eventSource.onmessage = event => {
      console.log('Mercure: Received event', event.data)
      try {
        const data = JSON.parse(event.data)
        callback(data)
      } catch (e) {
        console.error('Error parsing Mercure data:', e)
      }
    }

    eventSource.onerror = err => {
      console.error('Mercure EventSource connection error:', err)
    }
  }

  onUnmounted(() => {
    if (eventSource) {
      eventSource.close()
    }
  })

  return {
    subscribe
  }
}
