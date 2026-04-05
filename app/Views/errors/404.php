<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>404 — Página não encontrada</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:'Segoe UI',system-ui,sans-serif;}
.wrap{text-align:center;padding:2rem;}
.code{font-size:clamp(5rem,15vw,9rem);font-weight:900;line-height:1;background:linear-gradient(135deg,#3b82f6,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.icon{font-size:3rem;color:#3b82f6;margin:1rem 0 .5rem;}
h1{font-size:1.5rem;font-weight:600;color:#f1f5f9;margin-bottom:.5rem;}
p{color:#94a3b8;max-width:380px;margin:0 auto 2rem;line-height:1.6;}
.btn{display:inline-flex;align-items:center;gap:.5rem;background:#3b82f6;color:#fff;padding:.65rem 1.75rem;border-radius:.5rem;text-decoration:none;font-weight:600;font-size:.9rem;transition:background .2s;}
.btn:hover{background:#2563eb;color:#fff;}
.btn-outline{background:transparent;border:1px solid #334155;color:#94a3b8;margin-left:.75rem;}
.btn-outline:hover{background:#1e293b;color:#e2e8f0;}
</style>
</head>
<body>
<div class="wrap">
  <div class="code">404</div>
  <div class="icon"><i class="bi bi-compass"></i></div>
  <h1>Página não encontrada</h1>
  <p>A rota que você tentou acessar não existe ou foi movida.</p>
  <a href="/" class="btn"><i class="bi bi-house-fill"></i> Ir para o início</a>
  <a href="javascript:history.back()" class="btn btn-outline">
    <i class="bi bi-arrow-left"></i> Voltar
  </a>
</div>
</body>
</html>
