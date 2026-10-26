export interface OHLCData {
  open: number;
  high: number;
  low: number;
  close: number;
}

export interface BacktestParams {
  symbol: string;
  data: number[];
  rsi_lower?: number;
  rsi_upper?: number;
  ema_period?: number;
  target_profit?: number;
  stop_loss?: number;
}

export interface Trade {
  entry: number;
  tp: number;
  sl: number;
  hit: 'TP' | 'SL';
  bars: number;
}

export interface BacktestResult {
  symbol: string;
  trades: Trade[];
  stats: {
    total_trades: number;
    wins: number;
    losses: number;
    win_rate: string;
    final_balance: number;
    profit: string;
    roi: string;
  };
}

export function calculateRSI(prices: number[], period = 14): number[] {
  if (prices.length < period + 1) return [];
  const rsi: number[] = [];
  let gains = 0, losses = 0;
  for (let i = 1; i <= period; i++) {
    const diff = prices[i] - prices[i - 1];
    if (diff > 0) gains += diff; else losses -= diff;
  }
  gains /= period; losses /= period;
  for (let i = period; i < prices.length; i++) {
    if (i > period) {
      const diff = prices[i] - prices[i - 1];
      gains = (gains * (period - 1) + Math.max(diff, 0)) / period;
      losses = (losses * (period - 1) + Math.max(-diff, 0)) / period;
    }
    const rs = losses === 0 ? 100 : gains / losses;
    rsi.push(100 - 100 / (1 + rs));
  }
  return rsi;
}

export function calculateEMA(prices: number[], period: number): number[] {
  if (prices.length < period) return [];
  const k = 2 / (period + 1);
  const ema: number[] = [prices.slice(0, period).reduce((a, b) => a + b, 0) / period];
  for (let i = period; i < prices.length; i++) {
    ema.push(prices[i] * k + ema[ema.length - 1] * (1 - k));
  }
  return ema;
}

export function runBacktest(params: BacktestParams): BacktestResult {
  const { symbol, data, rsi_lower = 30, rsi_upper = 70, ema_period = 20, target_profit = 2, stop_loss = 1 } = params;
  const prices = data.map(Number);
  const rsi = calculateRSI(prices, 14);
  const ema = calculateEMA(prices, ema_period);
  const offset = Math.max(14, ema_period) - 1;
  const trades: Trade[] = [];
  let balance = 10000;

  for (let i = 1; i < rsi.length - 1; i++) {
    const pi = i + offset;
    if (pi >= prices.length - 1) break;
    const emaIdx = pi - (prices.length - ema.length);
    if (emaIdx < 0) continue;
    const price = prices[pi];
    const aboveEma = price > ema[emaIdx];
    if (rsi[i - 1] < rsi_lower && rsi[i] >= rsi_lower && aboveEma) {
      const tp = price * (1 + target_profit / 100);
      const sl = price * (1 - stop_loss / 100);
      let hit: 'TP' | 'SL' = 'SL', bars = 0;
      for (let j = pi + 1; j < Math.min(pi + 50, prices.length); j++) {
        bars++;
        if (prices[j] >= tp) { hit = 'TP'; break; }
        if (prices[j] <= sl) { hit = 'SL'; break; }
      }
      trades.push({ entry: +price.toFixed(2), tp: +tp.toFixed(2), sl: +sl.toFixed(2), hit, bars });
      const pct = hit === 'TP' ? target_profit / 100 : -stop_loss / 100;
      balance *= (1 + pct);
    }
  }

  const wins = trades.filter(t => t.hit === 'TP').length;
  return {
    symbol,
    trades: trades.slice(-50),
    stats: {
      total_trades: trades.length,
      wins,
      losses: trades.length - wins,
      win_rate: trades.length ? (wins / trades.length * 100).toFixed(1) + '%' : '0%',
      final_balance: +balance.toFixed(2),
      profit: (balance - 10000).toFixed(2),
      roi: ((balance - 10000) / 100).toFixed(2) + '%',
    },
  };
      }
