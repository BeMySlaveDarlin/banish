import {defineConfig} from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
	base: '/admin/',
	plugins: [vue()],
	resolve: {
		alias: {
			'@': path.resolve(__dirname, './src'),
		},
	},
	server: {
		host: '0.0.0.0',
		port: 5173,
		proxy: {
			'/api': {
				target: process.env.VITE_API_URL || 'http://localhost',
				changeOrigin: true,
			},
		},
	},
	build: {
		outDir: 'dist',
		assetsDir: 'assets',
		minify: 'terser',
	},
})
