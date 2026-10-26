// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  css: ['~/assets/css/main.css'],
  app: {
    head: {
      title: 'CelloTrav - Platform Reservasi Hotel & Tiket Pesawat Nusantara',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'CelloTrav: Platform reservasi hotel berbintang dan penerbangan nusantara dengan tarif transparan dan terpercaya.' }
      ]
    }
  }
})
