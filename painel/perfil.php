<?php include __DIR__.'/includes/header.php'; ?>

<main class="container-fluid pt-5 mt-3">
    <div class="container py-4">

        <!-- ===== TÍTULO ===== -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">
                <span class="material-symbols-outlined text-primary align-middle">account_circle</span>
                Meu Perfil
            </h3>
        </div>

        <!-- ===== PERFIL DO USUÁRIO ===== -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center mb-3 mb-md-0">
                        <div class="position-relative d-inline-block">
                            <img src="https://www.gravatar.com/avatar/?d=mp&s=160" 
                                 alt="Foto de perfil" 
                                 class="rounded-circle border border-3 border-primary" 
                                 width="120" height="120">
                            <button class="btn btn-sm btn-outline-primary rounded-circle position-absolute bottom-0 end-0" title="Alterar foto">
                                <span class="material-symbols-outlined">photo_camera</span>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h5 class="fw-bold mb-1"><?= $_SESSION['user']['nome'] ?? 'Usuário' ?></h5>
                        <p class="text-muted mb-2"><?= $_SESSION['user']['email'] ?? 'email@dominio.com' ?></p>
                        <p class="mb-0"><strong>Registrado em:</strong> <?= $_SESSION['user']['data_registro'] ?? '2025-01-01' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== FORMULÁRIO DE EDIÇÃO ===== -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-light">
                <span class="material-symbols-outlined align-middle me-1">edit</span>
                Editar Informações
            </div>
            <div class="card-body">
                <form id="formPerfil" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="nome" 
                                   value="<?= $_SESSION['user']['nome'] ?? '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?= $_SESSION['user']['email'] ?? '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" name="senha" placeholder="Deixe em branco para manter">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirmar Senha</label>
                            <input type="password" class="form-control" name="confirma" placeholder="Repita a nova senha">
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <span class="material-symbols-outlined align-middle me-1">save</span>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</main>

<script>
    // Exemplo de comportamento local (simulação de update)
    $('#formPerfil').on('submit', function (e) {
        e.preventDefault();

        const nome = $('input[name="nome"]').val();
        const email = $('input[name="email"]').val();

        if (!nome || !email) {
            alert('Preencha todos os campos obrigatórios.');
            return;
        }

        alert('Perfil atualizado com sucesso!');
        // Aqui você poderia fazer um $.post('api/updatePerfil.php', $(this).serialize())
    });
</script>

<?php include __DIR__.'/includes/footer.php'; ?>




