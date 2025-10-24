<?php include __DIR__.'/includes/header.php'; ?>
<?php include __DIR__.'/includes/menu.php'; ?>

<?php
require_once __DIR__.'/../autoload.php';
session_start();

$userId = $_SESSION['id'] ?? 0;

$db = (new Database())->getPdo();
$stmt = $db->prepare("SELECT id, nome FROM Carteira WHERE PayerId = :uid");
$stmt->execute([':uid' => $userId]);
$carteiras = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h4 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h4>
<h2>Bem-vindo, <?= htmlspecialchars($_SESSION['user']) ?></h2>

<!-- Select de carteiras -->
<div class="mb-3">
    <label for="selectCarteira" class="form-label">Selecione a carteira:</label>
    <select id="selectCarteira" class="form-select">
        <option value="0">Todas</option>
        <?php foreach($carteiras as $c): 
        if($c->selected)
        {
            ?>
                         <option value="<?= $c['id'] ?>" selected="true"><?= htmlspecialchars($c['nome']) ?></option>
            <?php
        }
        else
        {
                        ?>
                         <option value="<?= $c['id'] ?>" ><?= htmlspecialchars($c['nome']) ?></option>
            <?php
        }
        ?>

                  
     <?php endforeach; ?>
    </select>
</div>

<div class="row g-4">
    <div class="col-6 col-md-3 mb-3">
        <div class="card text-white bg-primary shadow-sm" id="card-deposito">
            <div class="card-body">
                <h6>Depósitos</h6>
                <h3>R$ 0,00</h3>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-3">
        <div class="card text-white bg-danger shadow-sm" id="card-retirada">
            <div class="card-body">
                <h6>Retiradas</h6>
                <h3>R$ 0,00</h3>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-3">
        <div class="card text-white bg-success shadow-sm" id="card-lucro">
            <div class="card-body">
                <h6>Lucro / Prejuízos</h6>
                <h3>R$ 0,00</h3>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-3">
        <div class="card text-white bg-info shadow-sm" id="card-saldo">
            <div class="card-body">
                <h6>Saldo</h6>
                <h3>R$ 0,00</h3>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-3">
        <div class="card text-white bg-secondary shadow-sm" id="card-dias">
            <div class="card-body">
                <h6>Dias</h6>
                <h3>0</h3>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-3">
        <div class="card text-white bg-warning shadow-sm" id="card-avg">
            <div class="card-body">
                <h6>Avg dia</h6>
                <h3>R$ 0,00</h3>
            </div>
        </div>
    </div>
</div>

<hr class="my-4">

<h5><i class="bi bi-graph-up"></i> Estatísticas Recentes</h5>
<canvas id="chartResumo" height="100"></canvas>

<script>
let chart;


function atualizarDashboard( ) {
   let carteiraId =$('#selectCarteira').val();
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `/app/api/dashboard.php?carteiraId=${carteiraId}`, true);

    xhr.onreadystatechange = function() {
        if(xhr.readyState === 4) {
            if(xhr.status === 200) {
                try {
                    const json = JSON.parse(xhr.responseText);
                    if(!json.success) return console.error(json.msg);

                    const d = json.data;

                    document.querySelector('#card-deposito h3').textContent =
                        d.deposito.toLocaleString('pt-BR', {style:'currency', currency:'BRL'});
                    document.querySelector('#card-retirada h3').textContent =
                        d.retirada.toLocaleString('pt-BR', {style:'currency', currency:'BRL'});
                    document.querySelector('#card-lucro h3').textContent =
                        d.lucro.toLocaleString('pt-BR', {style:'currency', currency:'BRL'});
                    document.querySelector('#card-saldo h3').textContent =
                        d.saldo.toLocaleString('pt-BR', {style:'currency', currency:'BRL'});
                    document.querySelector('#card-dias h3').textContent = d.dias;
                    document.querySelector('#card-avg h3').textContent =
                        d.avg_dia.toLocaleString('pt-BR', {style:'currency', currency:'BRL'});

                    // Atualizar gráfico
                    chart.data.labels = d.chart_labels;
                    chart.data.datasets[0].data = d.chart_data;
                    chart.update();

                } catch(e) {
                    console.error('Erro ao processar JSON', e);
                }
            } else {
                console.error('Erro na requisição Ajax', xhr.statusText);
            }
        }
    }

    xhr.send();
}




// Inicializar Chart.js
const ctx = document.getElementById('chartResumo');
chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Lucro Diário',
            data: [],
            borderWidth: 2,
            borderColor: 'dodgerblue',
            fill: true,
            backgroundColor: 'rgba(30,144,255,0.1)',
            tension: 0.3
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

// Atualizar automaticamente
atualizarDashboard();
setInterval(atualizarDashboard, 10000);

// Atualizar ao trocar a carteira
document.getElementById('selectCarteira').addEventListener('change', atualizarDashboard);

</script>

<?php include __DIR__.'/includes/footer.php'; ?>
