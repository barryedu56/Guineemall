// Types des réponses de l'API Django

export interface Paginated<T> {
  count: number
  next: string | null
  previous: string | null
  results: T[]
}

export type Role = 'admin' | 'vendeur' | 'client'

export interface User {
  id: number
  email: string
  name: string
  phone: string
  role: Role
  avatar: string | null
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

export type PaymentMethod = 'cash_on_delivery' | 'orange_money' | 'mtn_momo'
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

export interface Order {
  id: number
  total_amount: number
  status: OrderStatus
  status_label: string
  payment_method: PaymentMethod
  payment_status: string
  delivery_city: string
  delivery_commune: string
  delivery_quartier: string
  delivery_landmark: string
  delivery_phone: string
  vendor_orders: {
    id: number
    shop_name: string
    total_amount: number
    status: VendorOrderStatus
    status_label: string
    items: OrderItem[]
  }[]
  status_history: { status: string; note: string; created_at: string }[]
  created_at: string
}

export interface VendorSideOrder {
  id: number
  order_id: number
  client_name: string
  total_amount: number
  status: VendorOrderStatus
  status_label: string
  payment_method: PaymentMethod
  items: OrderItem[]
  delivery: {
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
