<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revendas de GLP – ANP</title>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background-color: #1a3a5c;
            padding: 28px;
            min-height: 100vh;
        }

        .container {
            max-width: 1260px;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            padding: 28px;
            box-shadow: 0 6px 24px rgba(0,0,0,0.4);
        }

        /* ── Cabeçalho ── */
        .cabecalho {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 12px;
        }

        .cabecalho h1 { font-size: 22px; color: #1a3a5c; margin-bottom: 4px; }
        .cabecalho .fonte { font-size: 12px; color: #888; }

        .btn-dashboard {
            padding: 9px 18px;
            background: #1a3a5c;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            display: inline-block;
        }

        .btn-dashboard:hover { background: #2a5a8c; }

        /* ── Barra de filtros ── */
        .barra-filtros {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 20px;
            align-items: flex-end;
        }

        .filtro-grupo label,
        .dropdown-wrap label,
        .filtro-data-grupo label {
            display: block;
            font-size: 12px;
            font-weight: bold;
            color: #1a3a5c;
            margin-bottom: 4px;
        }

        .filtro-grupo input[type="text"] {
            padding: 7px 12px;
            border: 1px solid #b0c4de;
            border-radius: 5px;
            font-size: 13px;
            width: 200px;
            outline: none;
        }

        .filtro-grupo input[type="text"]:focus { border-color: #1a3a5c; }

        /* ── Dropdowns ── */
        .dropdown-wrap { position: relative; }

        .dropdown-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 7px 12px;
            border: 1px solid #b0c4de;
            border-radius: 5px;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
            color: #333;
            min-width: 190px;
            user-select: none;
        }

        .dropdown-btn:hover { border-color: #1a3a5c; }
        .dropdown-btn.ativo { border-color: #1a3a5c; background: #eef4ff; font-weight: bold; color: #1a3a5c; }

        .dropdown-seta { font-size: 10px; color: #888; flex-shrink: 0; }

        .dropdown-painel {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            z-index: 9999;
            background: #fff;
            border: 1px solid #b0c4de;
            border-radius: 6px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
            min-width: 220px;
            max-width: 320px;
        }

        .dropdown-painel.aberto { display: block; }

        .dropdown-busca-interna {
            padding: 10px 10px 6px;
            border-bottom: 1px solid #eee;
        }

        .dropdown-busca-interna input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 13px;
            outline: none;
        }

        .dropdown-busca-interna input:focus { border-color: #1a3a5c; }

        .dropdown-lista { max-height: 240px; overflow-y: auto; padding: 4px 0; }

        .dropdown-item {
            padding: 7px 14px;
            font-size: 13px;
            cursor: pointer;
            color: #333;
        }

        .dropdown-item:hover { background: #eef4ff; color: #1a3a5c; }
        .dropdown-item.selecionado { background: #1a3a5c; color: #fff; }
        .dropdown-item.oculto { display: none; }

        .dropdown-vazio { padding: 10px 14px; font-size: 12px; color: #999; display: none; }

        /* ── Filtro de data ── */
        .filtro-data-grupo { display: flex; flex-direction: column; }

        .filtro-data-inputs {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filtro-data-inputs span { font-size: 12px; color: #666; }

        .filtro-data-inputs input[type="date"] {
            padding: 7px 10px;
            border: 1px solid #b0c4de;
            border-radius: 5px;
            font-size: 13px;
            color: #333;
            outline: none;
            cursor: pointer;
        }

        .filtro-data-inputs input[type="date"]:focus { border-color: #1a3a5c; }

        /* ── Botão limpar ── */
        .btn-limpar {
            padding: 7px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            background: #888;
            color: #fff;
            margin-top: 16px;
        }

        .btn-limpar:hover { background: #666; }

        /* ── Botão Excel ── */
        .buttons-excel {
            background-color: #217346 !important;
            color: white !important;
            border: none !important;
            border-radius: 5px !important;
            padding: 6px 14px !important;
            font-size: 13px !important;
            font-weight: bold !important;
        }

        .buttons-excel:hover { background-color: #1a5c38 !important; }

        #tabela-revendas { width: 100% !important; }
    </style>
</head>
<body>
<div class="container">

    <!-- Cabeçalho com botão do dashboard -->
    <div class="cabecalho">
        <div>
            <h1>Cadastro de Revendas de GLP – ANP</h1>
            <p class="fonte">
                Fonte: <a href="https://www.gov.br/anp/pt-br/centrais-de-conteudo/dados-abertos"
                          target="_blank">ANP – Dados Abertos</a>
            </p>
        </div>
        <a href="dashboard.php" class="btn-dashboard">📊 Ver Dashboard</a>
    </div>

    <!-- Barra de filtros -->
    <div class="barra-filtros">

        <!-- CNPJ -->
        <div class="filtro-grupo">
            <label for="busca-cnpj">CNPJ</label>
            <input type="text"
                   id="busca-cnpj"
                   placeholder="Ex: 01234567000191"
                   maxlength="14"
                   oninput="aplicarFiltros()">
        </div>

        <!-- Estado -->
        <div class="dropdown-wrap" id="wrap-uf">
            <label>Estado</label>
            <div class="dropdown-btn" id="btn-uf" onclick="toggleDropdown('uf')">
                <span class="dropdown-texto" id="texto-uf">Todos os estados</span>
                <span class="dropdown-seta">▾</span>
            </div>
            <div class="dropdown-painel" id="painel-uf">
                <div class="dropdown-busca-interna">
                    <input type="text"
                           placeholder="Buscar estado..."
                           oninput="filtrarOpcoes('uf', this.value)">
                </div>
                <div class="dropdown-lista" id="lista-uf"></div>
                <div class="dropdown-vazio" id="vazio-uf">Nenhum resultado</div>
            </div>
        </div>

        <!-- Município -->
        <div class="dropdown-wrap" id="wrap-municipio">
            <label>Município</label>
            <div class="dropdown-btn" id="btn-municipio" onclick="toggleDropdown('municipio')">
                <span class="dropdown-texto" id="texto-municipio">Todos os municípios</span>
                <span class="dropdown-seta">▾</span>
            </div>
            <div class="dropdown-painel" id="painel-municipio">
                <div class="dropdown-busca-interna">
                    <input type="text"
                           placeholder="Buscar município..."
                           oninput="filtrarOpcoes('municipio', this.value)">
                </div>
                <div class="dropdown-lista" id="lista-municipio"></div>
                <div class="dropdown-vazio" id="vazio-municipio">Nenhum resultado</div>
            </div>
        </div>

        <!-- Data de publicação -->
        <div class="filtro-data-grupo">
            <label>Data de Publicação</label>
            <div class="filtro-data-inputs">
                <input type="date" id="data-inicio" onchange="aplicarFiltros()">
                <span>até</span>
                <input type="date" id="data-fim" onchange="aplicarFiltros()">
            </div>
        </div>

        <!-- Limpar -->
        <button class="btn-limpar" onclick="limparTudo()">Limpar filtros</button>

    </div>

    <!-- Tabela -->
    <table id="tabela-revendas" class="display">
        <thead>
            <tr>
                <th>CNPJ</th>
                <th>Razão Social</th>
                <th>UF</th>
                <th>Município</th>
                <th>Autorização</th>
                <th>Data Publicação</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
var tabela;
var selecionado  = { uf: '', municipio: '' };
var textoPadrao  = { uf: 'Todos os estados', municipio: 'Todos os municípios' };

// Filtro de data personalizado
$.fn.dataTable.ext.search.push(function (settings, data) {
    var inicio = $('#data-inicio').val();
    var fim    = $('#data-fim').val();
    var celula = data[5];
    if (!inicio && !fim) return true;
    if (!celula) return true;
    var p = celula.split('/');
    if (p.length !== 3) return true;
    var dl = p[2] + '-' + p[1] + '-' + p[0];
    if (inicio && dl < inicio) return false;
    if (fim    && dl > fim)    return false;
    return true;
});

$(document).ready(function () {
    tabela = $('#tabela-revendas').DataTable({
        ajax: {
            url: 'revendas_anp_selecionar.php',
            type: 'GET',
            dataSrc: function (json) {
                popularDropdowns(json.data);
                return json.data;
            }
        },
        columns: [
            { data: 'CNPJ' },
            { data: 'RAZAOSOCIAL' },
            { data: 'UF' },
            { data: 'MUNICIPIO' },
            { data: 'AUTORIZACAO' },
            { data: 'DATAPUBLICACAO' }
        ],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json' },
        processing: true,
        dom: 'Blfrtp i',
        buttons: [{
            extend: 'excelHtml5',
            text: '⬇ Exportar Excel',
            title: 'Revendas_GLP_ANP',
            className: 'buttons-excel',
            exportOptions: { modifier: { search: 'applied', order: 'applied' } }
        }]
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.dropdown-wrap').length) fecharTodosDropdowns();
    });
});

function popularDropdowns(dados) {
    var ufs = [], munics = [];
    dados.forEach(function (r) {
        if (r.UF       && ufs.indexOf(r.UF) === -1)           ufs.push(r.UF);
        if (r.MUNICIPIO && munics.indexOf(r.MUNICIPIO) === -1) munics.push(r.MUNICIPIO);
    });
    ufs.sort(); munics.sort();
    renderizarOpcoes('uf', ufs);
    renderizarOpcoes('municipio', munics);
}

function renderizarOpcoes(id, valores) {
    var html = '';
    valores.forEach(function (v) {
        var ve = v.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        html += '<div class="dropdown-item" data-valor="' + v.replace(/"/g,'&quot;') + '" onclick="selecionarOpcao(\'' + id + '\',\'' + ve + '\')">' + v + '</div>';
    });
    $('#lista-' + id).html(html);
}

function toggleDropdown(id) {
    var aberto = $('#painel-' + id).hasClass('aberto');
    fecharTodosDropdowns();
    if (!aberto) {
        $('#painel-' + id).addClass('aberto');
        $('#btn-'   + id).addClass('ativo');
        $('#painel-' + id).find('input').first().val('').focus();
        filtrarOpcoes(id, '');
    }
}

function fecharTodosDropdowns() {
    $('.dropdown-painel').removeClass('aberto');
    $('.dropdown-btn').removeClass('ativo');
}

function filtrarOpcoes(id, termo) {
    var lower = termo.toLowerCase(), algum = false;
    $('#lista-' + id + ' .dropdown-item').each(function () {
        var match = $(this).text().toLowerCase().indexOf(lower) !== -1;
        $(this).toggleClass('oculto', !match);
        if (match) algum = true;
    });
    $('#vazio-' + id).css('display', algum ? 'none' : 'block');
}

function selecionarOpcao(id, valor) {
    selecionado[id] = valor;
    $('#lista-' + id + ' .dropdown-item').removeClass('selecionado');
    $('#lista-' + id + ' .dropdown-item[data-valor="' + valor.replace(/"/g,'&quot;') + '"]').addClass('selecionado');
    $('#texto-' + id).text(valor);
    fecharTodosDropdowns();
    aplicarFiltros();
}

function aplicarFiltros() {
    tabela.column(0).search($('#busca-cnpj').val().trim(), false, false);
    tabela.column(2).search(selecionado.uf        ? '^' + selecionado.uf        + '$' : '', true, false);
    tabela.column(3).search(selecionado.municipio ? '^' + selecionado.municipio + '$' : '', true, false);
    tabela.draw();
}

function limparTudo() {
    selecionado = { uf: '', municipio: '' };
    $('#busca-cnpj, #data-inicio, #data-fim').val('');
    $('#texto-uf').text(textoPadrao.uf);
    $('#texto-municipio').text(textoPadrao.municipio);
    $('.dropdown-item').removeClass('selecionado');
    tabela.column(0).search('');
    tabela.column(2).search('');
    tabela.column(3).search('');
    tabela.draw();
}
</script>
</body>
</html>