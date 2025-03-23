export interface MaterialData {
  kilos: number | null;
  snContent: number | null;
  inventoryItemId: number | null;
}

export interface Process {
  id?: number;
  batch_id?: number;
  processNumber: number;
  processingType: 'kaldo_furnace' | 'refining_kettle';
  inputTinKilos: number;
  inputTinSnContent: number;
  inputTinInventoryItemId: number;
  outputTinKilos: number;
  outputTinSnContent: number;
  outputTinInventoryItemId: number;
  inputSlagKilos: number;
  inputSlagSnContent: number;
  inputSlagInventoryItemId: number;
  outputSlagKilos: number;
  outputSlagSnContent: number;
  outputSlagInventoryItemId: number;
  notes?: string;
  createdAt?: string;
  updatedAt?: string;
}

export interface Batch {
  id?: number;
  batchNumber: string;
  date: string;
  status: 'in_progress' | 'completed' | 'cancelled';
  notes: string;
  processes: Process[];
  createdAt?: string;
  updatedAt?: string;
}

export interface InventoryItem {
  id: number;
  name: string;
  type: string;
  quantity: number;
  unit: string;
  sn_content: number;
  status: string;
} 