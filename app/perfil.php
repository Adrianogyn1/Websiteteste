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
                       
                         <div class="col-md-4 mb-4">
                <div class="avatar-card text-center sticky-top">
                    <h5 class="mb-3">Seu Avatar</h5>
                    <div id="avatar-container">
                        <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                                        </div>
                                        </div>
                                                   <a href="./avatar.php" class="btn btn-sm btn-outline-primary rounded-circle position-absolute bottom-0 end-0" title="Alterar foto">
                                <span class="material-symbols-outlined">photo_camera</span>
                            </a>
                       
                       <!-- <div class="position-relative d-inline-block" id="avatar-container">
                            <img src="https://www.gravatar.com/avatar/?d=mp&s=160" 
                                 alt="Foto de perfil" 
                                 class="rounded-circle border border-3 border-primary" 
                                 width="120" height="120">
                            <button class="btn btn-sm btn-outline-primary rounded-circle position-absolute bottom-0 end-0" title="Alterar foto">
                                <span class="material-symbols-outlined">photo_camera</span>
                            </button>
                        </div>
                        -->
                    </div>
                    <div class="col-md-9">
                        <h5 class="fw-bold mb-1"><?= $user->nome ?? 'Usuário' ?></h5>
                        <p class="text-muted mb-2"><?= $user->email ?? 'email@dominio.com' ?></p>
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
                                   value="<?= $user->nome  ?? '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?= $user->email ?? '' ?>" required>
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
    // Opções Padrão
    let currentOptions = {
        style: 'circle',
        top: 'shortWaved', 
        skin: 'light', 
        clothing: 'hoodie',
        clothingColor: 'blue03',
        eyes: 'happy',
        eyebrows: 'default',
        mouth: 'smile',
        hairColor: 'brown',
        facialHair: null,
        facialHairColor: 'brown',
        accessories: null,
        width: "150",
        height: "150"
    };

    // --- BLOCO PHP PARA CARREGAR DO BANCO DE DADOS ---
    <?php 
    // 1. Verifica se existe uma configuração de avatar salva para o usuário
    if($user->avatar && $user->avatar != ""): 
        // 2. Cria uma nova variável JavaScript chamada 'savedOptionsJson'
        // IMPORTANTE: use json_encode() para garantir que a string JSON PHP seja válida em JS
        // O $user->avatar DEVE ser uma string JSON válida aqui.
    ?>
    
    const savedOptionsJson = <?php echo json_encode($user->avatar); ?>;

    try {
        // Faz o parse da string JSON para um objeto JavaScript
        const savedOptions = JSON.parse(savedOptionsJson);
        
        // Sobrescreve as opções padrão com as opções salvas
        currentOptions = {
            ...currentOptions, // Mantém o style, width e height
            ...savedOptions    // Aplica as configurações salvas (top, skin, eyes, etc.)
        };

        console.log("Avatar carregado do BD!");

    } catch (e) {
        console.error("Erro ao fazer parse da configuração do avatar do banco de dados:", e);
    }

    <?php 
    endif; 
    ?>
    // --------------------------------------------------

    // ... (O restante do seu código JavaScript, incluindo renderAvatar(), saveAvatar(), etc., continua aqui)
</script>


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
    
    function renderAvatar() {
            try {
                const currentOptions =<?= $user->avatar; ?>
                // A função Avataaars.create() retorna a string SVG
                const svg = Avataaars.create(currentOptions);
                $('#avatar-container').html(svg);
            } catch (e) {
                console.error("Erro ao gerar avatar:", e);
               // $('#avatar-container').html('<div class="alert alert-danger">Erro ao carregar. Verifique se avataaars.js foi carregado corretamente.</div>');
            }
        }
        
      $(document).ready(function(){
          renderAvatar();
      });
</script>

<?php include __DIR__.'/includes/footer.php'; ?>




