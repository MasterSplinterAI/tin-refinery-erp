export interface ExchangeRate {
  id: number;
  rate: number;
  date: string;
  source: string;
  api_provider?: string;
  metadata?: Record<string, any>;
  created_at: string;
  updated_at: string;
} 