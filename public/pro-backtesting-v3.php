<?php
header('X-Robots-Tag: noindex');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pro Backtesting Terminal - ChartPulse</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Courier New', monospace; background: #0a0e27; color: #e0e0e0; }
    .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
    .header { border-bottom: 1px solid #333; padding: 20px 0; margin-bottom: 20px; }
    .header h1 { color: #f59e0b; margin-bottom: 5px; }
    .header p { color: #999; font-size: 12px; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .panel { background: #111; border: 1px solid #222; border-radius: 4px; padding: 15px; }
    .panel h2 { color: #10b981; margin-bottom: 15px; font-size: 14px; text-transform: uppercase; }
    input, select { width: 100%; padding: 8px; margin: 8px 0; background: #1a1f3a; border: 1px solid #333; color: #e0e0e0; border-radius: 3px; font-family: inherit; }
    button { background: #f59e0b; color: #000; border: none; padding: 10px 20px; border-radius: 3px; cursor: pointer; font-weight: bold; margin-top: 10px; }
    button:hover { background: #fbbf24; }
    .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
    .stat-box { background: #111; border: 1px solid #333; padding: 15px; border-radius: 3px; text-align: center; }
    .stat-box .label { font-size: 11px; color: #999; text-transform: uppercase; }
    .stat-box .value { font-size: 20px; color: #f59e0b; margin-top: 10px; font-weight: bold; }
    .loader { display: none; text-align: center; padding: 20px; color: #f59e0b; }
    .loader.active { display: block; }
    .error { color: #ef4444; padding: 10px; margin: 10px 0; background: #1a0a0a; border: 1px solid #ef4444; border-radius: 3px; }
    .trade { background: #111; border: 1px solid #333; padding: 12px; margin: 10px 0; border-radius: 3px; }
    .trade.win { border-left: 4px solid #10b981; }
    .trade.loss { border-left: 4px solid #ef4444; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>PRO BACKTESTING TERMINAL v3</h1>
      <p>Hono API Engine | Walk-Forward Analysis | Risk Management</p>
    </div>
    <div class="grid">
      <div class="panel">
        <h2>Strategy Setup</h2>
        <label>Symbol</label>
        <select id="symbol">
          <option>RELIANCE</option><option>TCS</option><option>INFY</option>
          <option>NIFTY</option><option>BTCUSDT</option><option>EURUSD</option>
        </select>
        <label>Timeframe</label>
        <select id="timeframe">
          <option value="1D">Daily (1D)</option>
          <option value="4H">4 Hour (4H)</option>
          <option value="1H">1 Hour (1H)</option>
        </select>
        <label>RSI Lower</label><input type="number" id="rsi_lower" value="30">
        <label>RSI Upper</label><input type="number" id="rsi_upper" value="70">
        <label>EMA Period</label><input type="number" id="ema_period" value="20">
        <label>Target Profit (%)</label><input type="number" id="target_profit" value="2" step="0.1">
        <label>Stop Loss (%)</label><input type="number" id="stop_loss" value="1" step="0.1">
        <button onclick="runBacktest()" style="width:100%;">RUN BACKTEST</button>
      </div>
      <div class="panel">
        <h2>Analytics</h2>
        <div class="stats">
          <div class="stat-box"><div class="label">Total Trades</div><div class="value" id="stat_trades">-</div></div>
          <div class="stat-box"><div class="label">Win Rate</div><div class="value" id="stat_wr">-</div></div>
          <div class="stat-box"><div class="label">P&L</div><div class="value" id="stat_pnl">-</div></div>
          <div class="stat-box"><div class="label">ROI</div><div class="value" id="stat_roi">-</div></div>
        </div>
      </div>
    </div>
    <div class="loader" id="loader">Analyzing...</div>
    <div id="error_container"></div>
    <div class="panel"><h2>Trade Results</h2><div id="trades_container"></div></div>
  </div>
  <script>
    const API_URL = 'https://chartpulse-backtesting.vercel.app/api';
    async function runBacktest() {
      document.getElementById('loader').classList.add('active');
      document.getElementById('error_container').innerHTML = '';
      try {
        const data = Array.from({length:100}, (_, i) => (100 + Math.sin(i/5)*10 + Math.random()*2).toFixed(2));
        const res = await fetch(API_URL+'/backtest', {
          method:'POST', headers:{'Content-Type':'application/json'},
          body: JSON.stringify({
            symbol: document.getElementById('symbol').value,
            data, rsi_lower: +document.getElementById('rsi_lower').value,
            rsi_upper: +document.getElementById('rsi_upper').value,
            ema_period: +document.getElementById('ema_period').value,
            target_profit: +document.getElementById('target_profit').value,
            stop_loss: +document.getElementById('stop_loss').value
          })
        });
        const r = await res.json();
        if(r.error){document.getElementById('error_container').innerHTML='<div class="error">'+r.error+'</div>';return;}
        document.getElementById('stat_trades').textContent = r.stats.total_trades;
        document.getElementById('stat_wr').textContent = r.stats.win_rate;
        document.getElementById('stat_pnl').textContent = '$'+r.stats.profit;
        document.getElementById('stat_roi').textContent = r.stats.roi;
        document.getElementById('trades_container').innerHTML = r.trades.map(t=>
          '<div class="trade '+(t.hit==='TP'?'win':'loss')+'">Entry: '+t.entry+' | TP: '+t.tp+' | SL: '+t.sl+' | '+t.hit+'</div>'
        ).join('') || '<p style="color:#999">No trades</p>';
      } catch(e) {
        document.getElementById('error_container').innerHTML='<div class="error">'+e.message+'</div>';
      } finally { document.getElementById('loader').classList.remove('active'); }
    }
  </script>
</body>
</html>
