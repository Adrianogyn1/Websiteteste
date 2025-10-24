<?php include __DIR__.'/includes/header.php'; ?>
<?php include __DIR__.'/includes/menu.php'; ?>






<h4 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h4>

    <h2>Bem-vindo, <?= htmlspecialchars($_SESSION['user']) ?></h2>
    
    
<div class="row g-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Total de Jogos</h6>
                <h3>58</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Usuários</h6>
                <h3>127</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Transações</h6>
                <h3>3.942</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Lucro Total</h6>
                <h3>R$ 8.540</h3>
            </div>
        </div>
    </div>
</div>

<hr class="my-4">

<h5><i class="bi bi-graph-up"></i> Estatísticas Recentes</h5>
<canvas id="chartResumo" height="100"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('chartResumo');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
        datasets: [{
            label: 'Lucro Diário',
            data: [1200, 950, 1600, 800, 1400, 1900, 1700],
            borderWidth: 2,
            borderColor: 'dodgerblue',
            fill: true,
            backgroundColor: 'rgba(30,144,255,0.1)',
            tension: 0.3
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>

<?php include __DIR__.'/includes/footer.php'; ?>




