import { Hono } from 'hono';
import { cors } from 'hono/cors';
import { backtestRoute } from './routes/backtest';
import { symbolRoute } from './routes/symbols';

const app = new Hono();

app.use('*', cors({
  origin: ['https://chartpulse.io', 'http://localhost:3000'],
  allowMethods: ['GET', 'POST', 'OPTIONS'],
  allowHeaders: ['Content-Type'],
}));

app.get('/api/health', (c) => c.json({ status: 'ok', engine: 'hono_v7', timestamp: new Date().toISOString() }));

app.route('/api', backtestRoute);
app.route('/api', symbolRoute);

export default app;
