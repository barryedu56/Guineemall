// Types des réponses de l'API Django

export interface Paginated<T> {
  count: number
  total_pages: number
  page: number
  next: string | null
  previous: string | null
  results: T[]
}

export type Role = 'admin' | 'vendeur' | 'client' | 'livreur'

export interface User {
  id: number
  email: string
  name: string
  phone: string
  role: Role
  avatar: string | null
  referral_code: string
  has_2fa: boolean
  shop: { id: number; slug: string; shop_name: string; status: VendorStatus } | null
}

export type VendorStatus = 'pending' | 'approved' | 'suspended'

export interface Shop {
  id: number
  slug: string
  shop_name: string
  description: string
  logo: string | null
  whatsapp: string
  is_verified: boolean
  is_premium?: boolean
  rating?: number | null
  reviews_count?: number
  products_count?: number
  created_at: string
}

export interface MyShop extends Shop {
  status: VendorStatus
}

export interface Category {
  id: number
  name: string
  slug: string
  description: string
  image: string | null
  parent: number | null
  products_count: number
}

export interface ProductImage {
  id: number
  image: string
  position: number
}

export interface ProductSummary {
  id: number
  slug: string
  name: string
  price: number
  compare_at_price: number | null
  stock: number
  image: string | null
  vendor: string
  category: string
  rating: number | null
  reviews_count: number
}

export interface ProductDetail extends Omit<ProductSummary, 'vendor' | 'category'> {
  description: string
  attributes: Record<string, string>
  images: ProductImage[]
  vendor: Shop
  category: Category
}

export interface VendorProduct {
  id: number
  slug: string
  name: string
  description: string
  price: number
  compare_at_price: number | null
  stock: number
  attributes: Record<string, string>
  status: 'active' | 'inactive'
  category: string
  images: ProductImage[]
}

export interface Address {
  id: number
  label: string
  city: string
  commune: string
  quartier: string
  landmark: string
  latitude: string | null
  longitude: string | null
  contact_phone: string
  is_default: boolean
}

export interface CartLine {
  id: number
  product: ProductSummary
  quantity: number
  subtotal: number
}

export type PaymentMethod = 'cash_on_delivery' | 'orange_money' | 'mtn_momo' | 'card'
export type OrderStatus = 'pending' | 'processing' | 'delivered' | 'cancelled'
export type VendorOrderStatus = 'pending' | 'confirmed' | 'preparing' | 'ready' | 'delivered' | 'cancelled'

export interface OrderItem {
  id: number
  product_slug: string | null
  product_name: string
  unit_price: number
  quantity: number
  subtotal: number
}

export interface Tracking {
  delivery_id: number
  rider_rating?: number | null
  my_rating?: number | null
  status: string
  status_label: string
  rider_name: string | null
  rider_phone: string | null
  latitude: string | null
  longitude: string | null
}

export interface Order {
  id: number
  subtotal: number
  delivery_fee: number
  discount_amount: number
  total_amount: number
  coupon_code: string | null
  status: OrderStatus
  status_label: string
  payment_method: PaymentMethod
  payment_method_label: string
  payment_status: string
  payment_status_label: string
  awaiting_payment: boolean
  can_confirm_reception: boolean
  delivery_method: 'home' | 'pickup'
  pickup_point_name: string | null
  delivery_name: string
  delivery_city: string
  delivery_commune: string
  delivery_quartier: string
  delivery_landmark: string
  delivery_phone: string
  vendor_orders: {
    id: number
    shop_name: string
    shop_slug: string
    subtotal: number
    delivery_fee: number
    total_amount: number
    status: VendorOrderStatus
    status_label: string
    received_at: string | null
    items: OrderItem[]
    tracking: Tracking | null
    delivery_code: string | null
  }[]
  status_history: { status: string; note: string; created_at: string }[]
  created_at: string
}

export interface VendorSideOrder {
  id: number
  order_id: number
  client_name: string
  subtotal: number
  delivery_fee: number
  vendor_discount: number
  total_amount: number
  net_amount: number
  status: VendorOrderStatus
  status_label: string
  payment_method: PaymentMethod
  payment_method_label: string
  payment_status: string
  delivery_method: 'home' | 'pickup'
  items: OrderItem[]
  tracking: Tracking | null
  received_at: string | null
  funds_released_at: string | null
  delivery: {
    name: string
    city: string
    commune: string
    quartier: string
    landmark: string
    latitude: string | null
    longitude: string | null
    phone: string
  }
  next_statuses: VendorOrderStatus[]
  created_at: string
}

export interface Statut {
  id: number
  media: string
  media_type: 'image' | 'video'
  caption: string
  product: { slug: string; name: string; price: number } | null
  seen: boolean
  created_at: string
  expires_at: string
}

export interface StatutGroup {
  vendor: { slug: string; shop_name: string; logo: string | null; is_verified: boolean }
  all_seen: boolean
  statuts: Statut[]
}

export interface VendorStatut {
  id: number
  media: string
  media_type: 'image' | 'video'
  caption: string
  product_slug: string | null
  views_count: number
  is_active: boolean
  created_at: string
  expires_at: string
}

export interface Choice {
  value: string
  label: string
}

