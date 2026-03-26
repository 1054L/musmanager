import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('../views/HomeView.vue')
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
    component: () => import('../views/FeaturesView.vue')
  },
  {
    path: '/tournaments',
    name: 'Tournaments',
    component: () => import('../views/TournamentsView.vue')
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

export default router
