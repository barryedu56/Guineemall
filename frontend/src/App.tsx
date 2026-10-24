import { BrowserRouter, Route, Routes } from 'react-router-dom'

import Layout from './components/Layout'
import RequireAuth from './components/RequireAuth'
import { AccountPage, FavoritesPage } from './pages/AccountPages'
import { LoginPage, RegisterPage, VendorRegisterPage } from './pages/AuthPages'
import CartPage from './pages/CartPage'
import CheckoutPage from './pages/CheckoutPage'
import HomePage from './pages/HomePage'
import { OrderDetailPage, OrdersPage } from './pages/OrdersPages'
import ProductPage from './pages/ProductPage'
import ProductsPage from './pages/ProductsPage'
import ShopPage from './pages/ShopPage'
import ShopSettingsPage from './pages/vendor/ShopSettingsPage'
import VendorDashboard from './pages/vendor/VendorDashboard'
import VendorLayout from './pages/vendor/VendorLayout'
import VendorOrdersPage from './pages/vendor/VendorOrdersPage'
import VendorProductsPage from './pages/vendor/VendorProductsPage'
import VendorStatutsPage from './pages/vendor/VendorStatutsPage'

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<Layout />}>
          <Route path="/" element={<HomePage />} />
          <Route path="/produits" element={<ProductsPage />} />
          <Route path="/produit/:slug" element={<ProductPage />} />
          <Route path="/boutique/:slug" element={<ShopPage />} />
          <Route path="/panier" element={<CartPage />} />
          <Route path="/connexion" element={<LoginPage />} />
          <Route path="/inscription" element={<RegisterPage />} />
          <Route path="/devenir-vendeur" element={<VendorRegisterPage />} />

          <Route path="/commande" element={<RequireAuth><CheckoutPage /></RequireAuth>} />
          <Route path="/commandes" element={<RequireAuth><OrdersPage /></RequireAuth>} />
          <Route path="/commandes/:id" element={<RequireAuth><OrderDetailPage /></RequireAuth>} />
          <Route path="/compte" element={<RequireAuth><AccountPage /></RequireAuth>} />
          <Route path="/favoris" element={<RequireAuth><FavoritesPage /></RequireAuth>} />

          <Route path="/vendeur" element={<RequireAuth role="vendeur"><VendorLayout /></RequireAuth>}>
            <Route index element={<VendorDashboard />} />
            <Route path="commandes" element={<VendorOrdersPage />} />
            <Route path="produits" element={<VendorProductsPage />} />
            <Route path="statuts" element={<VendorStatutsPage />} />
            <Route path="boutique" element={<ShopSettingsPage />} />
          </Route>

          <Route path="*" element={<p className="py-16 text-center text-lg">Page introuvable.</p>} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}