export interface SiteConfig {
  site: {
    name: string
    tagline: string
    announcement: string
    hero_title: string
    hero_subtitle: string
    hero_image: string | null
    contact_phone: string
    contact_whatsapp: string
    contact_email: string
    address: string
    social: Partial<Record<'facebook' | 'instagram' | 'tiktok', string>>
  }
  payment_methods: { value: PaymentMethod; label: string; description: string }[]
  delivery_locations: { city: string; communes: string[] }[]
  choices: {
    order_status: Choice[]
    payment_method: Choice[]
    vendor_order_status: (Choice & { action: string })[]
    document_type: Choice[]
    payout_method: Choice[]
    deposit_method: Choice[]
    coupon_type: Choice[]
    product_sort: Choice[]
  }
  exchange_rates: Record<'EUR' | 'USD', number>
  payment_simulation: boolean
  referral_reward: number
  affiliate_rate: number
  commission_rate: number
  escrow_days: number
  unpaid_timeout_minutes: number
  rider_cash_limit: number
  page_size: number
}

export interface Quote {
  subtotal: number
  delivery_fee: number
  discount: number
  total: number
  delivery_delay: string
  coupon: string | null
  coupon_error: string
  shops: { shop_name: string; subtotal: number; delivery_fee: number }[]
}

export interface PickupPoint {
  id: number
  name: string
  city: string
  commune: string
  address: string
  phone: string
  opening_hours: string
  fee: number
}

export interface Notification {
  id: number
  kind: string
  title: string
  body: string
  link: string
  read_at: string | null
  created_at: string
}

export interface Review {
  id: number
  author: string
  rating: number
  comment: string
  photos: { id: number; image: string }[]
  vendor_reply: string
  created_at: string
  product?: { slug: string; name: string }
}

export interface Conversation {
  id: number
  other: { kind: 'shop' | 'client'; name: string; slug: string | null; logo: string | null; is_verified: boolean }
  last_message: { preview: string; sender: number; created_at: string } | null
  unread: number
  last_message_at: string | null
}

export interface Message {
  id: number
  conversation: number
  sender: number
  type: 'text' | 'image' | 'file' | 'voice'
  content: string
  attachment: string | null
  product: { slug: string; name: string; price: number; image: string | null } | null
  read_at: string | null
  created_at: string
}

export interface Wallet {
  debt: number
  balance: number
  available: number
  pending_payouts: number
  escrow: number
  entries: { id: number; kind: string; kind_label: string; amount: number; note: string; created_at: string }[]
}

export interface Payout {
  id: number
  amount: number
  method: 'orange_money' | 'mtn_momo'
  phone: string
  status: 'pending' | 'paid' | 'rejected'
  status_label: string
  admin_note: string
  created_at: string
}

export interface Referral {
  code: string
  link: string
  referrals: number
  affiliate_orders: number
  affiliate_earnings: number
  reward: number
  affiliate_rate: number
  coupons: { code: string; value: number; min_amount: number; description: string }[]
}

export interface Campaign {
  id: number
  name: string
  slug: string
  description: string
  banner: string | null
  color: string
  starts_at: string
  ends_at: string
  coupon_code: string | null
  products?: ProductSummary[]
}

export interface Coupon {
  id: number
  code: string
  type: 'percent' | 'fixed'
  value: number
  max_discount: number | null
  min_amount: number
  starts_at: string
  ends_at: string | null
  usage_limit: number | null
  per_user_limit: number
  used_count: number
  is_active: boolean
  description: string
}

export interface Live {
  id: number
  title: string
  description: string
  stream_url: string
  embed_url: string | null
  starts_at: string
  status: 'scheduled' | 'live' | 'ended'
  status_label: string
  shop: { slug: string; shop_name: string; logo: string | null }
  products?: ProductSummary[]
  pinned_product?: string | null
  comments?: LiveComment[]
}

export interface LiveComment {
  id: number
  author: string
  text: string
  created_at: string
}

export interface VendorLive {
  id: number
  title: string
  description: string
  stream_url: string
  starts_at: string
  status: 'scheduled' | 'live' | 'ended'
  product_slugs: string[]
}

export interface VendorStats {
  days: number
  revenue: number
  orders: number
  views: number
  conversion: number | null
  balance: number
  by_day: { day: string; revenue: number; orders: number }[]
  top_products: { product_name: string; sold: number; revenue: number }[]
}

export interface CashDeposit {
  id: number
  amount: number
  method: string
  method_label: string
  reference: string
  status: 'pending' | 'confirmed' | 'rejected'
  status_label: string
  admin_note: string
  created_at: string
}

export interface RiderDelivery {
  id: number
  cash_blocked?: boolean
  order_id: number
  status: 'open' | 'assigned' | 'picked_up' | 'delivered' | 'cancelled'
  status_label: string
  fee: number
  cash_to_collect: number
  items_count: number
  pickup: { shop_name: string; phone: string }
  dropoff: {
    city: string
    commune: string
    quartier: string
    landmark: string | null
    phone: string | null
    name: string | null
    latitude: string | null
    longitude: string | null
  }
  created_at: string
}

export interface RiderProfile {
  city: string
  communes: string
  vehicle_plate: string
  is_approved: boolean
  is_available: boolean
  rating: { rating: number | null; ratings_count: number }
  cash_debt: number
  cash_limit: number
}
