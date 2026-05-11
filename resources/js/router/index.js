import { createRouter, createWebHistory } from 'vue-router'
import SetupView from '../views/SetupView.vue'
import LeagueView from '../components/LeagueView.vue'
import BulkView from '../views/BulkView.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', component: SetupView },
        { path: '/league', component: LeagueView },
        { path: '/bulk', component: BulkView },
    ],
})

export default router
