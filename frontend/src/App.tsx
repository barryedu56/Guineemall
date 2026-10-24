import { BrowserRouter, Route, Routes } from 'react-router-dom'

import Layout from './components/Layout'
import RequireAuth from './components/RequireAuth'
import { AccountPage, FavoritesPage } from './pages/AccountPages'
import { ForgotPasswordPage, LoginPage, RegisterPage, ResetPasswordPage, VendorRegisterPage } from './pages/AuthPages'
import CartPage from './pages/CartPage'
import ChatPage from './pages/chat/ChatPage'
import CheckoutPage from './pages/CheckoutPage'
import HomePage from './pages/HomePage'
import { LivePage, LivesPage } from './pages/LivePages'
import { OrderDetailPage, OrdersPage } from './pages/OrdersPages'
import PaymentSimulationPage from './pages/PaymentSimulationPage'
import ProductPage from './pages/ProductPage'
import ProductsPage from './pages/ProductsPage'
import { CampaignPage, ReferralPage } from './pages/PromoPages'
import { RiderDashboard, RiderRegisterPage } from './pages/RiderPages'
import ShopPage from './pages/ShopPage'
import ShopSettingsPage from './pages/vendor/ShopSettingsPage'
import VendorDashboard from './pages/vendor/VendorDashboard'
import VendorLayout from './pages/vendor/VendorLayout'
import { VendorCouponsPage, VendorLivesPage, VendorReviewsPage } from './pages/vendor/VendorMarketingPages'
import VendorOrdersPage from './pages/vendor/VendorOrdersPage'
import VendorProductsPage from './pages/vendor/VendorProductsPage'
import VendorStatutsPage from './pages/vendor/VendorStatutsPage'
import NotFound from './pages/NotFound'
import WalletPage from './pages/WalletPage'

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<Layout />}>
          <Route path="/" element={<HomePage />} />
          <Route path="/produits" element={<ProductsPage />} />
          <Route path="/produit/:slug" element={<ProductPage />} />
          <Route path="/boutique/:slug" element={<ShopPage />} />
          <Route path="/promo/:slug" element={<CampaignPage />} />
          <Route path="/lives" element={<LivesPage />} />
          <Route path="/lives/:id" element={<LivePage />} />
          <Route path="/panier" element={<CartPage />} />
          <Route path="/connexion" element={<LoginPage />} />
          <Route path="/inscription" element={<RegisterPage />} />
          <Route path="/mot-de-passe/oublie" element={<ForgotPasswordPage />} />
          <Route path="/mot-de-passe/nouveau" element={<ResetPasswordPage />} />
          <Route path="/devenir-vendeur" element={<VendorRegisterPage />} />
          <Route path="/devenir-livreur" element={<RiderRegisterPage />} />

          <Route path="/commande" element={<RequireAuth><CheckoutPage /></RequireAuth>} />
          <Route path="/paiement/simulation/:reference" element={<RequireAuth><PaymentSimulationPage /></RequireAuth>} />
          <Route path="/commandes" element={<RequireAuth><OrdersPage /></RequireAuth>} />
          <Route path="/commandes/:id" element={<RequireAuth><OrderDetailPage /></RequireAuth>} />
          <Route path="/messages" element={<RequireAuth><ChatPage base="/messages" /></RequireAuth>} />
          <Route path="/messages/:id" element={<RequireAuth><ChatPage base="/messages" /></RequireAuth>} />
          <Route path="/compte" element={<RequireAuth><AccountPage /></RequireAuth>} />
          <Route path="/favoris" element={<RequireAuth><FavoritesPage /></RequireAuth>} />
          <Route path="/parrainage" element={<RequireAuth><ReferralPage /></RequireAuth>} />
          <Route path="/portefeuille" element={<RequireAuth><WalletPage /></RequireAuth>} />
          <Route path="/livreur" element={<RequireAuth role="livreur"><RiderDashboard /></RequireAuth>} />

          <Route path="/vendeur" element={<RequireAuth role="vendeur"><VendorLayout /></RequireAuth>}>
            <Route index element={<VendorDashboard />} />
            <Route path="commandes" element={<VendorOrdersPage />} />
            <Route path="produits" element={<VendorProductsPage />} />
            <Route path="messages" element={<ChatPage base="/vendeur/messages" />} />
            <Route path="messages/:id" element={<ChatPage base="/vendeur/messages" />} />
            <Route path="statuts" element={<VendorStatutsPage />} />
            <Route path="promos" element={<VendorCouponsPage />} />
            <Route path="lives" element={<VendorLivesPage />} />
            <Route path="avis" element={<VendorReviewsPage />} />
            <Route path="portefeuille" element={<WalletPage />} />
            <Route path="boutique" element={<ShopSettingsPage />} />
          </Route>

          <Route path="*" element={<NotFound />} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}
