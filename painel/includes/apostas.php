<style>
    /* Container Principal: Alinha os itens horizontalmente */
    .action-menu {
        display: flex;
        flex-direction: row; 
        flex-wrap: wrap;
        justify-content: center;
        
        /* Estilos do Bloco */
        max-width: fit-content; 
        margin: 15px auto; /* Espaçamento ajustado para caber no layout */
        padding: 10px;
        
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* Espaçamento entre os ícones */
    .action-menu a {
        margin: 0 5px; 
    }

    /* Oculta o título */
    .action-menu h3 {
        display: none;
    }

    /* Estilo dos Botões (Ícones) */
    .action-menu .btn {
        width: 45px;
        height: 45px;
        padding: 8px;
        display: flex; 
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 1.1rem; 
    }
</style>

<div class="action-menu-wrapper">
    <div class="action-menu">
        <h3 class="text-center mb-4">Menu de Ações</h3>

        <a href="#" 
           class="btn btn-primary" 
           data-bs-toggle="tooltip" 
           data-bs-placement="bottom" 
           title="Aposta em Cassino" 
           data-bs-target="#modalCassino">
            <span class="material-symbols-outlined">casino</span> 
        </a>

        <a href="#" 
           class="btn btn-success" 
           data-bs-toggle="tooltip" 
           data-bs-placement="bottom" 
           title="Aposta em Esportes" 
           data-bs-target="#modalEsportes">
            <span class="material-symbols-outlined">sports_soccer</span> 
        </a>

        <a href="#" 
           class="btn btn-warning text-dark" 
           data-bs-toggle="tooltip" 
           data-bs-placement="bottom" 
           title="Gerar Desafio de Cassino" 
           data-bs-target="#modalDesafio">
            <span class="material-symbols-outlined">star</span> 
        </a>

        <a href="#" 
           class="btn btn-info text-dark" 
           data-bs-toggle="tooltip" 
           data-bs-placement="bottom" 
           title="Depósito" 
           data-bs-target="#modalDeposito">
            <span class="material-symbols-outlined">account_balance</span> 
        </a>

        <a href="#" 
           class="btn btn-danger" 
           data-bs-toggle="tooltip" 
           data-bs-placement="bottom" 
           title="Retirada" 
           data-bs-target="#modalRetirada">
            <span class="material-symbols-outlined">payments</span> 
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Inicialização do Tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl) 
        })
    });
</script>