import { ref, onMounted } from 'vue';

const THEME_KEY = 'mist-commons-theme';
const prefersDark = () => window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

export function useTheme() {
  const isDark = ref(false);

  const applyTheme = (dark) => {
    const root = document.documentElement;
    root.classList.toggle('dark', dark);
    isDark.value = dark;
    try { localStorage.setItem(THEME_KEY, dark ? 'dark' : 'light'); } catch {}
  };

  const toggleTheme = () => applyTheme(!isDark.value);

  onMounted(() => {
    try {
      const saved = localStorage.getItem(THEME_KEY);
      if (saved === 'dark' || saved === 'light') {
        applyTheme(saved === 'dark');
      } else {
        applyTheme(prefersDark());
      }
    } catch {
      applyTheme(prefersDark());
    }
  });

  return { isDark, toggleTheme, applyTheme };
}
