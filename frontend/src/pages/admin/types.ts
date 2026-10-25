// Données renvoyées par l'API du back-office (/api/backoffice/…)

export type UserMini = { id: number; name: string; email: string; phone: string }

export interface AdminVendor {
  id: number
  slug: string
  shop_name: string
  description: string
  logo: string | null
  whatsapp: string
  owner: UserMini
  status: string
  status_label: string
  is_verified: boolean
  is_premium: boolean
  commission_rate: string | null
  products_count: number
  orders_count: number
  revenue: number | null
  created_at: string
}

export interface AdminVerification {
  id: number
  shop_name: string
  shop_slug: string
  owner: UserMini
  document_type: string
  document_type_label: string
  document_number: string
  status: string
  status_label: string
  admin_note: string
  documents: string[]
  created_at: string
}

export interface AdminRider {
  id: number
  user: UserMini
  city: string
  communes: string
  vehicle_plate: string
  is_approved: boolean
  is_available: boolean
  rating: { rating: number | null; ratings_count: number }
  cash_debt: number
  deliveries_done: number
  created_at: string
}

export interface AdminUser {
  id: number
  name: string
  email: string
  phone: string
  role: string
  role_label: string
  is_active: boolean
  has_2fa: boolean
  shop: string | null
  date_joined: string
  last_login: string | null
}

export interface AdminVendorOrder {
  id: number
  shop_name: string
  subtotal: number
  delivery_fee: number
  total_amount: number
  status: string
  status_label: string
  delivery_code_attempts: number
  code_blocked: boolean
  funds_released_at: string | null
  items: { name: string; quantity: number; unit_price: number }[]
  rider: { name: string; phone: string; status: string } | null
}

export interface AdminOrder {
  id: number
  client: UserMini
  subtotal: number
  delivery_fee: number
  discount_amount: number
  total_amount: number
  status: string
  status_label: string
  payment_method_label: string
  payment_status: string
  payment_status_label: string
  delivery_method: string
  delivery_city: string
  delivery_commune: string
  delivery_quartier: string
  delivery_landmark: string
  delivery_phone: string
  delivery_name: string
  shops: string[]
  created_at: string
  vendor_orders?: AdminVendorOrder[]
  history?: { status: string; note: string; by: string | null; at: string }[]
}

export interface AdminPayout {
  id: number
  user: UserMini
  amount: number
  method_label: string
  phone: string
  status: string
  status_label: string
  transaction_reference: string
  admin_note: string
  balance: number
  created_at: string
}

export interface AdminDeposit {
  id: number
  user: UserMini
  amount: number
  method_label: string
  reference: string
  status: string
  status_label: string
  admin_note: string
  created_at: string
}

export interface AdminCategory {
  id: number
  name: string
  slug: string
  description: string
  image: string | null
  parent: number | null
  status: string
  products_count: number
}

export interface AdminProduct {
  id: number
  slug: string
  name: string
  shop_name: string
  category_name: string
  price: number
  stock: number
  status: string
  image: string | null
  created_at: string
}

export interface AdminReview {
  id: number
  author: string
  product_name: string
  product_slug: string
  rating: number
  comment: string
  vendor_reply: string
  is_visible: boolean
  created_at: string
}

export interface AdminCoupon {
  id: number
  code: string
  type: string
  type_label: string
  value: number
  max_discount: number | null
  min_amount: number
  shop_name: string | null
  starts_at: string
  ends_at: string | null
  usage_limit: number | null
  per_user_limit: number
  used_count: number
  is_active: boolean
  description: string
}

export interface AdminCampaign {
  id: number
  name: string
  slug: string
  description: string
  banner: string | null
  color: string
  starts_at: string
  ends_at: string
  product_slugs: string[]
  coupon_code: string | null
  is_active: boolean
  is_running: boolean
}

export interface AdminZone {
  id: number
  city: string
  commune: string
  fee: number
  delay: string
  is_active: boolean
}

export interface AdminPickupPoint {
  id: number
  name: string
  city: string
  commune: string
  address: string
  phone: string
  opening_hours: string
  fee: number
  is_active: boolean
}

export interface AuditEntry {
  id: number
  actor: string | null
  action: string
  target: string
  data: Record<string, unknown>
  ip: string | null
  created_at: string
}
