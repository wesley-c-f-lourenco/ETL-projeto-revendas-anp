<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Revendas de GLP</title>
    <!-- Chart.js via cdnjs (mais estável) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background: #1a3a5c;
            padding: 24px;
            min-height: 100vh;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .header h1 { font-size: 22px; color: #fff; }
        .header p  { font-size: 12px; color: #a0c4e8; margin-top: 2px; }

        .btn-voltar {
            padding: 8px 18px;
            background: #fff;
            color: #1a3a5c;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-voltar:hover { background: #e0eaf8; }

        #msg {
            text-align: center;
            padding: 60px;
            color: #a0c4e8;
            font-size: 16px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
        }

        .kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .kpi-label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .kpi-valor {
            font-size: 30px;
            font-weight: bold;
            color: #1a3a5c;
            line-height: 1;
        }

        .kpi-desc { font-size: 11px; color: #aaa; margin-top: 4px; }

        .graficos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .grafico-full { grid-column: 1 / -1; }

        .card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .card h2 {
            font-size: 13px;
            color: #1a3a5c;
            margin-bottom: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card canvas { max-height: 300px; }

        #conteudo { display: none; }

        @media (max-width: 768px) {
            .graficos { grid-template-columns: 1fr; }
            .grafico-full { grid-column: 1; }
        }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1>Dashboard – Revendas de GLP</h1>
        <p>Visão analítica do cadastro ANP</p>
    </div>
    <a href="index.php" class="btn-voltar">← Voltar para a tabela</a>
</div>

<div id="msg">Carregando dados...</div>

<div id="conteudo">
    <div class="kpis">
        <div class="kpi-card">
            <div class="kpi-label">Total de Revendas</div>
            <div class="kpi-valor" id="kpi-total">—</div>
            <div class="kpi-desc">registros no banco ANP</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Estados cobertos</div>
            <div class="kpi-valor" id="kpi-estados">—</div>
            <div class="kpi-desc">unidades federativas</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Municípios atendidos</div>
            <div class="kpi-valor" id="kpi-municipios">—</div>
            <div class="kpi-desc">cidades com revendas</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Distribuidoras</div>
            <div class="kpi-valor" id="kpi-distribuidoras">—</div>
            <div class="kpi-desc">empresas ativas</div>
        </div>
    </div>

    <div class="graficos">
        <div class="card grafico-full">
            <h2>Revendas por Estado</h2>
            <canvas id="g-estados"></canvas>
        </div>
        <div class="card">
            <h2>Top 10 Municípios</h2>
            <canvas id="g-municipios"></canvas>
        </div>
        <div class="card">
            <h2>Market Share por Distribuidora</h2>
            <canvas id="g-distribuidoras"></canvas>
        </div>
        <div class="card grafico-full">
            <h2>Novas Autorizações por Ano</h2>
            <canvas id="g-anos"></canvas>
        </div>
    </div>
</div>

<script>
var cores = [
    '#1a3a5c','#2a5a8c','#3a7abf','#4a9ae0','#5ab0f0',
    '#0066cc','#003d7a','#66aadd','#99ccee','#cce5ff',
    '#1d6fa4','#5b9bd5','#2e75b6','#2f5496','#4472c4',
    '#217346','#70ad47','#ed7d31','#ffc000','#ff0000'
];

function num(v) {
    return Number(v).toLocaleString('pt-BR');
}

fetch('dashboard_dados.php')
    .then(function(r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(function(d) {
        // KPIs
        document.getElementById('kpi-total').textContent        = num(d.kpis.total_revendas);
        document.getElementById('kpi-estados').textContent      = num(d.kpis.total_estados);
        document.getElementById('kpi-municipios').textContent   = num(d.kpis.total_municipios);
        document.getElementById('kpi-distribuidoras').textContent = num(d.kpis.total_distribuidoras);

        // Revendas por estado
        new Chart(document.getElementById('g-estados').getContext('2d'), {
            type: 'bar',
            data: {
                labels: d.por_estado.map(function(e){ return e.UF; }),
                datasets: [{
                    label: 'Revendas',
                    data: d.por_estado.map(function(e){ return Number(e.total); }),
                    backgroundColor: '#3a7abf',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Top 10 municípios
        new Chart(document.getElementById('g-municipios').getContext('2d'), {
            type: 'bar',
            data: {
                labels: d.top_municipios.map(function(m){ return m.MUNICIPIO + ' (' + m.UF + ')'; }),
                datasets: [{
                    label: 'Revendas',
                    data: d.top_municipios.map(function(m){ return Number(m.total); }),
                    backgroundColor: cores.slice(0, 10),
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });

        // Distribuidoras (rosca)
        new Chart(document.getElementById('g-distribuidoras').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: d.por_distribuidora.map(function(x){ return x.DISTRIBUIDORA; }),
                datasets: [{
                    data: d.por_distribuidora.map(function(x){ return Number(x.total); }),
                    backgroundColor: cores.slice(0, d.por_distribuidora.length),
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { size: 11 }, boxWidth: 14 }
                    }
                }
            }
        });

        // Por ano (linha)
        new Chart(document.getElementById('g-anos').getContext('2d'), {
            type: 'line',
            data: {
                labels: d.por_ano.map(function(a){ return a.ano; }),
                datasets: [{
                    label: 'Novas autorizações',
                    data: d.por_ano.map(function(a){ return Number(a.total); }),
                    borderColor: '#3a7abf',
                    backgroundColor: 'rgba(58,122,191,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Mostra conteúdo
        document.getElementById('msg').style.display      = 'none';
        document.getElementById('conteudo').style.display = 'block';
    })
    .catch(function(err) {
        document.getElementById('msg').textContent = 'Erro ao carregar: ' + err.message;
        document.getElementById('msg').style.color = '#ff9999';
    });
</script>
</body>
</html>