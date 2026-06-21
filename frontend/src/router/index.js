import { createRouter, createWebHistory } from 'vue-router'

// 路由守卫
const requireAuth = (to, from, next) => {
  const token = localStorage.getItem('token')
  if (token) {
    next()
  } else {
    next('/login')
  }
}

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/auth/Login.vue'),
  },
  {
    path: '/',
    component: () => import('../components/layout/MainLayout.vue'),
    redirect: '/dashboard',
    beforeEnter: requireAuth,
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('../views/dashboard/Dashboard.vue'),
      },
      {
        path: 'products',
        name: 'Products',
        component: () => import('../views/products/ProductList.vue'),
      },
      {
        path: 'products/create',
        name: 'ProductCreate',
        component: () => import('../views/products/ProductForm.vue'),
      },
      {
        path: 'products/:id/edit',
        name: 'ProductEdit',
        component: () => import('../views/products/ProductForm.vue'),
      },
      {
        path: 'price-trend',
        name: 'PriceTrend',
        component: () => import('../views/products/PriceTrend.vue'),
      },
      {
        path: 'suppliers',
        name: 'Suppliers',
        component: () => import('../views/suppliers/SupplierList.vue'),
      },
      {
        path: 'suppliers/create',
        name: 'SupplierCreate',
        component: () => import('../views/suppliers/SupplierForm.vue'),
      },
      {
        path: 'suppliers/:id/edit',
        name: 'SupplierEdit',
        component: () => import('../views/suppliers/SupplierForm.vue'),
      },
      {
        path: 'tags',
        name: 'Tags',
        component: () => import('../views/tags/TagList.vue'),
      },
      {
        path: 'orders',
        name: 'Orders',
        component: () => import('../views/orders/OrderList.vue'),
      },
      {
        path: 'orders/create',
        name: 'OrderCreate',
        component: () => import('../views/orders/OrderForm.vue'),
      },
      {
        path: 'orders/:id',
        name: 'OrderDetail',
        component: () => import('../views/orders/OrderDetail.vue'),
      },
      {
        path: 'inventory',
        name: 'Inventory',
        component: () => import('../views/inventory/InventoryList.vue'),
      },
      {
        path: 'coupons',
        name: 'Coupons',
        component: () => import('../views/coupons/CouponList.vue'),
      },
      {
        path: 'coupons/create',
        name: 'CouponCreate',
        component: () => import('../views/coupons/CouponForm.vue'),
      },
      {
        path: 'coupons/:id/edit',
        name: 'CouponEdit',
        component: () => import('../views/coupons/CouponForm.vue'),
      },
      {
        path: 'alerts',
        name: 'Alerts',
        component: () => import('../views/alerts/AlertList.vue'),
      },
      {
        path: 'reviews',
        name: 'Reviews',
        component: () => import('../views/reviews/ReviewList.vue'),
      },
      {
        path: 'returns',
        name: 'Returns',
        component: () => import('../views/returns/ReturnList.vue'),
      },
      {
        path: 'returns/:id',
        name: 'ReturnDetail',
        component: () => import('../views/returns/ReturnDetail.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
