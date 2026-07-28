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
          { text: 'Validacion', link: '/modules/validation' },
          { text: 'Catalogos', link: '/modules/catalogs' },
          { text: 'Signer', link: '/modules/signer' },
          { text: 'Certificates', link: '/modules/certificates' },
          { text: 'Sender', link: '/modules/sender' },
          { text: 'RideGenerator', link: '/modules/ride-generator' },
          { text: 'Storage', link: '/modules/storage' },
          { text: 'Pipeline', link: '/modules/pipeline' }
        ]
      },
      {
        text: 'Guias',
        items: [
          { text: 'Workflow completo', link: '/guides/complete-workflow' },
          { text: 'Generar XML por tipo', link: '/guides/xml-by-document-type' },
          { text: 'Ejemplo de factura', link: '/guides/invoice-example' },
          { text: 'Validar antes de enviar', link: '/guides/validate-before-sending' },
          { text: 'RIDE y almacenamiento', link: '/guides/ride-and-storage' },
          { text: 'Consulta y lote', link: '/guides/status-and-batch' },
          { text: 'Pipeline end-to-end', link: '/guides/end-to-end-pipeline' },
          { text: 'Testing', link: '/guides/testing' },
          { text: 'Troubleshooting', link: '/troubleshooting' }
        ]
      },
      {
        text: 'Referencia',
        items: [
          { text: 'Codigos de error', link: '/reference/error-codes' }
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
