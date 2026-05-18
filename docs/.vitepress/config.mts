import { defineConfig } from 'vitepress'

export default defineConfig({
  lang: 'es-EC',
  title: 'SRI Toolkit',
  description: 'Toolkit PHP para comprobantes electronicos del SRI Ecuador.',
  cleanUrls: true,
  lastUpdated: true,
  markdown: {
    lineNumbers: true
  },
  themeConfig: {
    siteTitle: 'SRI Toolkit',
    nav: [
      { text: 'Guia', link: '/getting-started' },
      { text: 'Modulos', link: '/modules/access-key-generator' },
      { text: 'GitHub', link: 'https://github.com/Sebas-DV/sri-toolkit' }
    ],
    sidebar: [
      {
        text: 'Inicio',
        items: [
          { text: 'Introduccion', link: '/' },
          { text: 'Instalacion', link: '/installation' },
          { text: 'Primeros pasos', link: '/getting-started' }
        ]
      },
      {
        text: 'Modulos',
        items: [
          { text: 'AccessKeyGenerator', link: '/modules/access-key-generator' },
          { text: 'XMLMaker', link: '/modules/xml-maker' },
          { text: 'Signer', link: '/modules/signer' },
          { text: 'Sender', link: '/modules/sender' }
        ]
      },
      {
        text: 'Guias',
        items: [
          { text: 'Workflow completo', link: '/guides/complete-workflow' },
          { text: 'Ejemplo de factura', link: '/guides/invoice-example' },
          { text: 'Testing', link: '/guides/testing' },
          { text: 'Troubleshooting', link: '/troubleshooting' }
        ]
      }
    ],
    search: {
      provider: 'local'
    },
    outline: {
      level: [2, 3],
      label: 'En esta pagina'
    },
    docFooter: {
      prev: 'Anterior',
      next: 'Siguiente'
    },
    lastUpdated: {
      text: 'Actualizado'
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/Sebas-DV/sri-toolkit' }
    ],
    footer: {
      message: 'Publicado bajo licencia MIT.',
      copyright: 'Copyright (c) Matiz Studio Creative'
    }
  }
})
