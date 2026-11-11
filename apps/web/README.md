# Meridian Web

React 19 + TypeScript single-page app for the Meridian API. Architecture, folder structure, authentication flow, state management and component design are covered in [docs/11 — Frontend Architecture](../../docs/11-frontend-architecture.md).

```bash
npm install
npm run dev            # http://localhost:5173, proxies /api → http://127.0.0.1:8000
npm run api:generate   # regenerate src/services/api/generated from the OpenAPI contract
npm run lint && npm run typecheck && npm test
npm run build
```

Never edit `src/services/api/generated` by hand; CI regenerates it and fails on any difference.

The employee module (directory, wizard, conflicts, documents, accounts, history) is described in [docs/12 — Employee Module](../../docs/12-employee-module.md). To regenerate its screenshots against a running stack:

```bash
E2E_EMAIL=… E2E_PASSWORD=… npm run docs:screenshots   # writes docs/screenshots/*.png
```
