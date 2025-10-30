<?php include __DIR__.'/includes/header.php'; ?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(dirname(__DIR__, 1) . '/autoload.php');

?>



<style>
.card {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.card-img-top {
    width: 100%;
    height: 120px;
    object-fit: cover;
}
.card-title {
    font-size: 1.1rem;
    margin-bottom: .5rem;
    text-align: center;
}
.card-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: center;
}
</style>



<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Games</h3>
        <button class="btn btn-success" id="btnNovo">Novo Game</button>
    </div>

    <div class="mb-3">
        <input type="text" id="search" class="form-control" placeholder="Buscar game...">
    </div>

    <div id="gamesList" class="row g-3"></div>

    <nav>
        <ul class="pagination justify-content-center mt-3" id="pagination"></ul>
    </nav>
</div>

<!-- Modal -->
<div class="modal fade" id="gameModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Game</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="gameId">
        <div class="mb-3">
            <input type="text" id="gameNome" class="form-control" placeholder="Nome">
        </div>
        <div class="mb-3">
            <input type="text" id="gameImage" class="form-control" placeholder="URL da imagem">
        </div>
        <div class="mb-3">
            <input type="text" id="gameUrl" class="form-control" placeholder="URL Jogar">
        </div>
        <div class="mb-3">
            <input type="text" id="gameDemo" class="form-control" placeholder="URL Demo">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" id="saveGame">Salvar</button>
      </div>
    </div>
  </div>
</div>
<?php
/*
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

*/?>
<script>
let currentPage = 1;
const pageSize = 20;

function loadGames(page = 1, search = '') {
    currentPage = page;
    $.getJSON(`/api/games/list.php`, { page, pageSize, search }, function(resp) {
        if (!resp.sucess) {
            $('#gamesList').html('<p class="text-danger">'+resp.msg+'</p>');
            return;
        }

        const data = resp.data.data;
        const totalPages = resp.data.totalPages;
        $('#gamesList').empty();

        if (data.length === 0) {
            $('#gamesList').html('<p>Nenhum game encontrado.</p>');
            $('#pagination').empty();
            return;
        }

        data.forEach(game => {
            const html = `
            <div class="col-md-4 col-sm-6">
                <div class="card h-100">
                    <img src="${game.image || 'https://via.placeholder.com/300x150?text=Sem+Imagem'}" class="card-img-top" alt="${game.nome}">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <h5 class="card-title">${game.nome}</h5>
                        <div class="card-buttons mt-2">
                            <button class="btn btn-sm btn-primary" onclick="playDemo('${game.demo}')">Demo</button>
                            <button class="btn btn-sm btn-success" onclick="playGame('${game.url}')">Jogar</button>
                            <button class="btn btn-sm btn-info text-white" onclick="infoGame(${game.id})">Info</button>
                            <button class="btn btn-sm btn-warning" onclick="editGame(${game.id})">Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteGame(${game.id})">Excluir</button>
                        </div>
                    </div>
                </div>
            </div>`;
            $('#gamesList').append(html);
        });

        renderPagination(totalPages);
    });
}

function renderPagination(totalPages) {
    const ul = $('#pagination');
    ul.empty();

    const delta = 3; // Quantas páginas antes/depois da atual
    const current = currentPage;

    const pages = [];

    // Sempre mostrar a primeira página
    if (current > delta + 2) {
        pages.push(1);
        pages.push('...');
    } else {
        for (let i = 1; i < Math.min(current - delta, 1); i++) pages.push(i);
    }

    // Páginas ao redor da atual
    const start = Math.max(1, current - delta);
    const end = Math.min(totalPages, current + delta);

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    // Sempre mostrar a última página
    if (current + delta < totalPages - 1) {
        pages.push('...');
        pages.push(totalPages);
    } else {
        for (let i = end + 1; i <= totalPages; i++) pages.push(i);
    }

    // Renderizar
    pages.forEach(p => {
        if (p === '...') {
            ul.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        } else {
            ul.append(`<li class="page-item ${p === current ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadGames(${p}, $('#search').val())">${p}</a>
            </li>`);
        }
    });
}


$('#search').on('input', function() {
    loadGames(1, $(this).val());
});

$('#btnNovo').on('click', function() {
    $('#gameId').val('');
    $('#gameNome').val('');
    $('#gameImage').val('');
    $('#gameUrl').val('');
    $('#gameDemo').val('');
    new bootstrap.Modal(document.getElementById('gameModal')).show();
});

$('#saveGame').on('click', function() {
    const payload = {
        id: $('#gameId').val(),
        nome: $('#gameNome').val(),
        image: $('#gameImage').val(),
        url: $('#gameUrl').val(),
        demo: $('#gameDemo').val()
    };

    $.ajax({
        url: '/api/games/save.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function(resp) {
            alert(resp.msg);
            loadGames(currentPage, $('#search').val());
            bootstrap.Modal.getInstance(document.getElementById('gameModal')).hide();
        }
    });
});

function editGame(id) {
    $.getJSON(`/api/games/get.php`, { id }, function(resp) {
        if (!resp.sucess) { alert(resp.msg); return; }
        const g = resp.data;
        $('#gameId').val(g.id);
        $('#gameNome').val(g.nome);
        $('#gameImage').val(g.image);
        $('#gameUrl').val(g.url);
        $('#gameDemo').val(g.demo);
        new bootstrap.Modal(document.getElementById('gameModal')).show();
    });
}

function deleteGame(id) {
    if (!confirm('Deseja realmente deletar este game?')) return;
    $.ajax({
        url: '/api/games/delete.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id }),
        success: function(resp) {
            alert(resp.msg);
            loadGames(currentPage, $('#search').val());
        }
    });
}

function infoGame(id) {
    window.location.href = `game_info.php?id=${id}`;
}

function playDemo(url) { if(url) window.open(url, '_blank'); else alert('Demo não disponível'); }
function playGame(url) { if(url) window.open(url, '_blank'); else alert('URL do jogo não disponível'); }

$(function(){ loadGames(); });
</script>
<?php
/*</body>
</html>*/?>

<?php include __DIR__.'/includes/footer.php'; ?>




