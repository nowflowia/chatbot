<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>500 — Erro interno</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:'Segoe UI',system-ui,sans-serif;}
.wrap{text-align:center;padding:2rem;}
.code{font-size:clamp(5rem,15vw,9rem);font-weight:900;line-height:1;background:linear-gradient(135deg,#ef4444,#f59e0b);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.icon{font-size:3rem;color:#ef4444;margin:1rem 0 .5rem;}
h1{font-size:1.5rem;font-weight:600;color:#f1f5f9;margin-bottom:.5rem;}
p{color:#94a3b8;max-width:400px;margin:0 auto 2rem;line-height:1.6;}
.hint{background:#1e293b;border:1px solid #334155;border-radius:.5rem;padding:1rem 1.5rem;text-align:left;max-width:600px;margin:0 auto 2rem;font-size:.8rem;color:#94a3b8;font-family:monospace;word-break:break-all;}
.btn{display:inline-flex;align-items:center;gap:.5rem;background:#ef4444;color:#fff;padding:.65rem 1.75rem;border-radius:.5rem;text-decoration:none;font-weight:600;font-size:.9rem;transition:background .2s;}
.btn:hover{background:#dc2626;color:#fff;}
.btn-outline{background:transparent;border:1px solid #334155;color:#94a3b8;margin-left:.75rem;}
.btn-outline:hover{background:#1e293b;color:#e2e8f0;}
</style>
</head>
<body>
<div class="wrap">
  <div class="code">500</div>
  <div class="icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
  <h1>Erro interno do servidor</h1>
  <p>Algo deu errado no servidor. A equipe técnica foi notificada.<br>Tente novamente em alguns instantes.</p>
  <?php if (!empty($message)): ?>
  <div class="hint"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <a href="/" class="btn"><i class="bi bi-house-fill"></i> Ir para o início</a>
  <a href="javascript:history.back()" class="btn btn-outline">
    <i class="bi bi-arrow-left"></i> Voltar
  </a>
</div>
</body>
</html>
