import { createApp } from 'vue'
import './style.css'
import InternalDashboard from './InternalDashboard.vue'


const appElement = document.getElementById('internalDashboard');

if (appElement) {
  const pageData = JSON.parse(appElement.getAttribute('data-page') || '{}');
  createApp(InternalDashboard, { pageData: pageData }).mount('#internalDashboard');
}
