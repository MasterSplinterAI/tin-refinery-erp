export interface InventoryItem {
  id: number;
  name: string;
  type: string;
  description?: string;
  quantity: number;
  unit: string;
  sn_content?: number;
  unit_price?: number;
  currency: string;
  location?: string;
  status: 'active' | 'inactive';
  notes?: string;
  created_at?: string;
  updated_at?: string;
}

export interface Transaction {
  id: number;
  inventory_item_id: number;
  type: 'consumption' | 'production' | 'reversal' | 'adjustment';
  quantity: number;
  unit_price?: number;
  currency: string;
  reference_type: string;
  reference_id?: number;
  notes?: string;
  created_at: string;
  updated_at: string;
  inventory_item?: InventoryItem;
}

export interface AdjustmentFormData {
  inventory_item_id: number;
  quantity: number;
  unit_price?: number;
  currency: string;
  notes?: string;
} 