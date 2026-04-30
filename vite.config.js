import { defineConfig } from 'vite'

export default defineConfig({
  root: '.',
  publicDir: false, // desativa o publicDir padrão
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        // Entrada principal
        main: 'app/view/layouts/main.html',

        // Páginas
        home:         'app/view/pages/home.html',
        customer:     'app/view/pages/customer.html',
        listCustomer: 'app/view/pages/list-customer.html',

        // JS dos assets
        indexJs:       'public/assets/js/index.php',
        customerJs:    'public/assets/js/pages/customer.js',
        listCustomer:  'public/assets/js/pages/list-customer.js',

        // Componentes
        dataTables:   'public/assets/js/components/data-tables.js',
        findCompany:  'public/assets/js/components/find-company.js',
        requests:     'public/assets/js/components/requests.js',
        validate:     'public/assets/js/components/validate.js',
      }
    }
  },
  server: {
    proxy: {
      '/api': 'http://localhost:8000'
    }
  }
})