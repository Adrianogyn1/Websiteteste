<style>
    .action-menu {
        display: flex;
        flex-direction: column; /* Alinha os botões verticalmente */
        gap: 15px; /* Espaçamento entre os botões */
        max-width: 100px; /* Reduz a largura para caber apenas o ícone */
        margin: 50px auto; /* Centraliza na página */
        padding: 20px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .action-menu .btn {
        /* Remove a centralização dupla de texto para focar apenas no ícone */
        padding: 12px; /* Ajusta o padding para um botão menor */
        font-size: 1.1rem;
        font-weight: 600;
        text-decoration: none;
    }
    /* Oculta o título do menu, já que ele é bem compacto */
    .action-menu h3 {
        display: none;
    }
</style>

<div class="container">
    <div class="action-menu">
        <h3 class="text-center mb-4">Menu de Ações</h3>

        <a href="#" 
           class="btn btn-primary" 
           data-bs-toggle="tooltip" 
           data-bs-placement="right" 
           title="Adicionar Jogos de Cassino" 
           data-bs-target="#modalCassino">
            <span class="material-symbols-outlined">casino</span> 
            </a>

        <a href="#" 
           class="btn btn-success" 
           data-bs-toggle="tooltip" 
           data-bs-placement="right" 
           title="Aposta em Esportes" 
           data-bs-target="#modalEsportes">
            <span class="material-symbols-outlined">sports_soccer</span> 
            </a>

        <a href="#" 
           class="btn btn-warning text-dark" 
           data-bs-toggle="tooltip" 
           data-bs-placement="right" 
           title="Gerar Desafio de Cassino" 
           data-bs-target="#modalDesafio">
            <span class="material-symbols-outlined">star</span> 
            </a>

        <a href="#" 
           class="btn btn-info text-dark" 
           data-bs-toggle="tooltip" 
           data-bs-placement="right" 
           title="Depósito" 
           data-bs-target="#modalDeposito">
            <span class="material-symbols-outlined">account_balance</span> 
            </a>

        <a href="#" 
           class="btn btn-danger" 
           data-bs-toggle="tooltip" 
           data-bs-placement="right" 
           title="Retirada" 
           data-bs-target="#modalRetirada">
            <span class="material-symbols-outlined">payments</span> 
            </a>
    </div>
</div>

<script>
    // Inicialização do Tooltip
    // Este código DEVE ser incluído na sua página APÓS o carregamento do Bootstrap JS.
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>