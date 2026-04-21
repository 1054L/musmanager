import { createRouter, createWebHistory } from 'vue-router'
import i18n from '../i18n'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('../views/HomeView.vue'),
    meta: { titleKey: 'seo.home_title', descKey: 'seo.home_desc' }
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue')
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/RegisterView.vue')
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/tournament/create',
    name: 'CreateTournament',
    component: () => import('../views/CreateTournamentView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/tournament/:uuid/edit',
    name: 'EditTournament',
    component: () => import('../views/EditTournamentView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/player/create',
    name: 'CreatePlayer',
    component: () => import('../views/CreatePlayerView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/team/create',
    name: 'CreateTeam',
    component: () => import('../views/CreateTeamView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/tournament/:uuid',
    name: 'Tournament',
    component: () => import('../views/TournamentView.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/features',
    name: 'Features',
    component: () => import('../views/FeaturesView.vue'),
    meta: { titleKey: 'seo.features_title', descKey: 'seo.features_desc' }
  },
  {
    path: '/tournaments',
    name: 'Tournaments',
    component: () => import('../views/TournamentsView.vue'),
    meta: { titleKey: 'seo.tournaments_title', descKey: 'seo.tournaments_desc' }
  },
  {
    path: '/about',
    name: 'About',
    component: () => import('../views/AboutView.vue'),
    meta: { titleKey: 'seo.about_title', descKey: 'seo.home_desc' }
  },
  {
    path: '/privacy',
    name: 'Privacy',
    component: () => import('../views/PrivacyPolicyView.vue'),
    meta: { titleKey: 'seo.privacy_title', descKey: 'legal.privacy_p1' }
  },
  {
    path: '/terms',
    name: 'Terms',
    component: () => import('../views/TermsView.vue'),
    meta: { titleKey: 'seo.terms_title', descKey: 'legal.terms_p1' }
  },
  {
    path: '/cookies',
    name: 'Cookies',
    component: () => import('../views/CookiesView.vue'),
    meta: { titleKey: 'legal.cookies_title', descKey: 'legal.cookies_p1' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const loggedIn = localStorage.getItem('user')
  if (to.matched.some(record => record.meta.requiresAuth) && !loggedIn) {
    next('/login')
  } else {
    next()
  }
})

router.afterEach((to) => {
  const { titleKey, descKey } = to.meta
  const t = i18n.global.t
  
  if (titleKey) {
    document.title = t(titleKey)
  }

  const descriptionTag = document.querySelector('meta[name="description"]')
  if (descriptionTag && descKey) {
    descriptionTag.setAttribute('content', t(descKey))
  }
})

export default router
