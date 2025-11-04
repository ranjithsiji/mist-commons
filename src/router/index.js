import { createRouter, createWebHistory } from 'vue-router';
import CoverPage from '../components/CoverPage.vue';
import About from '../pages/About.vue';

const routes = [
  { path: '/', name: 'home', component: CoverPage },
  { path: '/about', name: 'about', component: About },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 }; }
});

export default router;
