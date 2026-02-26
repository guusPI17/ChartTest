import { createApp } from 'vue'
import { createVuetify } from './plugins/vuetify'
import App from './App.vue'

const app = createApp(App)
app.use(createVuetify())
app.mount('#app')
